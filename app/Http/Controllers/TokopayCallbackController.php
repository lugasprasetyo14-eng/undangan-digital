<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Tokopay;
use Illuminate\Http\{JsonResponse, Request};
use RuntimeException;

class TokopayCallbackController extends Controller
{
    public function __invoke(Request $request, Tokopay $tokopay): JsonResponse
    {
        if (! $tokopay->ready()) {
            return response()->json(['status' => false], 503);
        }
        $reference = $request->input('reff_id');
        $signature = $request->input('signature');
        if (! is_string($reference) || ! is_string($signature)
            || ! hash_equals($tokopay->signature($reference), $signature)) {
            return response()->json(['status' => false], 403);
        }
        $invoice = Invoice::where('reference', $reference)->first();
        if (! $invoice) {
            return response()->json(['status' => false], 404);
        }
        try {
            $tokopay->reconcile($invoice);
        } catch (RuntimeException $e) {
            return response()->json(['status' => false], 503);
        }
        return response()->json(['status' => true]);
    }
}