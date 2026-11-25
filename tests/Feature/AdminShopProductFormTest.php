<?php

namespace Tests\Feature;

use App\Enums\ShopProductCategory;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShopProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_lists_all_design_categories_and_hides_manual_slug(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.shop-products.create'))
            ->assertOk();

        foreach (ShopProductCategory::options() as $value => $label) {
            $response->assertSee('value="'.$value.'"', false);
            $response->assertSee($label);
        }

        $response->assertDontSee('name="slug"', false);
        $response->assertSee('بيانات إضافية');
        $response->assertSee('meta_notes_editor', false);
        $response->assertDontSee('JSON اختياري');
    }

    public function test_store_auto_generates_slug_and_saves_rich_notes(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.shop-products.store'), [
                'name_ar' => 'بطارية اختبار',
                'name_en' => 'Test Battery Pack',
                'category' => ShopProductCategory::Batteries->value,
                'brand' => 'Varta',
                'size' => '70Ah',
                'price' => 450,
                'stock' => 5,
                'is_active' => 1,
                'meta_notes' => '<p><strong>ضمان سنتين</strong></p><ul><li>تركيب مجاني</li></ul>',
                'slug' => 'hacked-slug',
            ])
            ->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->first();
        $this->assertNotNull($product);
        $this->assertSame(ShopProductCategory::Batteries->value, $product->category);
        $this->assertSame('test-battery-pack', $product->slug);
        $this->assertNotSame('hacked-slug', $product->slug);
        $this->assertStringContainsString('ضمان سنتين', (string) data_get($product->meta, 'notes'));
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::query()->forceCreate([
            'name' => 'متجر أدمن',
            'email' => 'shop-admin@knooz.test',
            'phone' => '0501999888',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
