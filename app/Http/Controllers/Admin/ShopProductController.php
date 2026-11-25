<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShopProductRequest;
use App\Http\Requests\Admin\UpdateShopProductRequest;
use App\Models\Center;
use App\Models\ShopProduct;
use App\Support\CenterServiceCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('shop.manage');

        $products = ShopProduct::query()
            ->with('center:id,name')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->when($request->filled('center_id'), function ($q) use ($request) {
                if ($request->string('center_id') === 'platform') {
                    $q->whereNull('center_id');
                } else {
                    $q->where('center_id', $request->integer('center_id'));
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.shop-products.index', [
            'products' => $products,
            'centers' => Center::query()->orderBy('name')->get(['id', 'name', 'offers_center_service']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('shop.manage');

        return view('admin.shop-products.create', [
            'centers' => Center::query()
                ->where('offers_center_service', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreShopProductRequest $request): RedirectResponse
    {
        $this->authorize('shop.manage');

        $data = $this->normalized($request->validated());
        $data = $this->applyImageUpload($request, $data);
        ShopProduct::query()->create($data);

        return redirect()
            ->route('admin.shop-products.index')
            ->with('success', 'تم إنشاء المنتج بنجاح.');
    }

    public function show(ShopProduct $shopProduct): View
    {
        $this->authorize('shop.manage');

        return view('admin.shop-products.show', ['product' => $shopProduct]);
    }

    public function edit(ShopProduct $shopProduct): View
    {
        $this->authorize('shop.manage');

        return view('admin.shop-products.edit', [
            'product' => $shopProduct,
            'centers' => Center::query()
                ->where('offers_center_service', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateShopProductRequest $request, ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('shop.manage');

        $data = $this->normalized($request->validated(), $shopProduct);
        $data = $this->applyImageUpload($request, $data, $shopProduct->image_path);
        $shopProduct->update($data);

        return redirect()
            ->route('admin.shop-products.show', $shopProduct)
            ->with('success', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('shop.manage');

        $shopProduct->delete();

        return redirect()
            ->route('admin.shop-products.index')
            ->with('success', 'تم حذف المنتج بنجاح.');
    }

    public function toggleStatus(ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('shop.manage');

        $shopProduct->update(['is_active' => ! $shopProduct->is_active]);

        return back()->with(
            'success',
            $shopProduct->is_active ? 'تم تفعيل المنتج.' : 'تم إيقاف المنتج.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data, ?ShopProduct $existing = null): array
    {
        unset($data['image']);

        $notes = trim((string) ($data['meta_notes'] ?? ''));
        unset($data['meta_notes'], $data['slug']);

        // Keep existing slug on update; always generate on create.
        if ($existing?->slug) {
            $data['slug'] = $existing->slug;
        } else {
            $data['slug'] = $this->uniqueSlug(
                (string) ($data['name_en'] ?? ''),
                (string) ($data['name_ar'] ?? ''),
                $existing?->id,
            );
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['center_id'] = ! empty($data['center_id']) ? (int) $data['center_id'] : null;

        $useCustom = (bool) ($data['use_custom_app_profit'] ?? false);
        unset($data['use_custom_app_profit']);
        if ($useCustom) {
            $data['app_profit_mode'] = CenterServiceCommission::normalizeMode($data['app_profit_mode'] ?? null);
            if (($data['app_profit'] ?? null) !== null && $data['app_profit_mode'] === CenterServiceCommission::MODE_PERCENT) {
                $data['app_profit'] = min(100, (float) $data['app_profit']);
            }
        } else {
            $data['app_profit'] = null;
            $data['app_profit_mode'] = CenterServiceCommission::MODE_FIXED;
        }

        $data['meta'] = $this->mergeMetaNotes(
            is_array($existing?->meta) ? $existing->meta : [],
            $notes,
        );

        return $data;
    }

    private function uniqueSlug(string $nameEn, string $nameAr, ?int $ignoreId = null): string
    {
        $base = Str::slug($nameEn !== '' ? $nameEn : $nameAr, '-');
        if ($base === '') {
            $base = 'product-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $i = 1;
        while (
            ShopProduct::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>|null
     */
    private function mergeMetaNotes(array $existing, string $notesHtml): ?array
    {
        $meta = $existing;
        if ($notesHtml === '' || $notesHtml === '<p><br></p>' || $notesHtml === '<p></p>') {
            unset($meta['notes']);
        } else {
            $meta['notes'] = $notesHtml;
        }

        return $meta === [] ? null : $meta;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyImageUpload(Request $request, array $data, ?string $existingPath = null): array
    {
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

}
