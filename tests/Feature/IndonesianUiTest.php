<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndonesianUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_json_validation_and_creation_preserve_permissions(): void
    {
        Http::fake();
        $branch = Branch::create(['branch_name' => 'Jakarta', 'branch_code' => 'JKT']);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->postJson('/sites', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['machine_name', 'branch_id', 'location'])
            ->assertJsonPath('errors.machine_name.0', 'Nama mesin wajib diisi.');
        $this->postJson('/sites', ['branch_id' => $branch->id, 'machine_name' => 'Mesin Uji', 'location' => 'Jakarta'])
            ->assertCreated()->assertJsonPath('site.machine_name', 'Mesin Uji');
        $site = Site::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'team_leader', 'site_id' => $site->id]));
        $this->postJson('/sites', ['branch_id' => $branch->id, 'machine_name' => 'Dilarang', 'location' => 'Jakarta'])
            ->assertForbidden();
        $this->assertDatabaseCount('sites', 1);
    }

    public function test_regrouped_navigation_keeps_the_existing_role_visibility(): void
    {
        Http::fake();
        foreach (['superadmin', 'team_leader', 'administration', 'employee_role', 'station_master', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $html = preg_replace('/\s+/', ' ', view('layout.aside')->render());
            $this->assertStringContainsString('Reimbursement', $html);
            $this->assertStringContainsString('Laporan Harian', $html);
            $this->assertStringContainsString(route('schedule.index'), $html);
            $this->assertSame($role === 'superadmin', str_contains($html, route('employee.index')));
            $this->assertSame($role === 'superadmin', str_contains($html, route('salary.index')));
            $this->assertSame($role === 'superadmin', str_contains($html, route('branches.index')));
            $this->assertSame(in_array($role, ['superadmin', 'team_leader']), str_contains($html, route('sparepart.all')));
        }
    }

    public function test_validation_summary_keeps_safe_old_input_and_does_not_expose_passwords(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->withSession([
            '_old_input' => ['name' => 'Nama Dipertahankan', '_ui_form' => 'form-3', 'password' => 'never-expose-this', '_token' => 'never-expose-token'],
            'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['name' => 'Nama wajib diisi.'])),
        ])->get('/profile/create')->assertOk()
            ->assertSee('Data belum tersimpan')->assertSee('Nama Dipertahankan')
            ->assertDontSee('never-expose-this')->assertDontSee('never-expose-token');
    }
}
