<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user_with_hashed_password_and_default_categories(): void
    {
        $response = $this->postJson('/api/register', $this->registrationData());

        $response->assertCreated()
            ->assertJsonPath('role', 'user')
            ->assertJsonPath('user.email', 'student@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('test-password-2026', $user->password));
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'type' => 'income']);
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'type' => 'expense']);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_registration_cannot_grant_admin_role(): void
    {
        $response = $this->postJson('/api/register', [
            ...$this->registrationData(),
            'role_id' => 1,
        ]);

        $response->assertCreated()->assertJsonPath('role', 'user');
        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'role_id' => 2]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'student@example.com']);

        $this->postJson('/api/register', $this->registrationData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_registration_is_rejected_without_creating_user(): void
    {
        $this->postJson('/api/register', ['email' => 'invalid', 'password' => 'short'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_valid_credentials_return_token(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'test-password-2026',
        ]);

        $this->postJson('/api/login', ['email' => 'student@example.com', 'password' => 'test-password-2026'])
            ->assertOk()
            ->assertJsonPath('role', 'user')
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_wrong_password_does_not_issue_token(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'test-password-2026',
        ]);

        $this->postJson('/api/login', ['email' => 'student@example.com', 'password' => 'wrong-password'])
            ->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_endpoint_requires_valid_token(): void
    {
        $this->withToken('invalid-token')->getJson('/api/transactions')
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_list_admin_users(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_list_users_without_passwords(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role_id' => 1])->save();
        User::factory()->create();

        $response = $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/admin/users');

        $response->assertOk()->assertJsonCount(2)->assertJsonMissingPath('0.password');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $other = $user->createToken('other');

        $this->withToken($token->plainTextToken)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
        app('auth')->forgetGuards();
        $this->getJson('/api/transactions')->assertUnauthorized();
    }

    private function registrationData(): array
    {
        return [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => 'test-password-2026',
        ];
    }
}
