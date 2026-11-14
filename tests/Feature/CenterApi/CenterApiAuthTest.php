<?php

namespace Tests\Feature\CenterApi;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\PanelApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success(): void
    {
        $center = $this->makeCenter();
        $this->makeCenterUser($center, 'manager@center.test');

        $this->postJson('/api/center/v1/auth/login', [
            'login' => 'manager@center.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'role', 'center']]]);
    }

    public function test_login_fails_with_bad_password(): void
    {
        $center = $this->makeCenter();
        $this->makeCenterUser($center, 'manager@center.test');

        $this->postJson('/api/center/v1/auth/login', [
            'login' => 'manager@center.test',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $center = $this->makeCenter();
        $this->makeCenterUser($center, 'inactive@center.test', UserStatus::Inactive);

        $this->postJson('/api/center/v1/auth/login', [
            'login' => 'inactive@center.test',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_inactive_center_cannot_login(): void
    {
        $center = $this->makeCenter(CenterStatus::Inactive);
        $this->makeCenterUser($center, 'mgr@center.test');

        $this->postJson('/api/center/v1/auth/login', [
            'login' => 'mgr@center.test',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_me_and_logout(): void
    {
        $center = $this->makeCenter();
        $user = $this->makeCenterUser($center, 'me@center.test');
        $token = PanelApiToken::issue($user, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'me@center.test');

        $this->withToken($token)->postJson('/api/center/v1/auth/logout')
            ->assertOk();

        $this->withToken($token)->getJson('/api/center/v1/auth/me')
            ->assertStatus(401);
    }

    protected function makeCenter(CenterStatus $status = CenterStatus::Active, CenterType $type = CenterType::Owned): Center
    {
        return Center::query()->create([
            'name' => 'Center Auth',
            'type' => $type,
            'status' => $status,
            'city' => 'Riyadh',
            'show_revenue' => $type === CenterType::Owned,
        ]);
    }

    protected function makeCenterUser(
        Center $center,
        string $email,
        UserStatus $status = UserStatus::Active,
        CenterUserRole $role = CenterUserRole::Manager,
    ): CenterUser {
        return CenterUser::query()->create([
            'center_id' => $center->id,
            'name' => 'Center User',
            'email' => $email,
            'password' => 'password',
            'status' => $status,
            'role' => $role,
            'theme' => Theme::Light,
        ]);
    }
}
