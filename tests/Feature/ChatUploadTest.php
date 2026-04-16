<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_upload_creates_attachment()
    {
        Storage::fake('public');

        $user = Account::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '09123456789',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 600, 600)->size(5000);

        $response = $this->actingAs($user)->post('/api/chat/messages', [
            'messages' => 'Here is an image',
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attachments', ['filename' => 'photo.jpg']);
    }

    public function test_multiple_files_exceed_limit_returns_413()
    {
        Storage::fake('public');

        $user = Account::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '09123400000',
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);

        $files = [];
        for ($i = 0; $i < 7; $i++) {
            $files[] = UploadedFile::fake()->create("file{$i}.txt", 100, 'text/plain');
        }

        $response = $this->actingAs($user)->post('/api/chat/messages', array_merge([
            'messages' => 'Many files',
        ], ['files' => $files]));

        $response->assertStatus(413);
    }
}
