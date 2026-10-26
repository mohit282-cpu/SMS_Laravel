<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Setting;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class SchoolBrandingTest extends TestCase
{
    protected function getAdmin()
    {
        return User::where('user_type', 'admin')->first()
            ?? User::where('user_type', 'super_admin')->first();
    }

    /**
     * Test Qs helpers return correct updated school branding.
     */
    public function test_qs_helpers_return_updated_branding()
    {
        $this->assertEquals('School Management System Academy', Qs::getSystemName());
        $this->assertEquals('SMS Academy', Qs::getSystemTitle());
        $this->assertEquals('SMS Academy', Qs::getAppCode());
    }

    /**
     * Test login page displays new school branding and no old branding references.
     */
    public function test_login_page_displays_updated_school_branding()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $response->assertSee('School Management System Academy');
        $response->assertSee('SMS Academy');
        $response->assertDontSee('CJ INSPIRED ACADEMY');
        $response->assertDontSee('CJ Inspired');
    }

    /**
     * Test authenticated dashboard header displays updated school branding.
     */
    public function test_dashboard_displays_updated_school_branding()
    {
        $admin = $this->getAdmin();
        $this->assertNotNull($admin, 'Admin user must exist');

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('School Management System Academy');
        $response->assertDontSee('CJ INSPIRED ACADEMY');
    }

    /**
     * Test PWA manifest endpoint returns updated school name and short name.
     */
    public function test_pwa_manifest_returns_updated_branding()
    {
        $response = $this->get('/manifest.json');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('School Management System Academy', $data['name']);
        $this->assertEquals('SMS Academy', $data['short_name']);

        $this->assertNotEquals('CJ Inspired Academy', $data['name']);
        $this->assertNotEquals('SMS Nepal', $data['short_name']);
    }

    /**
     * Test static manifest.json file has updated school branding.
     */
    public function test_static_manifest_file_has_updated_branding()
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        $this->assertEquals('School Management System Academy', $data['name']);
        $this->assertEquals('SMS Academy', $data['short_name']);
        $this->assertStringNotContainsString('CJ Inspired', $content);
    }

    /**
     * Test branded offline fallback page uses updated school branding.
     */
    public function test_offline_page_displays_updated_branding()
    {
        $response = $this->get(route('pwa.offline'));
        $response->assertStatus(200);

        $response->assertSee('School Management System Academy');
        $response->assertDontSee('CJ INSPIRED ACADEMY');
        $response->assertDontSee('CJ Inspired');
    }

    /**
     * Test settings page displays current school branding in inputs.
     */
    public function test_settings_page_displays_updated_branding()
    {
        $sa = User::where('user_type', 'super_admin')->first();
        $this->assertNotNull($sa, 'Super Admin must exist');

        $response = $this->actingAs($sa)->get(route('settings'));
        $response->assertStatus(200);

        $response->assertSee('School Management System Academy');
        $response->assertSee('SMS Academy');
        $response->assertDontSee('CJ INSPIRED ACADEMY');
    }

    /**
     * Test maskable icon exists and is referenced as favicon.
     */
    public function test_favicon_links_and_files_exist()
    {
        $maskableIconPath = public_path('assets/images/icons/icon-maskable-512x512.png');
        $this->assertFileExists($maskableIconPath);
        $this->assertGreaterThan(0, filesize($maskableIconPath));

        $faviconPngPath = public_path('global_assets/images/favicon.png');
        $this->assertFileExists($faviconPngPath);
        $this->assertGreaterThan(0, filesize($faviconPngPath));

        $faviconIcoPath = public_path('favicon.ico');
        $this->assertFileExists($faviconIcoPath);
        $this->assertGreaterThan(0, filesize($faviconIcoPath));

        // Test login page includes maskable favicon link
        $resLogin = $this->get('/login');
        $resLogin->assertStatus(200);
        $resLogin->assertSee('assets/images/icons/icon-maskable-512x512.png');

        // Test dashboard page includes maskable favicon link
        $admin = $this->getAdmin();
        $resDash = $this->actingAs($admin)->get(route('dashboard'));
        $resDash->assertStatus(200);
        $resDash->assertSee('assets/images/icons/icon-maskable-512x512.png');
    }
}
