<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundRequestController extends Controller
{
    /**
     * List client refund requests as intake only.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'diajukan');
        if (! is_string($status) || ! in_array($status, ['diajukan', 'semua'], true)) {
            $status = 'diajukan';
        }

        return view('admin.pembatalan', [
            'refundRequests' => RefundRequest::query()
                ->with(['event', 'client', 'invoice'])
                ->when($status !== 'semua', fn ($query) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    /**
     * Show intake details without final decision or amount controls.
     */
    public function show(RefundRequest $refundRequest): View
    {
        return view('admin.pembatalan-detail', [
            'refundRequest' => $refundRequest->load(['event', 'client', 'invoice']),
        ]);
    }
}
