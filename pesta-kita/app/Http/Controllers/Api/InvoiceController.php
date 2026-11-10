<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless(
            in_array($request->user()->id, [$invoice->order->client_id, $invoice->order->vendor_id], true),
            404
        );

        return response()->json($invoice->load('order:id,event_id,client_id,vendor_id,status,payment_status'));
    }
}
