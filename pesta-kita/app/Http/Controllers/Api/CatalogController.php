<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', Rule::in(['vendor', 'venue', 'equipment'])],
            'category' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'min_capacity' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string', 'max:100'],
        ]);

        $items = CatalogItem::query()
            ->where('is_available', true)
            ->with(['user:id,name,role', 'user.vendorProfile:user_id,business_name,category,address'])
            ->when(isset($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(isset($filters['category']), fn (Builder $query) => $query->where('category', 'like', '%'.$filters['category'].'%'))
            ->when(isset($filters['location']), fn (Builder $query) => $query->where('location', 'like', '%'.$filters['location'].'%'))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('price', '<=', $filters['max_price']))
            ->when(isset($filters['min_capacity']), fn (Builder $query) => $query->where('capacity', '>=', $filters['min_capacity']))
            ->when(isset($filters['search']), fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json($items);
    }

    public function show(CatalogItem $catalogItem): JsonResponse
    {
        abort_unless($catalogItem->is_available, 404);

        return response()->json($catalogItem->load([
            'user:id,name,role',
            'user.vendorProfile:user_id,business_name,category,address,description',
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRole($request);
        $validated = $this->validatedItem($request);
        $this->authorizeItemType($request, $validated['type']);

        $item = $request->user()->catalogItems()->create($validated);

        return response()->json($item, 201);
    }

    public function update(Request $request, CatalogItem $catalogItem): JsonResponse
    {
        $this->authorizeOwner($request, $catalogItem);
        $validated = $this->validatedItem($request, partial: true);
        $this->authorizeItemType($request, $validated['type'] ?? $catalogItem->type);
        $catalogItem->update($validated);

        return response()->json($catalogItem->refresh());
    }

    public function destroy(Request $request, CatalogItem $catalogItem): JsonResponse
    {
        $this->authorizeOwner($request, $catalogItem);
        abort_if($catalogItem->orders()->exists(), 409,
            'Item yang sudah memiliki riwayat pesanan tidak dapat dihapus. Tandai item tidak tersedia.');

        $catalogItem->delete();

        return response()->json(['message' => 'Item katalog berhasil dihapus.']);
    }

    private function validatedItem(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return $request->validate([
            'type' => [$required, Rule::in(['vendor', 'venue', 'equipment'])],
            'name' => [$required, 'string', 'max:255'],
            'category' => [$required, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'price' => [$required, 'numeric', 'min:0', 'max:9999999999.99'],
            'is_available' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['vendor', 'venue'], true), 403,
            'Hanya vendor atau pengelola venue yang dapat mengelola katalog.');
    }

    private function authorizeOwner(Request $request, CatalogItem $catalogItem): void
    {
        abort_unless($catalogItem->user_id === $request->user()->id, 404);
        $this->authorizeRole($request);
    }

    private function authorizeItemType(Request $request, string $type): void
    {
        abort_unless(
            ($request->user()->role === 'venue' && $type === 'venue')
                || ($request->user()->role === 'vendor' && in_array($type, ['vendor', 'equipment'], true)),
            422,
            'Jenis katalog tidak sesuai dengan role akun.'
        );
    }
}
