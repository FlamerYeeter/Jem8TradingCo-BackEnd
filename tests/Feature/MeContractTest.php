<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Account;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_normalized_department_and_is_admin()
    {
        $user = Account::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'department' => '  Sales  ',
            'role' => 'user',
        ]);

        Sanctum::actingAs($user, [], 'web');

        $resp = $this->getJson('/api/me');

        $resp->assertStatus(200);
        $resp->assertJsonPath('data.department', 'Sales');
        $resp->assertJsonPath('data.is_admin', false);
        $resp->assertJsonPath('data.email', 'jane@example.com');
    }
}
