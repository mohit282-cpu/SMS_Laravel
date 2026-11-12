<?php

namespace App\Console\Commands;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Services\NepalLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportNepalLocationsCommand extends Command
{
    protected $signature = 'nepal:import-locations
                            {file : Path to official Nepal geography JSON dataset}
                            {--source=Government of Nepal, MoFAGA & Department of Postal Services : Attribution data source}
                            {--strict-official-counts : Require exact 7 provinces, 77 districts, 753 local governments (6/11/276/460), and 6,743 verified wards}
                            {--dry-run : Validate dataset structure, hierarchy, and ward counts without writing to the database}';

    protected $description = 'Import and validate Nepal provinces, districts, and local governments from a verified JSON dataset';

    protected const ALLOWED_TYPES = [
        'metropolitan',
        'sub_metropolitan',
        'municipality',
        'rural_municipality',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!File::exists($file)) {
            $this->error("Dataset file not found: {$file}");
            return 1;
        }

        $payload = json_decode(File::get($file), true);
        if (!is_array($payload) || empty($payload['provinces']) || !is_array($payload['provinces'])) {
            $this->error('Invalid JSON structure: expected top-level "provinces" array.');
            return 1;
        }

        $source = (string) ($payload['metadata']['source'] ?? $this->option('source'));
        $verifiedDate = $payload['metadata']['verified_date'] ?? null;
        $strict = (bool) $this->option('strict-official-counts');
        $dryRun = (bool) $this->option('dry-run');

        $counts = [
            'provinces' => 0,
            'districts' => 0,
            'local_levels' => 0,
            'total_wards' => 0,
            'metropolitan' => 0,
            'sub_metropolitan' => 0,
            'municipality' => 0,
            'rural_municipality' => 0,
        ];

        $errors = [];
        $seenProvinceCodes = [];
        $seenProvinceNames = [];
        $seenDistrictNames = [];
        $seenDistrictCodes = [];
        $seenLocalCodes = [];

        foreach ($payload['provinces'] as $pi => $p) {
            $pCode = trim((string) ($p['code'] ?? ''));
            $pName = trim((string) ($p['name'] ?? ''));
            $pNameNe = trim((string) ($p['name_ne'] ?? ''));

            if ($pCode === '' || $pName === '' || $pNameNe === '') {
                $errors[] = "Province index {$pi}: missing required code, name, or name_ne.";
                continue;
            }
            if (isset($seenProvinceCodes[$pCode])) {
                $errors[] = "Duplicate province code '{$pCode}'.";
            }
            if (isset($seenProvinceNames[strtolower($pName)])) {
                $errors[] = "Duplicate province name '{$pName}'.";
            }
            $seenProvinceCodes[$pCode] = true;
            $seenProvinceNames[strtolower($pName)] = true;
            $counts['provinces']++;

            if (!isset($p['districts']) || !is_array($p['districts'])) {
                $errors[] = "Province '{$pName}': missing districts array.";
                continue;
            }

            foreach ($p['districts'] as $di => $d) {
                $dName = trim((string) ($d['name'] ?? ''));
                $dNameNe = trim((string) ($d['name_ne'] ?? ''));
                $dCode = isset($d['code']) ? trim((string) $d['code']) : null;

                if ($dName === '' || $dNameNe === '') {
                    $errors[] = "Province '{$pName}' district index {$di}: missing name or name_ne.";
                    continue;
                }
                if (isset($seenDistrictNames[strtolower($dName)])) {
                    $errors[] = "Duplicate district '{$dName}'.";
                }
                if ($dCode !== null && $dCode !== '' && isset($seenDistrictCodes[$dCode])) {
                    $errors[] = "Duplicate district code '{$dCode}'.";
                }
                $seenDistrictNames[strtolower($dName)] = true;
                if ($dCode !== null && $dCode !== '') {
                    $seenDistrictCodes[$dCode] = true;
                }
                $counts['districts']++;

                $seenLocalInDistrict = [];
                foreach (($d['local_levels'] ?? []) as $li => $l) {
                    $lName = trim((string) ($l['name'] ?? ''));
                    $lNameNe = trim((string) ($l['name_ne'] ?? ''));
                    $lType = trim((string) ($l['level_type'] ?? ''));
                    $lCode = isset($l['code']) ? trim((string) $l['code']) : null;
                    $wards = $l['total_wards'] ?? null;

                    if ($lName === '' || $lNameNe === '') {
                        $errors[] = "District '{$dName}' local level index {$li}: missing name or name_ne.";
                        continue;
                    }
                    if (!in_array($lType, self::ALLOWED_TYPES, true)) {
                        $errors[] = "Local level '{$lName}' ({$dName}): invalid level_type '{$lType}'.";
                    }
                    if (!is_numeric($wards) || (int) $wards < 5 || (int) $wards > 35) {
                        $errors[] = "Local level '{$lName}' ({$dName}): invalid total_wards '{$wards}' (expected 5–35).";
                    }
                    $localKey = strtolower($lName);
                    if (isset($seenLocalInDistrict[$localKey])) {
                        $errors[] = "Duplicate local level '{$lName}' in district '{$dName}'.";
                    }
                    if ($lCode !== null && $lCode !== '' && isset($seenLocalCodes[$lCode])) {
                        $errors[] = "Duplicate local level code '{$lCode}'.";
                    }

                    $seenLocalInDistrict[$localKey] = true;
                    if ($lCode !== null && $lCode !== '') {
                        $seenLocalCodes[$lCode] = true;
                    }

                    $counts['local_levels']++;
                    $counts['total_wards'] += (int) $wards;
                    if (isset($counts[$lType])) {
                        $counts[$lType]++;
                    }
                }
            }
        }

