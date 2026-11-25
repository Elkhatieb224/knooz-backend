<?php

namespace App\Http\Controllers\Center;

use App\Enums\ShopProductCategory;
use App\Http\Controllers\Controller;
use App\Models\ShopProduct;
use App\Support\CenterServiceCommission;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanManageProducts();

        $products = ShopProduct::query()
            ->with('center')
            ->ownedByCenter((int) Tenant::centerId())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('brand', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('center.products.index', [
            'products' => $products,
            'categories' => ShopProductCategory::options(),
        ]);
    }

    public function create(): View
    {
        $this->ensureCanManageProducts();

        return view('center.products.create', [
            'categories' => ShopProductCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageProducts();

        $data = $this->validated($request);
        $data['center_id'] = Tenant::centerId();
        $data['slug'] = $this->uniqueSlug((string) ($data['name_en'] ?? ''), (string) $data['name_ar']);
        $data = $this->applyImageUpload($request, $data);

        ShopProduct::query()->create($data);

        return redirect()
            ->route('center.products.index')
            ->with('success', 'تمت إضافة المنتج.');
    }

    public function edit(ShopProduct $product): View
    {
        $this->ensureCanManageProducts();
        $this->ensureOwnProduct($product);

        $product->loadMissing('center');

        return view('center.products.edit', [
            'product' => $product,
            'categories' => ShopProductCategory::options(),
        ]);
    }

    public function update(Request $request, ShopProduct $product): RedirectResponse
    {
        $this->ensureCanManageProducts();
        $this->ensureOwnProduct($product);

        $data = $this->validated($request, $product);
        unset($data['slug']);
        $data = $this->applyImageUpload($request, $data, $product->image_path);
        $product->update($data);

        return redirect()
            ->route('center.products.index')
            ->with('success', 'تم تحديث المنتج.');
    }

    public function destroy(ShopProduct $product): RedirectResponse
    {
        $this->ensureCanManageProducts();
        $this->ensureOwnProduct($product);

        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()
            ->route('center.products.index')
            ->with('success', 'تم حذف المنتج.');
    }

    public function toggleStatus(ShopProduct $product): RedirectResponse
    {
        $this->ensureCanManageProducts();
        $this->ensureOwnProduct($product);

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with(
            'success',
            $product->is_active ? 'تم تفعيل المنتج.' : 'تم إيقاف المنتج.'
        );
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

        // Commission is admin-owned (center default or per-product override).
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

    private function ensureCanManageProducts(): void
    {
        $center = auth('center')->user()?->center;
        abort_unless($center && $center->canManageProducts(), 403, 'إدارة المنتجات متاحة فقط لمراكز خدمة الفرع.');
    }

    private function ensureOwnProduct(ShopProduct $product): void
    {
        abort_unless((int) $product->center_id === (int) Tenant::centerId(), 404);
    }
}
