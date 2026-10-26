<?php

namespace Tests\Feature;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\User;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    /**
     * Test that the default locale for new sessions is English ('en').
     */
    public function testDefaultLocaleIsEnglish()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
        $response->assertSee('English');
        $response->assertSee('Login to your account');
    }

    /**
     * Test that user can switch language to Nepali.
     */
    public function testCanSwitchLocaleToNepali()
    {
        $response = $this->get(route('set_locale', 'ne'));

        $response->assertStatus(302);
        $response->assertSessionHas('locale', 'ne');

        $page = $this->withSession(['locale' => 'ne'])->get('/login');
        $page->assertStatus(200);
        $page->assertSee('आफ्नो खातामा लगइन गर्नुहोस्');
    }

    /**
     * Test that user can switch language back to English.
     */
    public function testCanSwitchLocaleBackToEnglish()
    {
        $response = $this->withSession(['locale' => 'ne'])->get(route('set_locale', 'en'));

        $response->assertStatus(302);
        $response->assertSessionHas('locale', 'en');

        $page = $this->withSession(['locale' => 'en'])->get('/login');
        $page->assertStatus(200);
        $page->assertSee('Login to your account');
    }

    /**
     * Test that unsupported locales are safely rejected.
     */
    public function testUnsupportedLocaleIsRejectedSafely()
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('set_locale', 'fr'));

        $response->assertStatus(302);
        // Session locale should remain 'en' and not 'fr'
        $response->assertSessionHas('locale', 'en');
    }

    /**
     * Test translation keys resolve correctly in both locales.
     */
    public function testTranslationKeysResolveInBothLocales()
    {
        App::setLocale('ne');
        $this->assertEquals('ड्यासबोर्ड', __('Dashboard'));
        $this->assertEquals('विद्यार्थीहरू', __('Students'));
        $this->assertEquals('नयाँ रेकर्ड सफलतापूर्वक सिर्जना गरियो', __('msg.store_ok'));

        App::setLocale('en');
        $this->assertEquals('Dashboard', __('Dashboard'));
        $this->assertEquals('Students', __('Students'));
        $this->assertEquals('Record Created Successfully', __('msg.store_ok'));
    }

    /**
     * Test Nepali numeral conversion and NPR currency formatting helper.
     */
    public function testNepaliNumeralAndCurrencyFormatting()
    {
        $this->assertEquals('०१२३४५६७८९', NepaliHelper::toDevanagari('0123456789'));
        $this->assertEquals('0123456789', NepaliHelper::toEnglish('०१२३४५६७८९'));

        $npr = NepaliHelper::formatNpr(5000, 2, true);
        $this->assertStringContainsString('रु', $npr);
        $this->assertStringContainsString('५,०००.००', $npr);

        $nprEn = NepaliHelper::formatNpr(5000, 2, false);
        $this->assertStringContainsString('NPR', $nprEn);
        $this->assertStringContainsString('5,000.00', $nprEn);
    }

    /**
     * Test that authenticated user language preference updates in DB and persists.
     */
    public function testAuthenticatedUserLocalePersistence()
    {
        $user = User::where('user_type', 'super_admin')->first();
        if (!$user) {
            $user = User::factory()->create(['user_type' => 'super_admin']);
        }

        $this->actingAs($user)
            ->get(route('set_locale', 'en'))
            ->assertStatus(302)
            ->assertSessionHas('locale', 'en');

        $user->refresh();
        $this->assertEquals('en', $user->locale);

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');

        // Switch back to Nepali
        $this->actingAs($user)
            ->get(route('set_locale', 'ne'))
            ->assertStatus(302)
            ->assertSessionHas('locale', 'ne');

        $user->refresh();
        $this->assertEquals('ne', $user->locale);

        $responseNe = $this->actingAs($user)
            ->withSession(['locale' => 'ne'])
            ->get(route('dashboard'));

        $responseNe->assertStatus(200);
        $responseNe->assertSee('ड्यासबोर्ड');
    }
}
