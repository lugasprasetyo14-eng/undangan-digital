<?php

namespace App\Http\Controllers;

use App\Models\{Invitation, Invoice, Package};
use App\Services\Tokopay;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class BillingController extends Controller
{
    public function index(): View
    {
        return view('dashboard.billing.index', [
            'invitations' => request()->user()->invitations()->with('entitlements')->get(),
            'packages' => Package::where('is_active', true)->get(),
            'invoices' => Invoice::whereHas('invitation', fn ($q) => $q->where('user_id', request()->user()->id))->with('invitation')->latest()->paginate(15),
        ]);
    }

    public function checkout(Request $request, Invitation $invitation, Tokopay $tokopay): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        $data = $request->validate([
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('is_active', true)],
            'channel' => ['required', 'string', Rule::in(config('tokopay.channels'))],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ]);
        if (! $tokopay->ready()) {
            return back()->withErrors(['billing' => 'Pembayaran belum diaktifkan. Tidak ada tagihan provider yang dibuat.']);
        }
        $action = 'none';
        $invoice = DB::transaction(function () use ($invitation, $data, &$action) {
            Invitation::lockForUpdate()->findOrFail($invitation->id);
            $package = Package::where('is_active', true)->findOrFail($data['package_id']);
            $existing = $invitation->invoices()->where('package_id', $package->id)->where('active_slot', 1)->first();
            if ($existing) {
                if ($existing->status === 'paid') {
                    return $existing;
                }
                if ($existing->status === 'failed') {
                    $existing->update(['status' => 'pending']);
                }
                $action = filled($existing->trx_id) ? 'reconcile' : 'create';
                return $existing;
            }
            $action = 'create';
            return $invitation->invoices()->create([
                'package_id' => $package->id, 'package_name' => $package->name, 'amount' => $package->price,
                'reference' => 'RONA-'.Str::ulid(), 'channel' => $data['channel'],
                'customer_name' => $data['customer_name'], 'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
            ]);
        });
        if ($action !== 'none') {
            try {
                $action === 'create' ? $tokopay->create($invoice) : $tokopay->reconcile($invoice);
            } catch (RuntimeException $e) {
                return to_route('billing.show', $invoice)->withErrors(['billing' => $e->getMessage()]);
            }
        }
        return to_route('billing.show', $invoice);
    }

    public function show(Invoice $invoice, Tokopay $tokopay): View
    {
        Gate::authorize('view', $invoice->invitation);
        return view('dashboard.billing.show', ['invoice' => $invoice, 'canPay' => $tokopay->safePaymentUrl($invoice->pay_url)]);
    }

    public function check(Invoice $invoice, Tokopay $tokopay): RedirectResponse
    {
        Gate::authorize('update', $invoice->invitation);
        try {
            $tokopay->reconcile($invoice);
        } catch (RuntimeException $e) {
            return back()->withErrors(['billing' => $e->getMessage()]);
        }
        return to_route('billing.show', $invoice)->with('status', 'Status pembayaran telah diperiksa.');
    }
}