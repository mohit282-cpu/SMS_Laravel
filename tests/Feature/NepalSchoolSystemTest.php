<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\FinanceLedger;
use App\Models\FiscalYear;
use App\Models\GradingScale;
use App\Models\MyClass;
use App\Models\NepalDistrict;
use App\Models\NepalEducationLevel;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Models\SchoolProfile;
use App\Models\Section;
use App\Models\SeeCandidate;
use App\Models\StudentCertificate;
use App\Models\StudentRecord;
use App\Models\StudentTransport;
use App\Models\TransportRoute;
use App\Services\IemisReportService;
use App\Services\NepalGradingService;
use App\User;
use Database\Seeders\NepalGeographySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NepalSchoolSystemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_nepal_geography_seeder_populates_7_provinces_and_77_districts()
    {
        $this->seed(NepalGeographySeeder::class);

        $this->assertEquals(7, NepalProvince::count());
        $this->assertEquals(77, NepalDistrict::count());

        $bagmati = NepalProvince::where('code', 'P3')->firstOrFail();
        $this->assertEquals('बागमती प्रदेश', $bagmati->name_ne);

        $kathmandu = NepalDistrict::where('name', 'Kathmandu')->firstOrFail();
        $this->assertEquals('काठमाडौँ', $kathmandu->name_ne);

        $metro = NepalLocalLevel::create([
            'district_id' => $kathmandu->id,
            'name' => 'Kathmandu Metropolitan City',
            'name_ne' => 'काठमाडौँ महानगरपालिका',
            'level_type' => 'metropolitan',
            'total_wards' => 32,
        ]);

        $profile = SchoolProfile::create([
            'name' => 'SMS Academy Model School',
            'name_ne' => 'एसएमएस एकेडेमी मोडल स्कूल',
            'emis_code' => '270310001',
            'school_type' => 'institutional',
            'boarding_type' => 'both',
            'province_id' => $bagmati->id,
            'district_id' => $kathmandu->id,
            'local_level_id' => $metro->id,
            'ward_no' => 10,
            'pan_number' => '301234567',
            'calendar_preference' => 'both',
            'is_active' => true,
        ]);

        $this->assertEquals('एसएमएस एकेडेमी मोडल स्कूल', SchoolProfile::current()->name_ne);
        $this->assertEquals('301234567', $profile->pan_number);
    }

    public function test_versioned_curriculum_and_configurable_grading_engine()
    {
        $level = NepalEducationLevel::create([
            'code' => 'secondary_' . uniqid(),
            'name' => 'Secondary Education (Grade 9-10)',
            'name_ne' => 'माध्यमिक शिक्षा (कक्षा ९-१०)',
            'grade_range' => '9-10',
        ]);

        $curriculum = Curriculum::create([
            'name' => 'CDC Secondary Curriculum 2082',
            'version' => '2082.1',
            'education_level_id' => $level->id,
            'effective_from_bs' => '2082-01-01',
            'is_active' => true,
        ]);

        CurriculumSubject::create([
            'curriculum_id' => $curriculum->id,
            'subject_code' => 'C.NEP-101',
            'subject_name' => 'Compulsory Nepali',
            'subject_name_ne' => 'अनिवार्य नेपाली',
            'subject_type' => 'compulsory',
            'theory_max' => 75,
            'theory_pass' => 27,
            'practical_max' => 25,
            'practical_pass' => 10,
            'credit_hour' => 5.00,
        ]);

        $this->assertCount(1, $curriculum->subjects);

        // Configure custom academic year grading scale
        GradingScale::create([
            'name' => 'Test Year Custom Scale',
            'authority' => 'CDC',
            'academic_year' => '2082/83 BS',
            'grade_letter' => 'A+',
            'min_percentage' => 92.00,
            'max_percentage' => 100.00,
            'grade_point' => 4.00,
            'remark' => 'Distinction',
            'remark_ne' => 'विशिष्ट',
            'is_passing' => true,
            'is_active' => true,
        ]);

        $grading = app(NepalGradingService::class);
        $customResult = $grading->getGradeAndGpaForConfig(95, '2082/83 BS');
        $this->assertEquals('Distinction', $customResult['remark']);

        // Unconfigured year falls back cleanly to standard CDC scale
        $fallbackResult = $grading->getGradeAndGpaForConfig(91, '2079/80 BS');
        $this->assertEquals('A+', $fallbackResult['grade']);
        $this->assertEquals(4.0, $fallbackResult['gpa']);
    }

    public function test_see_candidate_transport_certificate_fiscal_ledger_and_iemis_summary()
    {
        $class = MyClass::first() ?: MyClass::create(['name' => 'Grade 10', 'class_type_id' => 1]);
        $section = Section::where('my_class_id', $class->id)->first()
            ?: Section::create(['name' => 'A', 'my_class_id' => $class->id, 'active' => 1]);

        $studentUser = User::create([
            'name' => 'Sita Adhikari',
            'email' => 'sita_' . uniqid() . '@sms.local',
            'username' => 'sita_' . uniqid(),
            'user_type' => 'student',
            'gender' => 'Female',
            'code' => strtoupper(Str::random(8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);

        $sr = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'adm_no' => 'ADM-' . uniqid(),
            'session' => '2082/83 BS',
            'year_admitted' => '2082',
            'name_ne' => 'सीता अधिकारी',
            'dob_bs' => '2067-05-14',
            'admission_date_bs' => '2082-01-05',
            'father_name' => 'Ram Adhikari',
            'mother_name' => 'Gita Adhikari',
            'municipality' => 'Lalitpur Metropolitan City',
            'ward_no' => 5,
            'scholarship_category' => 'Merit Scholarship',
            'enrollment_status' => 'active',
        ]);

        $see = SeeCandidate::create([
            'student_record_id' => $sr->id,
            'academic_year' => '2082/83 BS',
            'symbol_number' => '02810045A',
            'school_code' => '270310001',
            'is_eligible' => true,
            'optional_subject_1' => 'Optional Mathematics',
            'optional_subject_2' => 'Computer Science',
            'gpa' => 3.75,
            'overall_grade' => 'A+',
            'result_status' => 'passed',
        ]);
        $this->assertEquals('02810045A', $see->symbol_number);

        $fy = FiscalYear::create([
            'name' => 'FY 2082/83_' . uniqid(),
            'start_date_bs' => '2082-04-01',
            'end_date_bs' => '2083-03-31',
            'is_active' => true,
        ]);

        $ledger = FinanceLedger::create([
            'fiscal_year_id' => $fy->id,
            'entry_date_bs' => '2082-04-15',
            'entry_date_ad' => '2025-07-31',
            'entry_type' => 'income',
            'category' => 'Tuition Fee',
            'account_head' => 'bank',
            'amount' => '15500.50',
            'description' => 'Monthly NPR tuition collection',
        ]);
        $this->assertEquals('15500.50', $ledger->amount);

        $route = TransportRoute::create([
            'name' => 'Patan - Jawalakhel Route',
            'name_ne' => 'पाटन - जावलाखेल रुट',
            'vehicle_number' => 'Ba 2 Kha 4455',
            'monthly_fee' => '2500.00',
        ]);

        $assign = StudentTransport::create([
            'student_record_id' => $sr->id,
            'transport_route_id' => $route->id,
            'pickup_point' => 'Jawalakhel Chowk',
            'academic_year' => '2082/83 BS',
        ]);
        $this->assertEquals('Jawalakhel Chowk', $assign->pickup_point);

        $cert = StudentCertificate::create([
            'student_record_id' => $sr->id,
            'certificate_type' => 'character',
            'certificate_number' => 'CERT-' . uniqid(),
            'verification_code' => Str::random(32),
            'issued_date_bs' => '2082-11-10',
            'issued_date_ad' => '2026-02-22',
        ]);
        $this->assertEquals('character', $cert->certificate_type);

        $iemis = app(IemisReportService::class)->generateSummary('2082/83 BS');
        $this->assertGreaterThanOrEqual(1, $iemis['enrollment']['total_students']);
        $this->assertGreaterThanOrEqual(1, $iemis['enrollment']['scholarship_recipients']);
    }
}

