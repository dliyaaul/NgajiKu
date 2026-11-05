<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token_and_profile(): void
    {
        $user = User::factory()->pengajar()->create(['email' => 'ustadz@example.com', 'username' => 'Ustadz Ahmad']);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'ustadz@example.com',
            'password' => 'password',
            'device_name' => 'Pixel 8',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'pengajar')
            ->assertJsonPath('user.username', 'Ustadz Ahmad')
            ->assertJsonMissingPath('user.password');

        $this->assertSame('Pixel 8', PersonalAccessToken::sole()->name);

        $this->withToken($response->json('token'))
            ->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.email', 'ustadz@example.com');
    }

    public function test_koordinator_lembaga_can_log_in_to_the_mobile_api(): void
    {
        User::factory()->role(UserRole::KoordinatorLembaga)->create(['email' => 'koordinator@example.com']);

        $this->postJson(route('api.v1.auth.login'), ['email' => 'koordinator@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'koordinator_lembaga');
    }

    public function test_wrong_credentials_return_validation_error(): void
    {
        User::factory()->create(['email' => 'santri@example.com']);

        $this->postJson(route('api.v1.auth.login'), ['email' => 'santri@example.com', 'password' => 'salah'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_request_without_token_is_unauthorized(): void
    {
        $this->getJson(route('api.v1.auth.me'))
            ->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('hp')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertNoContent();

        $this->assertSame(['tablet'], $user->tokens()->pluck('name')->all());
    }
}
