<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShopProductRequest;
use App\Http\Requests\Admin\UpdateShopProductRequest;
use App\Models\ShopProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('shop.manage');

        $products = ShopProduct::query()
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
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.shop-products.index', compact('products'));
    }

    public function create(): View
    {
        $this->authorize('shop.manage');

        return view('admin.shop-products.create');
    }

    public function store(StoreShopProductRequest $request): RedirectResponse
    {
        $this->authorize('shop.manage');

        $data = $this->normalized($request->validated());
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

        return view('admin.shop-products.edit', ['product' => $shopProduct]);
    }

    public function update(UpdateShopProductRequest $request, ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('shop.manage');

        $shopProduct->update($this->normalized($request->validated(), $shopProduct));

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
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $base = Str::slug((string) ($data['name_en'] ?? $data['name_ar'] ?? 'product'), '-');
            if ($base === '') {
                $base = 'product-'.Str::lower(Str::random(6));
            }
            $slug = $base;
            $i = 1;
            while (
                ShopProduct::query()
                    ->where('slug', $slug)
                    ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                    ->exists()
            ) {
                $slug = $base.'-'.$i;
                $i++;
            }
        }

        $data['slug'] = $slug;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['meta'] = $this->decodeMeta($data['meta'] ?? null);

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeMeta(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw)) {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
