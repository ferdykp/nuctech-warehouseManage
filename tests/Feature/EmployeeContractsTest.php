<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Site;
use App\Models\User;
use App\Services\Contracts\ContractDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeContractsTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $b = Branch::create(['branch_name' => 'Jakarta', 'branch_code' => 'CT', 'branch_address' => 'Alamat Branch Uji']);
        $s = Site::create(['branch_id' => $b->id, 'machine_name' => 'Test', 'slug' => 'contract-test', 'location' => 'Jakarta']);

        return Employee::create(['site_id' => $s->id, 'branch_id' => $b->id, 'name' => 'Karyawan Kontrak Uji', 'nik' => '1234567890123456', 'phone_number' => '0800000000', 'basic_salary' => 6500000, 'position' => 'Engineer', 'mcu' => 'no', 'tld' => 'no', 'pkwt_date' => '2026-01-01', 'is_active' => true]);
    }

    private function data(Employee $e): array
    {
        $t = app(ContractDocuments::class)->defaultTemplate();

        return ['employee_id' => $e->id, 'contract_number' => 'PKWT/TEST/001', 'pkwt_date' => '2026-01-01', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'template_id' => $t->id, 'job_type' => 'Software Engineer', 'working_hours' => 'Shift', 'leave_entitlement' => '12 hari kerja per tahun', 'representative_name' => 'Perwakilan Uji', 'representative_position' => 'HR', 'representative_address' => 'Alamat HR'];
    }

    public function test_only_hr_roles_can_access_contracts_and_private_documents(): void
    {
        $e = $this->employee();
        $c = EmployeeContract::create(['employee_id' => $e->id, 'status' => 'draft']);
        foreach (['employee_role', 'team_leader', 'station_master', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('contracts.index'))->assertForbidden();
            $this->get(route('contracts.download', [$c, 'pdf']))->assertForbidden();
            $this->post(route('contracts.activate', $c), ['confirmed' => 1])->assertForbidden();
        }
        foreach (['superadmin', 'administration'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('contracts.index'))->assertOk();
            $this->get(route('contracts.create'))->assertOk();
            $this->get(route('contracts.create', ['employee_id' => $e->id]))->assertRedirect(route('contracts.show', $c));
            $this->get(route('contracts.show', $c))->assertOk();
            $this->get(route('contracts.templates'))->assertOk()->assertSee('${employee_name}', false);
        }
    }

    public function test_draft_uses_employee_salary_branch_and_pkwt_date_and_blocks_duplicate_work(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $e = $this->employee();
        $data = $this->data($e);
        $this->post(route('contracts.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $c = EmployeeContract::first();
        $this->assertEquals(6500000, $c->snapshot['basic_salary']);
        $this->assertSame('Alamat Branch Uji', $c->snapshot['employee_address']);
        $this->assertSame('2026-01-01', $c->contract_date->toDateString());
        $this->assertSame('draft', $c->status);
        $this->post(route('contracts.store'), array_replace($data, ['contract_number' => 'PKWT/TEST/002']))->assertSessionHasErrors('employee_id');
        $this->post(route('contracts.activate', $c), ['confirmed' => 1])->assertUnprocessable();
        $this->put(route('contracts.update', $c), array_replace($data, ['end_date' => '2025-01-01']))->assertSessionHasErrors('end_date');
        $this->get(route('contracts.edit', $c))->assertOk();
    }

    public function test_signed_contract_requires_acceptance_blocks_overlaps_and_freezes_snapshot(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'administration']));
        $e = $this->employee();
        $data = $this->data($e);
        $this->post(route('contracts.store'), $data);
        $c = EmployeeContract::first();
        $c->update(['status' => 'pending_signature', 'document_fingerprint' => $c->fingerprint()]);
        $this->post(route('contracts.signed', $c), ['signed_document' => UploadedFile::fake()->createWithContent('signed.pdf', "%PDF-1.4\nTest")])->assertSessionHasNoErrors();
        $this->post(route('contracts.activate', $c), ['confirmed' => 1])->assertUnprocessable();
        $this->post(route('contracts.communication', $c), ['notified_at' => today()->toDateString(), 'responded_at' => today()->toDateString(), 'employee_response' => 'accepted'])->assertSessionHasNoErrors();
        $this->post(route('contracts.activate', $c), ['confirmed' => 1])->assertRedirect();
        $this->assertSame('active', $c->fresh()->status);
        $this->put(route('contracts.update', $c), $data)->assertStatus(409);
        $this->post(route('contracts.cancel', $c), ['reason' => 'Test'])->assertStatus(409);
        $e->update(['name' => 'Nama Baru', 'basic_salary' => 9000000]);
        $this->assertSame('Karyawan Kontrak Uji', $c->fresh()->snapshot['employee_name']);
        $this->assertEquals(6500000, $c->fresh()->snapshot['basic_salary']);
        $this->post(route('contracts.store'), array_replace($data, ['contract_number' => 'PKWT/OVERLAP']))->assertSessionHasErrors('start_date');
        $this->post(route('contracts.store'), array_replace($data, ['contract_number' => 'PKWT/NEXT', 'pkwt_date' => '2027-01-01', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']))->assertSessionHasNoErrors();
        $next = EmployeeContract::latest('id')->first();
        $this->assertSame($c->id, $next->previous_contract_id);
        $this->get(route('contracts.download', [$c, 'signed']))->assertOk();
        $this->get(route('contracts.download', [$next, 'signed', 'event' => $c->events()->first()->id]))->assertNotFound();
    }

    public function test_document_preserves_template_and_escapes_data_without_leaking_original_identity(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $e = $this->employee();
        $e->update(['name' => 'Uji & <Nama>']);
        $this->post(route('contracts.store'), $this->data($e));
        $c = EmployeeContract::first()->load('template');
        $service = app(ContractDocuments::class);
        $service->validateTemplate($service->templatePath($c->template));
        // Exercise the real DOCX path; PDF conversion is optional on CI hosts.
        config(['contracts.soffice' => '/nonexistent/soffice']);
        $result = $service->generate($c);
        $this->assertTrue($result['conversion_error']);
        Storage::disk('local')->assertExists($result['docx_path']);
        $zip = new \ZipArchive;
        $zip->open(Storage::disk('local')->path($result['docx_path']));
        $xml = $zip->getFromName('word/document.xml');
        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $this->assertStringContainsString('Uji & <Nama>', $dom->textContent);
        $this->assertStringContainsString('Rp 6.500.000,00', $dom->textContent);
        $this->assertStringNotContainsString('${', $xml);
        $this->assertStringNotContainsString('3571011106010001', $xml);
        $this->assertStringNotContainsString('Ferdy Kurnia', $xml);
        $this->assertSame(3, substr_count($dom->textContent, 'Perwakilan Uji'));
        $this->assertStringNotContainsString('Banten 15411', $dom->textContent);
        $this->assertStringContainsString('PASAL 14', $dom->textContent);
        $zip->close();
        $this->post(route('contracts.generate', $c))->assertRedirect();
        $this->assertSame('pending_signature', $c->fresh()->status);
        $this->post(route('contracts.reopen', $c))->assertRedirect();
        $this->assertNull($c->fresh()->docx_path);
        $this->assertGreaterThan(0, $c->events()->where('action', 'Dokumen dibuat')->count());
    }

    public function test_template_upload_validation_and_scheduled_expired_statuses(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        Storage::fake('local');
        $this->post(route('contracts.templates.store'), ['name' => 'Bad', 'version' => 'v2', 'template' => UploadedFile::fake()->createWithContent('bad.docx', 'invalid')])->assertSessionHasErrors();
        $this->post(route('contracts.templates.store'), ['name' => 'Template Test', 'version' => 'v2', 'template' => new UploadedFile(resource_path('contracts/pkwt-default-v1.docx'), 'template.docx', null, null, true)])->assertSessionHasNoErrors()->assertRedirect();
        $e = $this->employee();
        $c = new EmployeeContract(['employee_id' => $e->id, 'status' => 'active', 'start_date' => today()->addDay(), 'end_date' => today()->addYear()]);
        $this->assertSame('Terjadwal', $c->display_status);
        $c->start_date = today()->subYear();
        $c->end_date = today()->subDay();
        $this->assertSame('Berakhir', $c->display_status);
    }

    public function test_verified_legacy_contract_can_archive_original_without_reissuing_and_keeps_employee_history(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $e = $this->employee();
        $c = EmployeeContract::create(['employee_id' => $e->id, 'status' => 'needs_verification', 'snapshot' => ['legacy_contract_number' => 'OLD/001']]);
        $this->post(route('contracts.signed', $c), ['signed_document' => UploadedFile::fake()->createWithContent('original.pdf', "%PDF-1.4\nTest")])->assertStatus(409);
        $this->put(route('contracts.update', $c), $this->data($e))->assertSessionHasNoErrors();
        $this->assertSame('OLD/001', $c->fresh()->snapshot['legacy_contract_number']);
        $this->post(route('contracts.signed', $c), ['signed_document' => UploadedFile::fake()->createWithContent('original.pdf', "%PDF-1.4\nTest")])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('pending_signature', $c->fresh()->status);
        $this->assertNull($c->fresh()->docx_path);
        $this->assertNotNull($c->fresh()->signed_document_path);
        $this->delete(route('employee.destroy', $e))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $e->id]);
        $this->delete(route('branches.destroy', $e->branch_id))->assertRedirect()->assertSessionHas('error');
        $this->delete(route('site.destroy', $e->site_id))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('branches', ['id' => $e->branch_id]);
        $this->assertDatabaseHas('sites', ['id' => $e->site_id]);
        $this->get(route('contracts.show', $c))->assertOk();
    }

    public function test_reminder_does_not_count_a_contract_that_already_has_an_approved_successor(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administration']));
        $e = $this->employee();
        $old = EmployeeContract::create(['employee_id' => $e->id, 'status' => 'active', 'contract_number' => 'CURRENT', 'start_date' => today()->subMonths(10), 'end_date' => today()->addDays(30)]);
        $this->get(route('contracts.index', ['status' => 'due']))->assertOk()->assertSee('CURRENT');
        EmployeeContract::create(['employee_id' => $e->id, 'status' => 'active', 'contract_number' => 'NEXT', 'previous_contract_id' => $old->id, 'start_date' => today()->addDays(31), 'end_date' => today()->addYear()]);
        $this->assertSame(0, EmployeeContract::needsRenewal()->whereBetween('end_date', [today(), today()->addDays(60)])->count());
        $this->get(route('contracts.index', ['status' => 'due']))->assertOk()->assertDontSee('CURRENT');
    }
}
