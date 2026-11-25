<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ShopProduct::query()
            ->with('center:id,name')
            ->where('is_active', true)
            ->when($request->filled('center_id'), fn ($q) => $q->where('center_id', $request->integer('center_id')))
            ->when(! $request->filled('center_id') && ! $request->boolean('include_center'), fn ($q) => $q->whereNull('center_id'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->string('brand')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('brand', 'like', $term);
                });
            })
            ->orderBy('name_ar');

        $products = $query->paginate(min(50, max(1, (int) $request->integer('per_page', 20))));

        $rows = collect($products->items())->map(fn (ShopProduct $p) => $this->publicPayload($p))->values()->all();

        return ApiResponse::success($rows, 'OK', 200, [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    public function show(ShopProduct $product): JsonResponse
    {
        abort_unless($product->is_active, 404);
        $product->loadMissing('center:id,name');

        return ApiResponse::success($this->publicPayload($product));
    }

    /**
     * Customer-facing payload — omits internal commission fields.
     *
     * @return array<string, mixed>
     */
    private function publicPayload(ShopProduct $product): array
    {
        return [
            'id' => $product->id,
            'center_id' => $product->center_id,
            'center' => $product->center ? [
                'id' => $product->center->id,
                'name' => $product->center->name,
            ] : null,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'slug' => $product->slug,
            'category' => $product->category,
            'brand' => $product->brand,
            'size' => $product->size,
            'description' => $product->description,
            'price' => (float) $product->price,
            'image_path' => $product->image_path,
            'stock' => (int) $product->stock,
            'is_active' => (bool) $product->is_active,
            'meta' => $product->meta,
        ];
    }
}
