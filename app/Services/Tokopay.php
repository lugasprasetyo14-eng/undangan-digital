<?php

namespace App\Services;

use App\Models\{Invoice, InvitationEntitlement};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{DB, Http};
use RuntimeException;

class Tokopay
{
    public function ready(): bool
    {
        return config('tokopay.enabled') && filled(config('tokopay.merchant_id')) && filled(config('tokopay.secret'));
    }

    public function signature(string $reference): string
    {
        return md5(config('tokopay.merchant_id').':'.config('tokopay.secret').':'.$reference);
    }

    public function safePaymentUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        return ($parts['scheme'] ?? '') === 'https'
            && in_array($parts['host'] ?? '', config('tokopay.payment_hosts'), true)
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['port']);
    }

    public function create(Invoice $invoice): void
    {
        $this->requireReady();
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->post('https://api.tokopay.id/v1/order', [
                    'merchant_id' => config('tokopay.merchant_id'),
                    'signature' => $this->signature($invoice->reference),
                    'reff_id' => $invoice->reference,
                    'amount' => $invoice->amount,
                    'kode_channel' => $invoice->channel,
                    'customer_name' => $invoice->customer_name,
                    'customer_email' => $invoice->customer_email,
                    'customer_phone' => $invoice->customer_phone,
                    'expired_ts' => 0,
                    'redirect_url' => route('billing.show', $invoice),
                ]);
            $data = $response->json('data');
            // Referensi terpakai mengembalikan bentuk check-order, bukan create-order.
            if ($response->successful() && $response->json('status') === 'Success'
                && is_array($data) && is_string($data['trx_id'] ?? null) && filled($data['trx_id'])
                && $this->safePaymentUrl($data['pay_url'] ?? null)) {
                DB::transaction(function () use ($invoice, $data) {
                    $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
                    if ($locked->trx_id && $locked->trx_id !== $data['trx_id']) {
                        throw new RuntimeException('Identitas transaksi tidak cocok.');
                    }
                    $locked->update(['trx_id' => $data['trx_id'], 'pay_url' => $data['pay_url']]);
                });
                return;
            }
        } catch (ConnectionException $e) {
            // Timeout tidak membuktikan order gagal; rekonsiliasi referensi yang sama.
        }
        $this->reconcile($invoice);
    }

    public function reconcile(Invoice $invoice): void
    {
        $this->requireReady();
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->get('https://api.tokopay.id/v1/check-order', [
                    'merchant_id' => config('tokopay.merchant_id'),
                    'secret' => config('tokopay.secret'),
                    $invoice->trx_id ? 'trx_id' : 'ref_id' => $invoice->trx_id ?: $invoice->reference,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Status pembayaran belum dapat diverifikasi. Coba cek status lagi.');
        }
        $data = $response->json('data');
        if (! $response->successful() || ! is_array($data)
            || ($data['reff_id'] ?? null) !== $invoice->reference
            || ($data['merchant_id'] ?? null) !== config('tokopay.merchant_id')
            || ! is_int($data['nominal'] ?? null) || $data['nominal'] !== $invoice->amount
            || ! is_string($data['trx_id'] ?? null) || ! filled($data['trx_id'])
            || ! in_array($data['status'] ?? null, ['Unpaid', 'Failed', 'Success', 'Completed', 'Proses Callback'], true)) {
            throw new RuntimeException('Respons pembayaran tidak dapat diverifikasi.');
        }
        DB::transaction(function () use ($invoice, $data) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($locked->trx_id && $locked->trx_id !== $data['trx_id']) {
                throw new RuntimeException('Identitas transaksi tidak cocok.');
            }
            if ($locked->status === 'paid') {
                return;
            }
            $paid = in_array($data['status'], ['Success', 'Completed'], true);
            $locked->update([
                'trx_id' => $data['trx_id'],
                'status' => $paid ? 'paid' : ($data['status'] === 'Failed' ? 'failed' : 'pending'),
                'paid_at' => $paid ? now() : null,
            ]);
            if ($paid) {
                InvitationEntitlement::firstOrCreate([
                    'invitation_id' => $locked->invitation_id, 'package_id' => $locked->package_id,
                ], ['invoice_id' => $locked->id, 'activated_at' => now()]);
            }
        });
    }

    private function requireReady(): void
    {
        if (! $this->ready()) {
            throw new RuntimeException('Pembayaran Tokopay belum diaktifkan. Tidak ada permintaan ke provider.');
        }
    }
}