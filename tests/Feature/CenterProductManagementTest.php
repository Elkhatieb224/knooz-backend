<?php

namespace Tests\Feature;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\ShopProductCategory;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\PanelApiToken;
use App\Models\ShopProduct;
use App\Support\CenterServiceCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_center_product_inherits_center_default_commission(): void
    {
        [$center, $user] = $this->makeCenterUser(offersCenter: true, productProfit: 10, productMode: 'percent');
        $token = PanelApiToken::issue($user, 'center')['plain'];

        $this->withToken($token)->postJson('/api/center/v1/products', [
            'name_ar' => 'زيت مركز',
            'name_en' => 'Center Oil',
            'category' => ShopProductCategory::Oils->value,
            'brand' => 'Shell',
            'price' => 200,
            'app_profit' => 99,
            'app_profit_mode' => CenterServiceCommission::MODE_FIXED,
            'stock' => 12,
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name_ar', 'زيت مركز')
            ->assertJsonPath('data.center_id', $center->id)
            ->assertJsonPath('data.commission_source', 'center_default')
            ->assertJsonPath('data.has_custom_commission', false)
            ->assertJsonPath('data.app_commission', 20)
            ->assertJsonPath('data.center_net', 180);

        $this->assertDatabaseHas('shop_products', [
            'center_id' => $center->id,
            'name_ar' => 'زيت مركز',
            'app_profit' => null,
        ]);
    }

    public function test_custom_product_commission_overrides_center_default(): void
    {
        [$center] = $this->makeCenterUser(offersCenter: true, productProfit: 10, productMode: 'percent');
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.shop-products.store'), [
                'center_id' => $center->id,
                'name_ar' => 'إطار مخصص',
                'name_en' => 'Custom Tire',
                'category' => ShopProductCategory::Tires->value,
                'price' => 350,
                'use_custom_app_profit' => 1,
                'app_profit' => 40,
                'app_profit_mode' => CenterServiceCommission::MODE_FIXED,
                'stock' => 3,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('name_ar', 'إطار مخصص')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->hasCustomCommission());

        $breakdown = $product->profitBreakdown(center: $center);
        $this->assertSame('custom', $breakdown['commission_source']);
        $this->assertSame(40.0, $breakdown['app_commission']);
        $this->assertSame(310.0, $breakdown['center_net']);
    }

    public function test_admin_can_clear_custom_commission_to_use_center_default(): void
    {
        [$center] = $this->makeCenterUser(offersCenter: true, productProfit: 50, productMode: 'fixed');
        $admin = $this->makeAdmin();

        $product = ShopProduct::query()->create([
            'center_id' => $center->id,
            'name_ar' => 'بطارية',
            'slug' => 'battery-custom',
            'category' => ShopProductCategory::Batteries->value,
            'price' => 500,
            'app_profit' => 80,
            'app_profit_mode' => 'fixed',
            'stock' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.shop-products.update', $product), [
                'center_id' => $center->id,
                'name_ar' => 'بطارية',
                'category' => ShopProductCategory::Batteries->value,
                'price' => 500,
                'use_custom_app_profit' => 0,
                'stock' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.shop-products.show', $product));

        $product->refresh();
        $this->assertNull($product->app_profit);
        $breakdown = $product->profitBreakdown(center: $center->fresh());
        $this->assertSame('center_default', $breakdown['commission_source']);
        $this->assertSame(50.0, $breakdown['app_commission']);
        $this->assertSame(450.0, $breakdown['center_net']);
    }

    public function test_home_only_center_cannot_manage_products(): void
    {
        [, $user] = $this->makeCenterUser(offersCenter: false);
        $token = PanelApiToken::issue($user, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/products')->assertForbidden();
        $this->withToken($token)->postJson('/api/center/v1/products', [
            'name_ar' => 'ممنوع',
            'category' => ShopProductCategory::Parts->value,
            'price' => 10,
            'stock' => 1,
        ])->assertForbidden();
    }

    public function test_web_panel_lists_and_creates_products_without_setting_commission(): void
    {
        [$center, $user] = $this->makeCenterUser(offersCenter: true, productProfit: 25, productMode: 'fixed');

        $this->actingAs($user, 'center')
            ->get(route('center.products.index'))
            ->assertOk()
            ->assertSee('منتجات المركز');

        $this->actingAs($user, 'center')
            ->post(route('center.products.store'), [
                'name_ar' => 'بطارية فرع',
                'category' => ShopProductCategory::Batteries->value,
                'price' => 500,
                'stock' => 4,
                'is_active' => 1,
            ])
            ->assertRedirect(route('center.products.index'));

        $product = ShopProduct::query()->where('name_ar', 'بطارية فرع')->first();
        $this->assertNotNull($product);
        $this->assertSame($center->id, (int) $product->center_id);
        $this->assertNull($product->app_profit);
        $this->assertSame(25.0, $product->profitBreakdown(center: $center)['app_commission']);
    }

    public function test_public_shop_hides_center_products_unless_filtered(): void
    {
        [$center] = $this->makeCenterUser(offersCenter: true);

        ShopProduct::query()->create([
            'center_id' => null,
            'name_ar' => 'منتج عام',
            'slug' => 'platform-product',
            'category' => ShopProductCategory::Oils->value,
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
            'app_profit_mode' => 'fixed',
        ]);

        ShopProduct::query()->create([
            'center_id' => $center->id,
            'name_ar' => 'منتج مركز',
            'slug' => 'center-product',
            'category' => ShopProductCategory::Oils->value,
            'price' => 120,
            'stock' => 1,
            'is_active' => true,
            'app_profit' => null,
            'app_profit_mode' => 'fixed',
        ]);

        $platform = $this->getJson('/api/v1/shop/products')->assertOk()->json('data');
        $names = collect($platform)->pluck('name_ar')->all();
        $this->assertContains('منتج عام', $names);
        $this->assertNotContains('منتج مركز', $names);

        $forCenter = $this->getJson('/api/v1/shop/products?center_id='.$center->id)
            ->assertOk()
            ->json('data');
        $this->assertContains('منتج مركز', collect($forCenter)->pluck('name_ar')->all());
    }

    public function test_auth_me_exposes_products_capability(): void
    {
        [, $user] = $this->makeCenterUser(offersCenter: true);
        $token = PanelApiToken::issue($user, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.center.capabilities.products', true);

        [, $homeOnly] = $this->makeCenterUser(
            offersCenter: false,
            email: 'home-only@center.test',
        );
        $homeToken = PanelApiToken::issue($homeOnly, 'center')['plain'];

        $this->withToken($homeToken)->getJson('/api/center/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.center.capabilities.products', false);
    }

    /**
     * @return array{0: Center, 1: CenterUser}
     */
    private function makeCenterUser(
        bool $offersCenter,
        string $email = 'products@center.test',
        ?float $productProfit = null,
        string $productMode = 'fixed',
    ): array {
        $center = Center::query()->create([
            'name' => 'Products Center',
            'type' => CenterType::Contracted,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'offers_center_service' => $offersCenter,
            'offers_home_service' => ! $offersCenter,
            'offers_emergency_service' => false,
            'show_revenue' => true,
            'product_app_profit' => $productProfit,
            'product_app_profit_mode' => $productMode,
        ]);

        $user = CenterUser::query()->forceCreate([
            'center_id' => $center->id,
            'name' => 'Products Manager',
            'email' => $email,
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => CenterUserRole::Manager,
            'theme' => Theme::Light,
        ]);

        return [$center, $user];
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::query()->forceCreate([
            'name' => 'Shop Admin',
            'email' => 'shop-commission@knooz.test',
            'phone' => '0501888777',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
