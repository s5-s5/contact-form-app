<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者登録・ログイン・ログアウト（Fortify）
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_is_displayed(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_user_can_register_and_is_redirected_to_admin(): void
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['name' => '山田 太郎', 'email' => 'yamada@example.com']);
    }

    public function test_register_shows_errors_when_fields_are_empty(): void
    {
        $response = $this->from('/register')->post('/register', []);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
        $this->assertGuest();
    }

    public function test_register_shows_format_length_and_confirmation_errors(): void
    {
        $this->from('/register')->post('/register', [
            'name' => '山田 太郎',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->from('/register')->post('/register', [
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->assertGuest();
    }

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_user_can_login_and_is_redirected_to_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_shows_errors_when_fields_are_empty(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_logged_in_user_is_redirected_from_login_page_to_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/admin');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
