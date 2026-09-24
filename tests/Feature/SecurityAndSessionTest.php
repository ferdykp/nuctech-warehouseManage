<?php

namespace Tests\Feature;

use App\Models\{Branch, DailyReport, DailyReportPhoto, Employee, EmployeeLeaveBalance, LeaveRequest, LeaveType, Reimbursement, Shift, Site, Sparepart, SparepartStock, SparepartTransfer, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Http, Route, Storage};
use Tests\TestCase;

class SecurityAndSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('public');
    }

    private function site(string $name = 'Machine'): Site
    {
        $branch = Branch::create(['branch_name' => 'Branch', 'branch_code' => uniqid('B')]);
        return Site::create(['branch_id' => $branch->id, 'machine_name' => $name, 'slug' => uniqid('site-'), 'location' => 'Jakarta']);
    }

    private function user(string $role = 'employee_role', ?Site $site = null): User
    {
        return User::factory()->create(['role' => $role, 'site_id' => $site?->id]);
    }

    private function employee(Site $site): Employee
    {
        return Employee::create(['name' => 'Engineer', 'site_id' => $site->id, 'branch_id' => $site->branch_id, 'phone_number' => '123', 'mcu' => 'no', 'tld' => 'no']);
    }

    private function claim(User $owner, string $status = 'pending'): Reimbursement
    {
        return Reimbursement::create(['user_id' => $owner->id, 'person_name' => $owner->name, 'date' => '2026-09-01', 'category' => 'office', 'amount' => 100, 'receipt_attachment' => 'receipts/test.png', 'status' => $status]);
    }

    public function test_login_logout_back_and_login_again(): void
    {
        $user = $this->user();
        $this->post('/login/auth', ['username' => $user->username, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get('/profile/profile')->assertOk()->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/profile/profile')->assertRedirect(route('login'));
        $this->post('/login/auth', ['username' => $user->username, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_idle_session_expires_and_background_checks_do_not_extend_it(): void
    {
        $user = $this->user();
        config(['session.idle_timeout' => 30]);
        $last = now()->subMinutes(29)->timestamp;
        $this->actingAs($user)->withSession(['last_activity_at' => $last]);
        $this->getJson('/session/status')->assertOk()->assertJson(['expires_at' => ($last + 1800) * 1000]);
        $this->assertSame($last, session('last_activity_at'));
        $this->travel(2)->minutes();
        $this->getJson('/session/status')->assertUnauthorized();
        $this->assertGuest();
        $this->get('/profile/profile')->assertRedirect(route('login'));
    }

    public function test_activity_refreshes_session_and_expired_html_request_redirects(): void
    {
        $user = $this->user();
        $this->actingAs($user)->withSession(['last_activity_at' => now()->subMinutes(20)->timestamp]);
        $this->postJson('/session/activity')->assertNoContent();
        $this->assertSame(now()->timestamp, session('last_activity_at'));
        $this->travel(31)->minutes();
        $this->get('/profile/profile')->assertRedirect(route('login'))->assertSessionHas('warning');
        $this->assertGuest();
    }

    public function test_login_aliases_and_expired_csrf_form_recover(): void
    {
        $this->get('/login')->assertRedirect(route('login'));
        $this->get('/login/auth')->assertRedirect(route('login'));
        // Enable real CSRF verification, normally disabled by Laravel's test environment.
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'fresh-token'])->post('/login/auth', ['_token' => 'expired-token'])->assertRedirect(route('login'))->assertSessionHas('warning');
        $this->postJson('/session/activity', ['_token' => 'expired-token'])->assertStatus(419);
    }

    public function test_invalid_login_never_flashes_password(): void
    {
        $this->post('/login/auth', ['username' => 'unknown', 'password' => 'secret-value'])
            ->assertSessionHasErrors('username')->assertSessionMissing('_old_input.password');
    }

    public function test_user_cannot_edit_another_account_or_escalate_own_role(): void
    {
        $user = $this->user();
        $admin = $this->user('superadmin');
        $this->actingAs($user)->get('/profile/profileEdit/'.$admin->id)->assertForbidden();
        $this->put('/profile/profileEdit/'.$admin->id, ['name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertForbidden();
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->put('/profile/profileEdit/'.$user->id, ['name' => 'Updated', 'username' => $user->username, 'email' => $user->email, 'role' => 'superadmin'])->assertRedirect();
        $this->assertSame('employee_role', $user->fresh()->role);
    }

    public function test_admin_can_create_employee_role(): void
    {
        $site = $this->site();
        $this->actingAs($this->user('superadmin'))->post('/profile/store', ['name' => 'Employee', 'username' => 'employee-new', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'employee_role', 'site_id' => $site->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'employee-new', 'role' => 'employee_role']);
    }

    public function test_all_controller_routes_resolve_to_public_methods(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (!str_contains($route->getActionName(), '@')) continue;
            [$class, $method] = explode('@', $route->getActionName());
            $this->assertTrue(method_exists($class, $method), $route->uri().' -> '.$method);
            $this->assertTrue((new \ReflectionMethod($class, $method))->isPublic());
        }
    }

    public function test_daily_report_archive_preserves_files_and_denies_other_sites(): void
    {
        $site = $this->site();
        $owner = $this->user('employee_role', $site);
        $report = DailyReport::create(['site_id' => $site->id, 'user_id' => $owner->id, 'report_date' => '2026-09-01', 'description' => 'Test']);
        Storage::disk('public')->put('daily_reports/photo.jpg', 'image');
        DailyReportPhoto::create(['daily_report_id' => $report->id, 'photo_path' => 'daily_reports/photo.jpg']);
        $this->actingAs($this->user('employee_role', $this->site()))->delete('/daily-reports/'.$report->id)->assertForbidden();
        $this->actingAs($owner)->delete('/daily-reports/'.$report->id)->assertRedirect();
        Storage::disk('public')->assertExists('daily_reports/photo.jpg');
        $this->assertSoftDeleted($report);
        $this->actingAs($this->user('employee_role', $this->site()))->post('/daily-reports/'.$report->id.'/restore')->assertForbidden();
        $this->delete('/daily-reports/'.$report->id.'/force-delete')->assertForbidden();
        $this->actingAs($owner)->post('/daily-reports/'.$report->id.'/restore')->assertRedirect();
        $this->assertNotSoftDeleted($report);
    }

    public function test_daily_report_cannot_be_created_in_another_site(): void
    {
        $this->actingAs($this->user('employee_role', $this->site()))->post('/daily-reports', ['site_id' => $this->site()->id, 'report_date' => '2026-09-01', 'description' => 'Invalid'])->assertForbidden();
        $this->assertDatabaseCount('daily_reports', 0);
    }

    public function test_employee_cannot_approve_leave_and_approval_is_only_applied_once(): void
    {
        $site = $this->site();
        $employee = $this->employee($site);
        $type = LeaveType::create(['name' => 'Annual', 'default_quota' => 12, 'cut_annual_quota' => true]);
        $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-02', 'total_days' => 2, 'reason' => 'Test']);
        $this->actingAs($this->user('employee_role', $site))->post('/leave/'.$leave->id.'/approve')->assertForbidden();
        $this->actingAs($this->user('team_leader', $this->site()))->post('/leave/'.$leave->id.'/approve')->assertForbidden();
        $this->actingAs($this->user('team_leader', $site))->post('/leave/'.$leave->id.'/approve')->assertSessionHasNoErrors();
        $this->post('/leave/'.$leave->id.'/approve')->assertSessionHasErrors('leave');
        $this->assertDatabaseHas('employee_leave_balances', ['employee_id' => $employee->id, 'used_quota' => 2, 'remaining_quota' => 10]);
    }

    public function test_insufficient_leave_balance_does_not_approve(): void
    {
        $site = $this->site(); $employee = $this->employee($site);
        $type = LeaveType::create(['name' => 'Annual', 'default_quota' => 1, 'cut_annual_quota' => true]);
        $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-02', 'total_days' => 2, 'reason' => 'Test']);
        $this->actingAs($this->user('superadmin'))->post('/leave/'.$leave->id.'/approve')->assertSessionHasErrors('leave');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_transfer_cannot_be_approved_or_received_twice(): void
    {
        $from = $this->site(); $to = $this->site();
        $part = Sparepart::create(['item_name' => 'Part', 'type' => 'T', 'uom' => 'pcs']);
        $stock = SparepartStock::create(['sparepart_id' => $part->id, 'site_id' => $from->id, 'condition' => 'new', 'qty' => 10]);
        $transfer = SparepartTransfer::create(['sparepart_id' => $part->id, 'from_site_id' => $from->id, 'to_site_id' => $to->id, 'condition' => 'new', 'from_condition' => 'new', 'qty' => 3, 'status' => 'pending']);
        $this->actingAs($this->user('team_leader', $to))->post('/movement/approve/'.$transfer->id)->assertForbidden();
        $this->actingAs($this->user('team_leader', $from))->post('/movement/approve/'.$transfer->id)->assertSessionHasNoErrors();
        $this->post('/movement/approve/'.$transfer->id)->assertSessionHasErrors('transfer');
        $this->assertSame(7, $stock->fresh()->qty);
        $this->actingAs($this->user('team_leader', $to))->post('/movement/receive/'.$transfer->id)->assertRedirect();
        $this->post('/movement/receive/'.$transfer->id)->assertSessionHasErrors('transfer');
        $this->assertDatabaseHas('sparepart_stocks', ['site_id' => $to->id, 'qty' => 3]);
        $this->assertDatabaseCount('sparepart_histories', 2);
    }

    public function test_schedule_updates_and_clear_are_scoped_to_authorized_sites(): void
    {
        $site = $this->site('ABCDE One'); $other = $this->site('OTHER Two');
        $employee = $this->employee($other);
        $shift = Shift::create(['shift_name' => 'Day', 'start_time' => '08:00', 'end_time' => '16:00']);
        $this->actingAs($this->user('team_leader', $site))->postJson('/schedule/update-single', ['employee_id' => $employee->id, 'date' => '2026-09-01', 'shift_id' => $shift->id])->assertForbidden();
        $this->delete('/schedule/clear', ['month' => 9, 'year' => 2026, 'site_id' => (string) $other->id])->assertForbidden();
        $this->get('/schedule/export?site_id='.$other->id)->assertForbidden();
        $this->actingAs($this->user('superadmin'))->postJson('/schedule/update-single', ['employee_id' => $employee->id, 'date' => '2026-09-01', 'shift_id' => $shift->id])->assertOk();
        $this->delete('/schedule/clear', ['month' => 9, 'year' => 2026, 'site_id' => (string) $other->id])->assertRedirect();
        $this->assertDatabaseCount('employee_schedules', 0);
    }

    public function test_reimbursement_access_and_approval_order(): void
    {
        $owner = $this->user('employee_role', $this->site());
        $claim = $this->claim($owner, 'pending_manager');
        $this->actingAs($this->user('employee_role', $this->site()))->get('/reimbursements/'.$claim->id.'/export-single-pdf')->assertForbidden();
        $this->delete('/reimbursements/'.$claim->id)->assertForbidden();
        $this->actingAs($owner)->put('/reimbursements/'.$claim->id.'/reject', ['rejected_reason' => 'Invalid'])->assertForbidden();
        $this->actingAs($this->user('manager'))->get('/reimbursements')->assertOk();
        $this->put('/reimbursements/'.$claim->id.'/reject', ['rejected_reason' => 'Invalid'])->assertRedirect();
        $this->assertSame('rejected', $claim->fresh()->status);
    }

    public function test_webhook_denies_missing_secret_and_unapproved_senders(): void
    {
        config(['services.telegram.webhook_secret' => 'test-secret', 'services.telegram.allowed_user_ids' => ['123'], 'services.telegram.chat_id' => '456']);
        $payload = ['message' => ['text' => '/backup', 'from' => ['id' => 789], 'chat' => ['id' => 456]]];
        $this->postJson('/telegram/webhook', $payload)->assertForbidden();
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')->postJson('/telegram/webhook', $payload)->assertForbidden();
        Http::assertNothingSent();
        $payload['message']['from']['id'] = 123; $payload['message']['text'] = '/help';
        $this->postJson('/telegram/webhook', $payload)->assertOk();
    }
    public function test_primary_pages_render_for_superadmin(): void
    {
        $site = $this->site();
        $this->employee($site);
        $this->actingAs($this->user('superadmin'));
        foreach (['/dashboard', '/categories', '/sites', '/branches', '/shift', '/report', '/report/create', '/spareparts/all', '/inventory/'.$site->slug, '/reimbursements', '/reimbursements/create', '/daily-reports', '/daily-reports/create', '/attendance', '/schedules', '/leave', '/leave/create', '/employee', '/employee/create', '/salary', '/salary/create', '/profile/profileList'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_invalid_schedule_generation_does_not_write_patterns(): void
    {
        $site = $this->site(); $employee = $this->employee($site);
        $this->actingAs($this->user('superadmin'))->post('/schedules/generate', [
            'target_site_ids' => [$site->id], 'employee_ids' => [$employee->id], 'month' => 2, 'year' => 2026,
            'start_day' => 31, 'schedule_type' => 'shift_rotation', 'active_shifts' => [],
        ])->assertSessionHasErrors('active_shifts');
        $this->assertDatabaseCount('site_schedules', 0);
        $this->assertSame(1, \Illuminate\Support\Facades\DB::transactionLevel());
    }

    public function test_schedule_export_and_failure_export_download(): void
    {
        $site = $this->site(); $this->employee($site);
        $this->actingAs($this->user('superadmin'));
        $this->get('/schedule/export?site_id='.$site->id.'&month=9&year=2026')->assertOk()->assertDownload();
        $this->get('/report/export')->assertOk()->assertDownload();
    }

    public function test_stock_adjustment_allows_correction_upward_but_denies_cross_site(): void
    {
        $site = $this->site();
        $part = Sparepart::create(['item_name' => 'Part', 'type' => 'T', 'uom' => 'pcs']);
        $stock = SparepartStock::create(['sparepart_id' => $part->id, 'site_id' => $site->id, 'condition' => 'new', 'qty' => 3]);
        $this->actingAs($this->user('team_leader', $this->site()))->post('/inventory/'.$site->slug.'/adjust/'.$stock->id, ['qty_to_move' => 5, 'new_condition' => 'new'])->assertForbidden();
        $this->actingAs($this->user('team_leader', $site))->post('/inventory/'.$site->slug.'/adjust/'.$stock->id, ['qty_to_move' => 5, 'new_condition' => 'new'])->assertRedirect();
        $this->assertSame(5, $stock->fresh()->qty);
        $this->post('/inventory/'.$site->slug.'/adjust/'.$stock->id, ['qty_to_move' => 6, 'new_condition' => 'damaged'])->assertSessionHasErrors('qty_to_move');
        $this->assertSame(5, $stock->fresh()->qty);
    }

}
