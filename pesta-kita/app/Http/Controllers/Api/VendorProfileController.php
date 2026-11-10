<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->authorizeVendor($request);

        return response()->json($request->user()->load('vendorProfile'));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeVendor($request);

        $validated = $request->validate([
            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $request->user()->vendorProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json($request->user()->fresh()->load('vendorProfile'));
    }

    private function authorizeVendor(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['vendor', 'venue'], true), 403);
    }
}
