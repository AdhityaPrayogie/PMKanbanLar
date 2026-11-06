<?php

namespace Tests\Feature;

use Tests\TestCase;

// Guest-path contract with the Next.js app; none of these touch the DB
class AccessTest extends TestCase
{
    public function test_pages_redirect_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/helpdesk')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_api_guest_gets_401_error_json(): void
    {
        $this->getJson('/api/board')->assertStatus(401)->assertExactJson(['error' => 'Unauthorized']);
        $this->withUnencryptedCookie('auth_token', 'x.y.z')->get('/api/helpdesk')->assertStatus(401)->assertExactJson(['error' => 'Unauthorized']);
        $this->get('/api/helpdesk/master/kategori')->assertStatus(401)->assertExactJson(['error' => 'Unauthorized']);
        $this->postJson('/api/broadcasting/auth', ['channel_name' => 'private-user.x'])->assertStatus(401)->assertExactJson(['error' => 'Unauthorized']);
    }

    public function test_admin_guest_gets_403_and_unknown_master_type_404(): void
    {
        $this->get('/api/admin/users')->assertStatus(403)->assertExactJson(['error' => 'Akses ditolak: Hanya untuk Administrator']);
        $this->get('/api/helpdesk/master/xyz')->assertStatus(404)->assertExactJson(['error' => 'Tipe tidak dikenal']);
    }
}
