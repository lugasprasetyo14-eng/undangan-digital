<?php

namespace Tests\Feature;

use App\Models\{Invitation, Invoice, Package, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function setupBilling(): array
    {
        config()->set('tokopay.enabled', true);
        config()->set('tokopay.merchant_id', 'M-TEST');
        config()->set('tokopay.secret', 'secret');
        config()->set('tokopay.channels', ['QRIS']);
        $owner = User::factory()->create(['phone' => '08123456789']);
        $other = User::factory()->create();
        $invitation = Invitation::factory()->create(['user_id' => $owner->id]);
        $otherInvitation = Invitation::factory()->create(['user_id' => $other->id]);
        $package = Package::create(['code' => 'test-'.Str::lower(Str::random(8)), 'name' => 'Rona Test', 'price' => 99000, 'is_active' => true]);
        return compact('owner', 'other', 'invitation', 'otherInvitation', 'package');
    }

    private function makeInvoice(array $context, string $reference = 'RONA-TEST'): Invoice
    {
        return Invoice::create([
            'invitation_id' => $context['invitation']->id, 'package_id' => $context['package']->id,
            'reference' => $reference, 'package_name' => $context['package']->name,
            'amount' => $context['package']->price, 'channel' => 'QRIS',
            'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789',
        ]);
    }

    public function test_create_order_uses_server_price_and_does_not_activate_until_check_succeeds(): void
    {
        ['owner' => $owner, 'invitation' => $invitation, 'package' => $package] = $this->setupBilling();
        Http::fake([
            'https://api.tokopay.id/v1/order' => Http::response(['status' => 'Success', 'data' => ['trx_id' => 'TP-1', 'pay_url' => 'https://pay.tokopay.id/?token=x']]),
            'https://api.tokopay.id/v1/check-order*' => Http::response(['status' => 1, 'data' => ['reff_id' => 'RONA-*', 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-1', 'status' => 'Unpaid']]),
        ]);

        $response = $this->actingAs($owner)->post(route('billing.checkout', $invitation), [
            'package_id' => $package->id, 'channel' => 'QRIS', 'customer_name' => 'Nama Asli',
            'customer_email' => 'nama@example.test', 'customer_phone' => '08123456789',
        ]);

        $response->assertRedirect();
        $invoice = Invoice::firstOrFail();
        $this->assertSame(99000, $invoice->amount);
        $this->assertSame('pending', $invoice->status);
        $this->assertDatabaseCount('invitation_entitlements', 0);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.tokopay.id/v1/order'
            && $request['amount'] === 99000 && $request['kode_channel'] === 'QRIS'
            && $request['signature'] === md5('M-TEST:secret:'.$invoice->reference));
    }

    public function test_check_order_success_is_idempotent_and_does_not_publish_invitation(): void
    {
        ['owner' => $owner, 'invitation' => $invitation, 'package' => $package] = $this->setupBilling();
        $invoice = Invoice::create([
            'invitation_id' => $invitation->id, 'package_id' => $package->id, 'reference' => 'RONA-CHECK',
            'package_name' => $package->name, 'amount' => 99000, 'channel' => 'QRIS',
            'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789',
        ]);
        Http::fake(['https://api.tokopay.id/v1/check-order*' => Http::response(['status' => 1, 'data' => [
            'reff_id' => 'RONA-CHECK', 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-CHECK', 'status' => 'Completed',
        ]])]);

        $this->actingAs($owner)->post(route('billing.check', $invoice))->assertRedirect();
        $this->actingAs($owner)->post(route('billing.check', $invoice))->assertRedirect();
        $this->assertDatabaseCount('invitation_entitlements', 1);
        $this->assertSame('draft', $invitation->fresh()->status);
    }

    public function test_invalid_callback_and_cross_account_invoice_are_rejected(): void
    {
        ['owner' => $owner, 'other' => $other, 'invitation' => $invitation, 'package' => $package] = $this->setupBilling();
        $invoice = Invoice::create([
            'invitation_id' => $invitation->id, 'package_id' => $package->id, 'reference' => 'RONA-CALLBACK',
            'package_name' => $package->name, 'amount' => 99000, 'channel' => 'QRIS',
            'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789',
        ]);
        $this->postJson(route('billing.callback'), ['reff_id' => $invoice->reference, 'signature' => 'bad'])->assertForbidden();
        $this->actingAs($other)->get(route('billing.show', $invoice))->assertForbidden();
    }

    public function test_invoice_keeps_price_snapshot_and_repeat_checkout_reuses_reference(): void
    {
        $context = $this->setupBilling();
        Http::fake(['https://api.tokopay.id/v1/order' => Http::response(['status' => 'Success', 'data' => ['trx_id' => 'TP-SNAPSHOT', 'pay_url' => 'https://pay.tokopay.id/?token=x']])]);

        $payload = ['package_id' => $context['package']->id, 'channel' => 'QRIS', 'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789'];
        $this->actingAs($context['owner'])->post(route('billing.checkout', $context['invitation']), $payload)->assertRedirect();
        $reference = Invoice::firstOrFail()->reference;
        $context['package']->update(['price' => 125000, 'name' => 'Harga Baru']);
        $this->actingAs($context['owner'])->post(route('billing.checkout', $context['invitation']), $payload)->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('invoices', ['reference' => $reference, 'amount' => 99000, 'package_name' => 'Rona Test']);
        $this->assertCount(1, collect(Http::recorded())->filter(fn ($recorded) => $recorded[0]->url() === 'https://api.tokopay.id/v1/order'));
    }

    public function test_create_timeout_reconciles_same_reference_without_second_order(): void
    {
        $context = $this->setupBilling();
        $calls = 0;
        Http::fake(function ($request) use (&$calls) {
            $calls++;
            if ($request->url() === 'https://api.tokopay.id/v1/order') {
                throw new ConnectionException('timeout');
            }
            return Http::response(['status' => 1, 'data' => ['reff_id' => Invoice::first()->reference, 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-TIMEOUT', 'status' => 'Unpaid']]);
        });

        $this->actingAs($context['owner'])->post(route('billing.checkout', $context['invitation']), [
            'package_id' => $context['package']->id, 'channel' => 'QRIS', 'customer_name' => 'A', 'customer_email' => 'a@example.test', 'customer_phone' => '08123456789',
        ])->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame('pending', Invoice::firstOrFail()->status);
        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('invitation_entitlements', 0);
    }

    public function test_unpaid_and_failed_never_activate_and_valid_success_activates_once(): void
    {
        $cases = [];
        foreach (['Unpaid', 'Failed', 'Success', 'Completed'] as $status) {
            $context = $this->setupBilling();
            $invoice = $this->makeInvoice($context, 'RONA-'.$status);
            $cases[] = compact('context', 'invoice', 'status');
        }
        Http::fake(function ($request) use ($cases) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $case = collect($cases)->first(fn ($case) => $case['invoice']->reference === ($query['ref_id'] ?? null));
            return Http::response(['status' => 1, 'data' => [
                'reff_id' => $case['invoice']->reference, 'merchant_id' => 'M-TEST', 'nominal' => 99000,
                'trx_id' => 'TP-'.$case['status'], 'status' => $case['status'],
            ]]);
        });
        foreach ($cases as $case) {
            $context = $case['context'];
            $invoice = $case['invoice'];
            $status = $case['status'];
            $response = $this->actingAs($context['owner'])->post(route('billing.check', $invoice));
            $response->assertRedirect();
            $expected = in_array($status, ['Success', 'Completed'], true) ? 'paid' : ($status === 'Failed' ? 'failed' : 'pending');
            $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => $expected]);
            $this->assertSame(in_array($status, ['Success', 'Completed'], true), \App\Models\InvitationEntitlement::where('invitation_id', $context['invitation']->id)->exists());
        }
    }

    public function test_callback_success_duplicate_and_late_unpaid_cannot_downgrade_invoice(): void
    {
        $context = $this->setupBilling();
        $invoice = $this->makeInvoice($context, 'RONA-CALLBACK-VALID');
        $signature = md5('M-TEST:secret:'.$invoice->reference);
        Http::fakeSequence('https://api.tokopay.id/v1/check-order*')
            ->push(['status' => 1, 'data' => ['reff_id' => $invoice->reference, 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-CALLBACK', 'status' => 'Success']])
            ->push(['status' => 1, 'data' => ['reff_id' => $invoice->reference, 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-CALLBACK', 'status' => 'Success']])
            ->push(['status' => 1, 'data' => ['reff_id' => $invoice->reference, 'merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-CALLBACK', 'status' => 'Unpaid']]);

        $payload = ['reff_id' => $invoice->reference, 'signature' => $signature];
        $this->postJson(route('billing.callback'), $payload)->assertOk()->assertExactJson(['status' => true]);
        $this->postJson(route('billing.callback'), $payload)->assertOk()->assertExactJson(['status' => true]);
        $this->postJson(route('billing.callback'), $payload)->assertOk()->assertExactJson(['status' => true]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertDatabaseCount('invitation_entitlements', 1);
    }

    public function test_unknown_reference_and_status_identity_mismatches_never_activate(): void
    {
        $context = $this->setupBilling();
        $this->postJson(route('billing.callback'), ['reff_id' => 'RONA-NOT-FOUND', 'signature' => md5('M-TEST:secret:RONA-NOT-FOUND')])->assertNotFound();
        foreach ([
            ['merchant_id' => 'OTHER', 'nominal' => 99000, 'trx_id' => 'TP-1'],
            ['merchant_id' => 'M-TEST', 'nominal' => 98000, 'trx_id' => 'TP-2'],
            ['merchant_id' => 'M-TEST', 'nominal' => 99000, 'trx_id' => 'TP-3'],
        ] as $index => $data) {
            $context = $this->setupBilling();
            $invoice = $this->makeInvoice($context, 'RONA-MISMATCH-'.$index);
            if ($index === 2) {
                $invoice->update(['trx_id' => 'TP-ORIGINAL']);
            }
            Http::fake(['https://api.tokopay.id/v1/check-order*' => Http::response(['status' => 1, 'data' => [
                'reff_id' => $invoice->reference, ...$data, 'status' => 'Success',
            ]])]);
            $this->actingAs($context['owner'])->post(route('billing.check', $invoice))->assertRedirect();
            $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'pending']);
        }
        $this->assertDatabaseCount('invitation_entitlements', 0);
    }

    public function test_provider_api_failure_does_not_activate(): void
    {
        $context = $this->setupBilling();
        $invoice = $this->makeInvoice($context, 'RONA-API-FAIL');
        Http::fake(['https://api.tokopay.id/v1/check-order*' => Http::response(['status' => 0, 'error_msg' => 'Data Not Found'], 500)]);
        $this->actingAs($context['owner'])->post(route('billing.check', $invoice))->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'pending']);
        $this->assertDatabaseCount('invitation_entitlements', 0);
    }
}