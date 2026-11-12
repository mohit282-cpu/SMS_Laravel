<?php

namespace App\Console\Commands;

use App\Models\NepalDistrict;
use App\Models\NepalLocalLevel;
use App\Models\NepalProvince;
use App\Services\NepalLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportNepalLocationsCommand extends Command
{
    protected $signature = 'nepal:import-locations
                            {file : Path to official Nepal geography JSON dataset}
                            {--source=Government of Nepal Official Dataset : Attribution data source}
                            {--strict-official-counts : Require exact 7 provinces, 77 districts, and 753 local governments (6/11/276/460)}';

    protected $description = 'Import and validate Nepal provinces, districts, and local governments from a verified JSON dataset';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!File::exists($file)) {
            $this->error("Dataset file not found: {$file}");
            return 1;
        }

        $payload = json_decode(File::get($file), true);
        if (!is_array($payload) || empty($payload['provinces'])) {
            $this->error('Invalid JSON structure: expected top-level "provinces" array.');
            return 1;
        }

        $source = (string) $this->option('source');
        $strict = (bool) $this->option('strict-official-counts');

        $counts = [
            'provinces' => 0,
            'districts' => 0,
            'local_levels' => 0,
            'metropolitan' => 0,
            'sub_metropolitan' => 0,
            'municipality' => 0,
            'rural_municipality' => 0,
        ];

        foreach ($payload['provinces'] as $p) {
            $counts['provinces']++;
            foreach (($p['districts'] ?? []) as $d) {
                $counts['districts']++;
                foreach (($d['local_levels'] ?? []) as $l) {
                    $counts['local_levels']++;
                    $type = $l['level_type'] ?? '';
                    if (isset($counts[$type])) {
                        $counts[$type]++;
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
                $this->error('Strict official count validation failed: dataset does not match 7 provinces, 77 districts, and 753 local governments (6/11/276/460).');
                return 1;
            }
        }

        DB::transaction(function () use ($payload, $source) {
            foreach ($payload['provinces'] as $p) {
                $province = NepalProvince::updateOrCreate(
                    ['code' => $p['code']],
                    [
                        'name' => $p['name'],
                        'name_ne' => $p['name_ne'] ?? $p['name'],
                        'is_active' => true,
                        'data_source' => $source,
                    ]
                );

                foreach (($p['districts'] ?? []) as $d) {
                    $district = NepalDistrict::updateOrCreate(
                        ['province_id' => $province->id, 'name' => $d['name']],
                        [
                            'name_ne' => $d['name_ne'] ?? $d['name'],
                            'code' => $d['code'] ?? null,
                            'is_active' => true,
                            'data_source' => $source,
                        ]
                    );

                    foreach (($d['local_levels'] ?? []) as $l) {
                        NepalLocalLevel::updateOrCreate(
                            ['district_id' => $district->id, 'name' => $l['name']],
                            [
                                'name_ne' => $l['name_ne'] ?? $l['name'],
                                'level_type' => $l['level_type'],
                                'total_wards' => (int) ($l['total_wards'] ?? 1),
                                'code' => $l['code'] ?? null,
                                'is_active' => true,
                                'data_source' => $source,
                            ]
                        );
                    }
                }
            }
        });

        $this->info("Imported {$counts['provinces']} provinces, {$counts['districts']} districts, {$counts['local_levels']} local governments.");
        return 0;
    }
}
