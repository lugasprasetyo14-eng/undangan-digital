<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Invitation, InvitationEntitlement, InvitationMedia, Invoice, Package, Theme, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, Http, Storage};
use Tests\TestCase;

class Phase9AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_layout_exposes_csrf_logout_and_logout_removes_admin_access(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin'))
            ->assertOk()
            ->assertSee('Keluar')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);

        $this->actingAs($admin)->post(route('logout'))->assertRedirect('/');
        $this->get(route('admin'))->assertRedirect(route('login'));
    }

    public function test_guest_and_customer_cannot_access_any_admin_page_or_write_action(): void
    {
        $invitation = Invitation::factory()->published()->create();
        $theme = $invitation->theme;
        $package = Package::create(['code' => 'p9', 'name' => 'P9', 'price' => 10000, 'is_active' => true]);
        $invoice = Invoice::create(['invitation_id' => $invitation->id, 'package_id' => $package->id, 'reference' => 'P9-1', 'package_name' => 'P9', 'amount' => 10000, 'channel' => 'QRIS', 'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789']);
        foreach ([route('admin'), route('admin.users'), route('admin.invitations'), route('admin.themes'), route('admin.packages'), route('admin.invoices'), route('admin.audit')] as $url) {
            $this->get($url)->assertRedirect(route('login'));
            $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
            Auth::logout();
        }
        $this->actingAs(User::factory()->create())->patch(route('admin.invitations.unpublish', $invitation), ['reason' => 'x'])->assertForbidden();
        $this->actingAs(User::factory()->create())->patch(route('admin.themes.update', $theme), ['is_active' => false])->assertForbidden();
        $this->actingAs(User::factory()->create())->patch(route('admin.packages.update', $package), ['name' => 'x', 'price' => 1, 'is_active' => false])->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.invoices.show', $invoice))->assertForbidden();
    }

    public function test_admin_can_unpublish_and_audit_log_removes_public_page_and_media(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $invitation = Invitation::factory()->published()->create();
        Storage::disk('local')->put('p9/photo.jpg', 'image');
        $media = InvitationMedia::create(['invitation_id' => $invitation->id, 'type' => 'photo', 'path' => 'p9/photo.jpg', 'thumbnail_path' => 'p9/photo.jpg']);
        $this->get(route('invitations.public', $invitation->slug))->assertOk();
        $this->get(route('invitations.media.file', [$invitation->slug, $media, 'thumb']))->assertOk();
        $this->actingAs($admin)->patch(route('admin.invitations.unpublish', $invitation), ['reason' => 'Moderasi konten'])->assertRedirect();
        $this->assertDatabaseHas('invitations', ['id' => $invitation->id, 'status' => 'draft', 'published_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'invitation.unpublish', 'target_id' => $invitation->id, 'reason' => 'Moderasi konten']);
        $this->get(route('invitations.public', $invitation->slug))->assertNotFound();
        $this->get(route('invitations.media.file', [$invitation->slug, $media, 'thumb']))->assertNotFound();
    }

    public function test_theme_availability_only_affects_new_selection_and_existing_theme_still_renders(): void
    {
        $admin = $this->admin();
        $theme = Theme::factory()->create(['is_active' => true, 'slug' => 'p9-theme']);
        $invitation = Invitation::factory()->published()->create(['theme_id' => $theme->id]);
        $this->actingAs($admin)->patch(route('admin.themes.update', $theme), ['is_active' => false])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'theme.availability_updated', 'target_id' => $theme->id]);
        $this->actingAs(User::factory()->create())->get(route('dashboard.invitations.create'))->assertDontSee('p9-theme');
        $this->actingAs(User::factory()->create())->post(route('dashboard.invitations.store'), ['title' => 'New', 'event_type' => 'wedding', 'groom_name' => 'A', 'bride_name' => 'B', 'theme_id' => $theme->id])->assertSessionHasErrors('theme_id');
        $this->get(route('invitations.public', $invitation->slug))->assertOk()->assertSee($invitation->groom_name);
    }

    public function test_package_change_preserves_invoice_snapshot_and_entitlement_and_invoice_filter_is_read_only(): void
    {
        $admin = $this->admin();
        $invitation = Invitation::factory()->create();
        $package = Package::create(['code' => 'p9-package', 'name' => 'Lama', 'price' => 99000, 'is_active' => true]);
        $invoice = Invoice::create(['invitation_id' => $invitation->id, 'package_id' => $package->id, 'reference' => 'P9-FILTER', 'package_name' => 'Lama', 'amount' => 99000, 'channel' => 'QRIS', 'status' => 'paid', 'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789']);
        $entitlement = InvitationEntitlement::create(['invitation_id' => $invitation->id, 'package_id' => $package->id, 'invoice_id' => $invoice->id, 'activated_at' => now()]);
        Http::fake();
        $this->actingAs($admin)->patch(route('admin.packages.update', $package), ['name' => 'Baru', 'price' => 125000, 'is_active' => false])->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'package_name' => 'Lama', 'amount' => 99000, 'status' => 'paid']);
        $this->assertDatabaseHas('invitation_entitlements', ['id' => $entitlement->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'package.updated', 'target_id' => $package->id]);
        $this->actingAs($admin)->get(route('admin.invoices', ['status' => 'paid']))->assertOk()->assertSee('P9-FILTER')->assertDontSee('pay.tokopay.id');
        Http::assertNothingSent();
    }
}