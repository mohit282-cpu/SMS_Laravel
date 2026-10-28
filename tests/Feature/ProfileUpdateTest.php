<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use DatabaseTransactions;

    protected function getUser()
    {
        return User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);
    }

    public function test_guest_cannot_update_profile()
    {
        $response = $this->put(route('my_account.update'), [
            'address' => 'Valid Address Street 123',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_profile_successfully()
    {
        $user = $this->getUser();

        $response = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'Kathmandu Valley Road 456',
            'phone' => '9841234567',
            'phone2' => '014234567',
            'email' => $user->email, // keeping own email
            'locale' => 'ne',
        ]);

        $response->assertSessionHas('flash_success');
        $this->assertEquals('ne', session('locale'));

        $user->refresh();
        $this->assertEquals('Kathmandu Valley Road 456', $user->address);
        $this->assertEquals('9841234567', $user->phone);
    }

    public function test_protected_account_attributes_cannot_be_mass_assigned()
    {
        $user = $this->getUser();
        $originalType = $user->user_type;
        $originalCode = $user->code;
        $originalPassword = $user->password;

        $response = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'Safe Address Update 789',
            'user_type' => 'student', // Attempt privilege escalation or change
            'code' => 'HACKEDCODE',
            'password' => 'new_plain_pass',
        ]);

        $response->assertSessionHas('flash_success');

        $user->refresh();
        $this->assertEquals($originalType, $user->user_type);
        $this->assertEquals($originalCode, $user->code);
        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_photo_upload_saves_safely()
    {
        Storage::fake('public');
        $user = $this->getUser();

        // 1x1 pixel transparent GIF
        $gifContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $file = UploadedFile::fake()->createWithContent('avatar.gif', $gifContent);

        $response = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'Photo Test Address 123',
            'photo' => $file,
        ]);

        $response->assertSessionHas('flash_success');
        $user->refresh();
        $this->assertNotNull($user->getRawOriginal('photo'));
        $this->assertStringContainsString('photo.gif', $user->getRawOriginal('photo'));
        Storage::disk('public')->assertExists(Qs::getUploadPath($user->user_type) . $user->code . '/photo.gif');
    }

    public function test_invalid_input_is_rejected()
    {
        $user = $this->getUser();

        // Short address (< 6 chars)
        $response = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'abc',
        ]);
        $response->assertSessionHasErrors(['address']);

        // Invalid locale
        $responseLocale = $this->actingAs($user)->put(route('my_account.update'), [
            'address' => 'Valid Address Street 123',
            'locale' => 'fr', // not ne or en
        ]);
        $responseLocale->assertSessionHasErrors(['locale']);
    }
}