        if ($strict) {
            if (
                $counts['provinces'] !== NepalLocationService::OFFICIAL_TOTAL_PROVINCES ||
                $counts['districts'] !== NepalLocationService::OFFICIAL_TOTAL_DISTRICTS ||
                $counts['local_levels'] !== NepalLocationService::OFFICIAL_TOTAL_LOCAL_LEVELS ||
                $counts['metropolitan'] !== 6 ||
                $counts['sub_metropolitan'] !== 11 ||
                $counts['municipality'] !== 276 ||
                $counts['rural_municipality'] !== 460
            ) {
                $errors[] = 'Strict official count validation failed: expected 7 provinces, 77 districts, and 753 local governments (6/11/276/460).';
            }

            if ($counts['total_wards'] !== NepalLocationService::OFFICIAL_TOTAL_WARDS) {
                $errors[] = "Strict ward count validation failed: got {$counts['total_wards']}, expected " . NepalLocationService::OFFICIAL_TOTAL_WARDS . '.';
            }
        }

        if (!empty($errors)) {
            $this->error('Import validation failed with ' . count($errors) . ' error(s):');
            foreach (array_slice($errors, 0, 20) as $err) {
                $this->error(' - ' . $err);
            }
            return 1;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Validated {$counts['provinces']} provinces, {$counts['districts']} districts, {$counts['local_levels']} local governments, {$counts['total_wards']} wards. No database writes performed.");
            return 0;
        }

        try {
            DB::transaction(function () use ($payload, $source, $verifiedDate, $strict) {
                foreach ($payload['provinces'] as $p) {
                    $province = NepalProvince::updateOrCreate(
                        ['code' => $p['code']],
                        [
                            'country_code' => 'NP',
                            'name' => trim($p['name']),
                            'name_ne' => trim($p['name_ne'] ?? $p['name']),
                            'is_active' => true,
                            'effective_date' => $verifiedDate,
                            'data_source' => $source,
                        ]
                    );

                    foreach (($p['districts'] ?? []) as $d) {
                        $district = NepalDistrict::updateOrCreate(
                            ['province_id' => $province->id, 'name' => trim($d['name'])],
                            [
                                'name_ne' => trim($d['name_ne'] ?? $d['name']),
                                'code' => $d['code'] ?? null,
                                'is_active' => true,
                                'effective_date' => $verifiedDate,
                                'data_source' => $source,
                            ]
                        );

                        foreach (($d['local_levels'] ?? []) as $l) {
                            NepalLocalLevel::updateOrCreate(
                                ['district_id' => $district->id, 'name' => trim($l['name'])],
                                [
                                    'name_ne' => trim($l['name_ne'] ?? $l['name']),
                                    'level_type' => $l['level_type'],
                                    'total_wards' => (int) $l['total_wards'],
                                    'code' => $l['code'] ?? null,
                                    'is_active' => true,
                                    'effective_date' => $verifiedDate,
                                    'data_source' => $source,
                                ]
                            );
                        }
                    }
                }

                if ($strict) {
                    $report = app(NepalLocationService::class)->getDatasetCompletenessReport();
                    if (!$report['is_complete_official_dataset']) {
                        throw new \RuntimeException('In-transaction completeness verification failed; rolling back import.');
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('Import failed and was rolled back: ' . $e->getMessage());
            return 1;
        }

        app(NepalLocationService::class)->clearCache();

        $this->info("Imported {$counts['provinces']} provinces, {$counts['districts']} districts, {$counts['local_levels']} local governments ({$counts['total_wards']} wards).");
        return 0;
    }
}
