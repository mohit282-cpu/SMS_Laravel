<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDownloadCacheHeadersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_study_material_download_has_no_store_and_private_cache_headers()
    {
        Storage::fake('local');

        $admin = User::where('user_type', 'super_admin')->first();
        $class = MyClass::first();
        $section = Section::where('my_class_id', $class->id)->first();
        $subject = Subject::first();

        // Put a fake file on local storage
        $storedPath = 'study_materials/test_doc.pdf';
        Storage::disk('local')->put($storedPath, '%PDF-1.4 test binary stream');

        $material = StudyMaterial::create([
            'title' => 'Cache Test Document',
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $admin->id,
            'file_path' => $storedPath,
            'file_name' => 'test_doc.pdf',
            'file_size' => 25,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('study_materials.download', $material->id));

        $response->assertStatus(200);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));

        // Verify content disposition header exists and preserves filename
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('test_doc.pdf', $disposition);
    }

    public function test_binary_file_response_preserves_no_store_cache_headers()
    {
        $admin = User::where('user_type', 'super_admin')->first();

        $tempPath = tempnam(sys_get_temp_dir(), 'sms_test_');
        file_put_contents($tempPath, 'sensitive download content');

        \Illuminate\Support\Facades\Route::middleware(['auth', 'no_store'])->get('/test-binary-download', function () use ($tempPath) {
            return response()->download($tempPath, 'sensitive.txt');
        });

        $response = $this->actingAs($admin)->get('/test-binary-download');

        $response->assertStatus(200);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));

        @unlink($tempPath);
    }
}
