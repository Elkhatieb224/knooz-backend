<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Enums\ShopProductCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\ShopProduct;
use App\Support\CenterServiceCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $center = $this->requireProductsCenter($request);

        $products = ShopProduct::query()
            ->with('center')
            ->ownedByCenter($center->id)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('brand', 'like', $term);
                });
            })
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 20))));

        $rows = collect($products->items())
            ->map(fn (ShopProduct $p) => $this->serialize($p, $center, true))
            ->values()
            ->all();

        return ApiResponse::success($rows, 'OK', 200, [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $center = $this->requireProductsCenter($request);
        $data = $this->validated($request);
        $data['center_id'] = $center->id;
        $data['slug'] = $this->uniqueSlug((string) ($data['name_en'] ?? ''), (string) $data['name_ar']);
        $data = $this->applyImageUpload($request, $data);

        $product = ShopProduct::query()->create($data);
        $product->setRelation('center', $center);

        return ApiResponse::success($this->serialize($product, $center, true), 'تمت إضافة المنتج.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $center = $this->requireProductsCenter($request);
        $product = $this->findOwn($center->id, $id);

        return ApiResponse::success($this->serialize($product, $center, true));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $center = $this->requireProductsCenter($request);
        $product = $this->findOwn($center->id, $id);
        $data = $this->validated($request, $product);
        unset($data['slug']);
        $data = $this->applyImageUpload($request, $data, $product->image_path);
        $product->update($data);
        $fresh = $product->fresh();
        $fresh->setRelation('center', $center);

        return ApiResponse::success($this->serialize($fresh, $center, true), 'تم تحديث المنتج.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $center = $this->requireProductsCenter($request);
        $product = $this->findOwn($center->id, $id);

        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return ApiResponse::success(null, 'تم حذف المنتج.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ShopProduct $existing = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::enum(ShopProductCategory::class)],
            'brand' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        // Centers set price/stock only — commission is admin-controlled.
        unset($data['app_profit'], $data['app_profit_mode']);
        $data['is_active'] = $request->boolean('is_active', $existing?->is_active ?? true);

        if (! $existing) {
            $data['app_profit'] = null;
            $data['app_profit_mode'] = CenterServiceCommission::MODE_FIXED;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyImageUpload(Request $request, array $data, ?string $existingPath = null): array
    {
        unset($data['image']);

        if (! $request->hasFile('image')) {
            return $data;
        }

        $path = $request->file('image')->store('shop', 'public');
        $data['image_path'] = $path;

        if ($existingPath && $existingPath !== $path && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $data;
    }

    private function uniqueSlug(string $nameEn, string $nameAr): string
    {
        $base = Str::slug($nameEn !== '' ? $nameEn : $nameAr, '-');
        if ($base === '') {
            $base = 'product-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $i = 1;
        while (ShopProduct::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function findOwn(int $centerId, int $id): ShopProduct
    {
        return ShopProduct::query()
            ->ownedByCenter($centerId)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * @return \App\Models\Center
     */
    private function requireProductsCenter(Request $request)
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $center = $user->center;
        abort_unless($center && $center->canManageProducts(), 403, 'إدارة المنتجات متاحة فقط لمراكز خدمة الفرع.');

        return $center;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ShopProduct $product, \App\Models\Center $center, bool $includeProfit): array
    {
        $row = [
            'id' => $product->id,
            'center_id' => $product->center_id,
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
        ];

        if ($includeProfit) {
            $breakdown = $product->profitBreakdown(center: $center);
            $row['app_profit'] = $breakdown['app_profit'];
            $row['app_profit_mode'] = $breakdown['app_profit_mode'];
            $row['app_commission'] = $breakdown['app_commission'];
            $row['center_net'] = $breakdown['center_net'];
            $row['commission_source'] = $breakdown['commission_source'];
            $row['has_custom_commission'] = $breakdown['has_custom_commission'];
            $row['center_default_app_profit'] = $center->product_app_profit !== null
                ? (float) $center->product_app_profit
                : null;
            $row['center_default_app_profit_mode'] = CenterServiceCommission::normalizeMode($center->product_app_profit_mode);
        }

        return $row;
    }
}
