<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Reimbursement;
use App\Models\Site;
use App\Models\User;
use App\Services\SensitiveFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('public');
        Storage::fake('sensitive');
    }

    private function site(): Site
    {
        $branch = Branch::create(['branch_name' => 'Branch', 'branch_code' => uniqid()]);

        return Site::create(['branch_id' => $branch->id, 'machine_name' => 'Machine', 'slug' => uniqid(), 'location' => 'Jakarta']);
    }

    private function claim(User $owner): Reimbursement
    {
        return Reimbursement::create(['user_id' => $owner->id, 'person_name' => 'Owner', 'date' => '2026-10-22',
            'category' => 'office', 'amount' => 100, 'receipt_attachment' => 'receipts/test.pdf', 'status' => 'pending_leader']);
    }

    public function test_receipt_requires_login_and_existing_claim_permissions(): void
    {
        $site = $this->site();
        $owner = User::factory()->create(['role' => 'employee_role', 'site_id' => $site->id]);
        $claim = $this->claim($owner);
        Storage::disk('sensitive')->put($claim->receipt_attachment, '%PDF-1.4 test');
        $url = route('reimbursements.receipt', $claim);
        $this->get($url)->assertRedirect(route('login'));
        $other = User::factory()->create(['role' => 'employee_role', 'site_id' => $site->id]);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $leader = User::factory()->create(['role' => 'team_leader', 'site_id' => $site->id]);
        $this->actingAs($leader)->get($url)->assertOk();
        $leader->update(['site_id' => $this->site()->id]);
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))->get($url)->assertOk();
        $claim->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_receipt_upload_and_replacement_are_private(): void
    {
        $owner = User::factory()->create(['role' => 'employee_role', 'site_id' => $this->site()->id]);
        $payload = ['person_name' => 'Owner', 'date' => '2026-10-22', 'category' => 'office', 'amount' => 100,
            'receipt_attachment' => UploadedFile::fake()->image('receipt.png')];
        $this->actingAs($owner)->post('/reimbursements/store', $payload)->assertSessionHasNoErrors();
        $claim = Reimbursement::firstOrFail();
        $oldPath = $claim->receipt_attachment;
        Storage::disk('sensitive')->assertExists($oldPath);
        Storage::disk('public')->assertMissing($oldPath);
        $payload['receipt_attachment'] = UploadedFile::fake()->image('replacement.png');
        $this->put('/reimbursements/'.$claim->id, $payload)->assertSessionHasNoErrors();
        $newPath = $claim->fresh()->receipt_attachment;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('sensitive')->assertExists($newPath);
        Storage::disk('public')->assertMissing($newPath);
        $this->get(route('reimbursements.receipt', $claim))->assertOk();
    }

    public function test_leave_attachment_uses_site_scope_and_private_upload(): void
    {
        $site = $this->site();
        $owner = User::factory()->create(['role' => 'employee_role', 'site_id' => $site->id]);
        $employee = Employee::create(['name' => 'Engineer', 'site_id' => $site->id, 'branch_id' => $site->branch_id, 'phone_number' => '123', 'mcu' => 'no', 'tld' => 'no']);
        $type = LeaveType::create(['name' => 'Medical', 'default_quota' => 12, 'cut_annual_quota' => false]);
        $this->actingAs($owner)->post('/leave', ['employee_id' => $employee->id, 'leave_type_id' => $type->id,
            'start_date' => '2026-10-22', 'end_date' => '2026-10-22', 'reason' => 'Medical',
            'attachment_file' => UploadedFile::fake()->image('medical.png')])->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        Storage::disk('sensitive')->assertExists($leave->attachment_file);
        Storage::disk('public')->assertMissing($leave->attachment_file);
        $this->get(route('leave.attachment', $leave))->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'employee_role', 'site_id' => $this->site()->id]))
            ->get(route('leave.attachment', $leave))->assertForbidden();
    }

    public static function receiptFormats(): array
    {
        return [['png', 'sensitive'], ['pdf', 'sensitive'], ['png', 'public'], ['pdf', 'public']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('receiptFormats')]
    public function test_signing_and_pdf_export_work_with_private_receipts(string $extension, string $diskName): void
    {
        $owner = User::factory()->create(['role' => 'employee_role', 'site_id' => $this->site()->id]);
        $claim = $this->claim($owner);
        $claim->update(['status' => 'pending_manager', 'receipt_attachment' => 'receipts/signable.'.$extension]);
        if ($extension === 'pdf') {
            $pdf = new \setasign\Fpdi\Fpdi;
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(30, 10, 'Receipt');
            $bytes = $pdf->Output('S');
        } else {
            $receiptFile = UploadedFile::fake()->image('receipt.png', 600, 400);
            $bytes = file_get_contents($receiptFile->getPathname());
        }
        Storage::disk('sensitive')->put($claim->receipt_attachment, $bytes);
        $manager = User::factory()->create(['role' => 'manager']);
        $signatureFile = UploadedFile::fake()->image('signature.png', 120, 40);
        $signature = base64_encode(file_get_contents($signatureFile->getPathname()));
        $this->actingAs($manager)->put('/reimbursements/'.$claim->id.'/approve', [
            'signature' => 'data:image/png;base64,'.$signature, 'pos_x' => 10, 'pos_y' => 10,
            'scale_w' => 20, 'scale_h' => 10, 'page' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNotSame($bytes, Storage::disk('sensitive')->get($claim->receipt_attachment));
        $this->assertCount(1, Storage::disk('sensitive')->allFiles('signatures'));
        if ($diskName === 'sensitive') {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
        if ($diskName === 'public') {
            Storage::disk('public')->put($claim->receipt_attachment, Storage::disk('sensitive')->get($claim->receipt_attachment));
            Storage::disk('sensitive')->delete($claim->receipt_attachment);
        }
        $claim->update(['receipt_attachment' => 'storage/'.$claim->receipt_attachment]);
        $this->actingAs($owner)->get('/reimbursements/'.$claim->id.'/export-single-pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame([], Storage::disk('sensitive')->allFiles('temporary'));
    }

    public function test_legacy_migration_is_verified_repeatable_and_keeps_public_images(): void
    {
        foreach (SensitiveFiles::DIRECTORIES as $dir) {
            Storage::disk('public')->put($dir.'/old.pdf', 'private bytes');
        }
        Storage::disk('public')->put('daily_reports/photo.jpg', 'public photo');
        $this->artisan('security:privatize-attachments', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('public')->assertExists('receipts/old.pdf');
        Storage::disk('sensitive')->assertMissing('receipts/old.pdf');
        $this->artisan('security:privatize-attachments')->assertSuccessful();
        foreach (SensitiveFiles::DIRECTORIES as $dir) {
            Storage::disk('public')->assertMissing($dir.'/old.pdf');
            $this->assertSame('private bytes', Storage::disk('sensitive')->get($dir.'/old.pdf'));
        }
        Storage::disk('public')->assertExists('daily_reports/photo.jpg');
        $this->artisan('security:privatize-attachments')->assertSuccessful();
    }

    public function test_migration_never_overwrites_conflicting_private_file(): void
    {
        Storage::disk('public')->put('receipts/conflict.pdf', 'legacy');
        Storage::disk('sensitive')->put('receipts/conflict.pdf', 'different private data');
        $this->artisan('security:privatize-attachments')->assertFailed();
        $this->assertSame('legacy', Storage::disk('public')->get('receipts/conflict.pdf'));
        $this->assertSame('different private data', Storage::disk('sensitive')->get('receipts/conflict.pdf'));
    }

    public function test_legacy_receipt_falls_back_to_public_and_prefers_sensitive(): void
    {
        $owner = User::factory()->create(['role' => 'employee_role']);
        $claim = $this->claim($owner);
        Storage::disk('public')->put($claim->receipt_attachment, '%PDF-1.4 public legacy');
        $this->actingAs($owner)->get(route('reimbursements.receipt', $claim))->assertOk();
        $this->assertSame(Storage::disk('public')->path($claim->receipt_attachment), SensitiveFiles::receiptPath($claim->receipt_attachment));
        $claim->update(['receipt_attachment' => 'storage/receipts/test.pdf']);
        Storage::disk('sensitive')->put('receipts/test.pdf', '%PDF-1.4 private');
        $this->assertSame(Storage::disk('sensitive')->path('receipts/test.pdf'), SensitiveFiles::receiptPath($claim->receipt_attachment));
        $this->get(route('reimbursements.receipt', $claim))->assertOk();
    }

    public function test_imported_claim_without_receipt_still_opens_and_missing_attachment_returns_404(): void
    {
        $owner = User::factory()->create(['role' => 'employee_role']);
        $claim = $this->claim($owner);
        $claim->update(['receipt_attachment' => null]);
        $this->actingAs($owner)->get(route('reimbursements.approval', $claim))->assertOk();
        $this->get(route('reimbursements.receipt', $claim))->assertNotFound();
    }

    public function test_sensitive_path_cannot_escape_private_root(): void
    {
        foreach (['../.env', 'receipts/../../.env', 'receipts/..\\.env', 'contracts/secret.pdf', '/etc/passwd'] as $path) {
            try {
                SensitiveFiles::path($path);
                $this->fail('Unsafe path accepted: '.$path);
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('Invalid sensitive attachment path.', $e->getMessage());
            }
        }
    }

    public function test_seeder_does_not_create_accounts_with_shared_credentials(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_interactive_admin_creation_rejects_weak_password_and_never_overwrites_accounts(): void
    {
        $this->artisan('security:create-admin')
            ->expectsQuestion('Nama admin', 'Admin')
            ->expectsQuestion('Username', 'new-admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka, simbol)', '123456')
            ->expectsQuestion('Ulangi password', '123456')->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->artisan('security:create-admin')
            ->expectsQuestion('Nama admin', 'Admin')
            ->expectsQuestion('Username', 'new-admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka, simbol)', 'Example-test-2026!')
            ->expectsQuestion('Ulangi password', 'Example-test-2026!')->assertSuccessful();
        $admin = User::where('username', 'new-admin')->firstOrFail();
        $this->assertSame('superadmin', $admin->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Example-test-2026!', $admin->password));
        $this->artisan('security:create-admin')
            ->expectsQuestion('Nama admin', 'Replacement')
            ->expectsQuestion('Username', 'new-admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka, simbol)', 'Another-test-2026!')
            ->expectsQuestion('Ulangi password', 'Another-test-2026!')->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($admin->password, $admin->fresh()->password);
    }

    public function test_untrusted_forwarded_headers_cannot_spoof_client_ip_or_https(): void
    {
        \Illuminate\Support\Facades\Route::get('/security-proxy-check', fn (\Illuminate\Http\Request $request) => [
            'ip' => $request->ip(), 'secure' => $request->isSecure(),
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders(['X-Forwarded-For' => '192.0.2.1', 'X-Forwarded-Proto' => 'https'])
            ->getJson('http://localhost/security-proxy-check')->assertOk()
            ->assertJson(['ip' => '203.0.113.42', 'secure' => false]);
    }
}
