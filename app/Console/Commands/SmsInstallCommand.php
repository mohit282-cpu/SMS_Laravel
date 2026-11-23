<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\User;
use Database\Seeders\ProductionReferenceSeeder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SmsInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:install
                            {--force : Force installation even if an existing installation is detected}
                            {--database= : Specify target database name to configure and install into}
                            {--connection= : Database connection to use (defaults to default connection)}
                            {--admin-name= : Initial Super Admin full name}
                            {--admin-username= : Initial Super Admin username}
                            {--admin-email= : Initial Super Admin email address}
                            {--admin-phone= : Initial Super Admin mobile phone}
                            {--admin-password= : Initial Super Admin password}
                            {--school-name= : Official school name}
                            {--school-short= : School short acronym}
                            {--school-address= : Official school address}
                            {--school-phone= : Official school contact telephone}
                            {--school-email= : Official school administrative email}
                            {--session= : Initial academic session (e.g. 2083/84 BS)}
                            {--skip-migrations : Skip running database migrations}
                            {--skip-seeds : Skip seeding master reference data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform fresh production installation and clean school initialization for SMS Laravel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line("<info>===============================================================</info>");
        $this->line("<info>  SMS Laravel — Clean Production Installation & Initialization </info>");
        $this->line("<info>===============================================================</info>");

        // 1. Configure and Verify Target Database Connection
        $connectionName = $this->option('connection') ?: config('database.default');
        $targetDb = $this->option('database');

        if ($targetDb) {
            Config::set("database.connections.{$connectionName}.database", $targetDb);
            DB::purge($connectionName);
            DB::reconnect($connectionName);
            $this->info("Target database configured: [{$targetDb}] on connection [{$connectionName}]");
        } else {
            $targetDb = config("database.connections.{$connectionName}.database");
            $this->info("Using default configured database: [{$targetDb}] on connection [{$connectionName}]");
        }

        try {
            DB::connection($connectionName)->getPdo();
            $this->info("Database connection to [{$targetDb}] verified successfully.");
        } catch (Throwable $e) {
            $this->error("Database connection failure: " . $e->getMessage());
            Log::error("sms:install database connection failed: " . $e->getMessage());
            return 1;
        }

        // 2. Prevent Accidental Duplicate Installation
        $isForce = (bool) $this->option('force');
        if (!$isForce && $this->isAlreadyInstalled($connectionName)) {
            $this->error("The application is already installed on database [{$targetDb}].");
            $this->warn("A Super Admin account or prior installation signature was detected.");
            $this->line("If you explicitly intend to re-initialize this database, run with the --force flag.");
            return 1;
        }

        // 3. Collect & Validate Super Admin Credentials
        $adminData = $this->resolveAdminDetails();
        if ($adminData === false) {
            return 1;
        }

        // 4. Collect & Validate School Configuration
        $schoolData = $this->resolveSchoolDetails($adminData['email']);

        // 5. Run Database Migrations
        if (!$this->option('skip-migrations')) {
            $this->info("Executing verified database migrations...");
            try {
                $exitCode = Artisan::call('migrate', [
                    '--database' => $connectionName,
                    '--force' => true,
                ], $this->getOutput());

                if ($exitCode !== 0) {
                    $this->error("Migration execution failed with code [{$exitCode}].");
                    return 1;
                }
                $this->info("All migrations executed successfully.");
            } catch (Throwable $e) {
                $this->error("Migration error: " . $e->getMessage());
                Log::error("sms:install migrations failed: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->warn("Skipping database migrations as requested (--skip-migrations).");
        }

        // 6. Seed Master Reference Data Only
        if (!$this->option('skip-seeds')) {
            $this->info("Seeding master reference data (Nepal geography, blood groups, user types, grades)...");
            try {
                $exitCode = Artisan::call('db:seed', [
                    '--class' => ProductionReferenceSeeder::class,
                    '--database' => $connectionName,
                    '--force' => true,
                ], $this->getOutput());

                if ($exitCode !== 0) {
                    $this->error("Reference data seeding failed with code [{$exitCode}].");
                    return 1;
                }
                $this->info("Master reference data seeded successfully.");
            } catch (Throwable $e) {
                $this->error("Seeding error: " . $e->getMessage());
                Log::error("sms:install seeding failed: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->warn("Skipping reference data seeding as requested (--skip-seeds).");
        }

        // 7. Provision the Initial Super Admin Account
        $this->info("Provisioning initial authorized Super Admin account...");
        try {
            $adminUser = $this->provisionSuperAdmin($connectionName, $adminData);
            $this->info("Super Admin account [{$adminUser->username}] ({$adminUser->email}) created successfully.");
        } catch (Throwable $e) {
            $this->error("Failed to provision Super Admin account: " . $e->getMessage());
            Log::error("sms:install Super Admin creation failed: " . $e->getMessage());
            return 1;
        }

        // 8. Apply Clean School Configuration Settings
        $this->info("Applying clean school identity and regional settings...");
        try {
            $this->applySchoolSettings($connectionName, $schoolData, $adminData['email']);
            $this->info("School configuration settings saved successfully.");
        } catch (Throwable $e) {
            $this->error("Failed to save school settings: " . $e->getMessage());
            Log::error("sms:install apply settings failed: " . $e->getMessage());
            return 1;
        }

        // 9. Storage Directories and Public Assets Verification
        $this->info("Verifying storage directories and symlinks...");
        $this->verifyStorageStructure();

        // 10. Cache Management
        $this->info("Refreshing application cache...");
        try {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
        } catch (Throwable $e) {
            $this->warn("Cache clear note: " . $e->getMessage());
        }

        // 11. Completion Banner & Handover Summary
        $this->line("");
        $this->info("===============================================================");
        $this->info("       FRESH INSTALLATION & INITIALIZATION COMPLETED!          ");
        $this->info("===============================================================");
        $this->line("Target Database : <comment>{$targetDb}</comment>");
        $this->line("Super Admin User: <comment>{$adminData['username']}</comment>");
        $this->line("Super Admin Mail: <comment>{$adminData['email']}</comment>");
        $this->line("School Name     : <comment>{$schoolData['system_name']}</comment>");
        $this->line("Academic Session: <comment>{$schoolData['current_session']}</comment>");
        $this->line("Country/Currency: <comment>Nepal / NPR</comment>");
        $this->line("Timezone        : <comment>Asia/Kathmandu</comment>");
        $this->line("---------------------------------------------------------------");
        $this->line("<info>Status: Production-ready clean installation verified.</info>");
        $this->line("===============================================================");

        Log::info("SMS Laravel fresh installation completed successfully on database [{$targetDb}] by [{$adminData['email']}].");

        return 0;
    }

    /**
     * Determine if an installation already exists in the target database.
     */
    protected function isAlreadyInstalled(string $connection): bool
    {
        try {
            if (!Schema::connection($connection)->hasTable('users')) {
                return false;
            }

            $adminCount = DB::connection($connection)
                ->table('users')
                ->where('user_type', 'super_admin')
                ->count();

            if ($adminCount > 0) {
                return true;
            }

            if (Schema::connection($connection)->hasTable('settings')) {
                $installed = DB::connection($connection)
                    ->table('settings')
                    ->where('type', 'installation_completed')
                    ->where('description', '1')
                    ->exists();

                if ($installed) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Resolve Super Admin details securely from options or interactive prompts.
     *
     * @return array{name: string, username: string, email: string, phone: ?string, password: string}|false
     */
    protected function resolveAdminDetails()
    {
        $canPrompt = $this->input->isInteractive() && !app()->runningUnitTests();

        $name = $this->option('admin-name');
        if (empty($name)) {
            $name = $canPrompt ? $this->ask('Enter Super Admin Full Name') : 'System Administrator';
        }
        $name = trim((string) $name);
        if (empty($name)) {
            $name = 'System Administrator';
        }

        $username = $this->option('admin-username');
        if (empty($username)) {
            $username = $canPrompt ? $this->ask('Enter Super Admin Username', 'admin') : 'admin';
        }
        $username = trim((string) $username);
        if (empty($username) || !preg_match('/^[a-zA-Z0-9_\.\-]+$/', $username)) {
            $this->error("Invalid username. Alphanumeric and underscore characters only.");
            return false;
        }

        $email = $this->option('admin-email');
        if (empty($email)) {
            $email = $canPrompt ? $this->ask('Enter Super Admin Email') : 'admin@school.edu.np';
        }
        $email = trim((string) $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address format [{$email}].");
            return false;
        }

        $phone = $this->option('admin-phone');
        if (empty($phone) && $canPrompt) {
            $phone = $this->ask('Enter Super Admin Mobile Phone (optional)');
        }
        $phone = $phone ? trim((string) $phone) : null;

        $password = $this->option('admin-password');
        if (empty($password)) {
            if ($canPrompt) {
                $password = $this->secret('Enter Super Admin Password (min 8 characters)');
                $confirm = $this->secret('Confirm Super Admin Password');
                if ($password !== $confirm) {
                    $this->error("Passwords do not match.");
                    return false;
                }
            } else {
                $this->error("Admin password must be provided via --admin-password in non-interactive mode.");
                return false;
            }
        }

        if (strlen((string) $password) < 8) {
            $this->error("Admin password must be at least 8 characters long.");
            return false;
        }

        return [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => (string) $password,
        ];
    }

    /**
     * Resolve School details from options or clean defaults.
     *
     * @return array<string, string>
     */
    protected function resolveSchoolDetails(string $defaultEmail): array
    {
        $canPrompt = $this->input->isInteractive() && !app()->runningUnitTests();

        $schoolName = $this->option('school-name');
        if (empty($schoolName)) {
            $schoolName = $canPrompt ? $this->ask('Enter Official School Name', 'Apex Model Secondary School') : 'Apex Model Secondary School';
        }

        $schoolShort = $this->option('school-short');
        if (empty($schoolShort)) {
            $schoolShort = $canPrompt ? $this->ask('Enter School Acronym / Short Name', 'Apex School') : 'Apex School';
        }

        $schoolAddress = $this->option('school-address');
        if (empty($schoolAddress)) {
            $schoolAddress = $canPrompt ? $this->ask('Enter Official School Address', 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal') : 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal';
        }

        $schoolPhone = $this->option('school-phone');
        if (empty($schoolPhone)) {
            $schoolPhone = $canPrompt ? $this->ask('Enter School Telephone Number', '+977-25-560000') : '+977-25-560000';
        }

        $schoolEmail = $this->option('school-email');
        if (empty($schoolEmail)) {
            $schoolEmail = $canPrompt ? $this->ask('Enter School Administrative Email', 'info@apexschool.edu.np') : 'info@apexschool.edu.np';
        }

        $session = $this->option('session');
        if (empty($session)) {
            $session = $canPrompt ? $this->ask('Enter Academic Session (e.g. 2083/84 BS)', '2083/84 BS') : '2083/84 BS';
        }

        return [
            'system_name' => trim((string) $schoolName),
            'system_title' => trim((string) $schoolShort),
            'address' => trim((string) $schoolAddress),
            'phone' => trim((string) $schoolPhone),
            'system_email' => trim((string) $schoolEmail),
            'current_session' => trim((string) $session),
        ];
    }

    /**
     * Provision the Super Admin record.
     */
    protected function provisionSuperAdmin(string $connection, array $adminData): User
    {
        $connectionName = $connection;

        // If force is active, remove prior super admin if username or email matches
        DB::connection($connectionName)->table('users')
            ->where('username', $adminData['username'])
            ->orWhere('email', $adminData['email'])
            ->delete();

        $userId = DB::connection($connectionName)->table('users')->insertGetId([
            'name' => $adminData['name'],
            'email' => $adminData['email'],
            'username' => $adminData['username'],
            'phone' => $adminData['phone'],
            'password' => Hash::make($adminData['password']),
            'user_type' => 'super_admin',
            'code' => strtoupper(Str::random(10)),
            'email_verified_at' => now(),
            'is_active' => true,
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->setConnection($connectionName);
        return $user->newQuery()->findOrFail($userId);
    }

    /**
     * Apply clean settings into the database.
     *
     * @param array<string, string> $schoolData
     */
    protected function applySchoolSettings(string $connection, array $schoolData, string $adminEmail): void
    {
        $settings = [
            'system_name' => $schoolData['system_name'],
            'system_title' => $schoolData['system_title'],
            'address' => $schoolData['address'],
            'phone' => $schoolData['phone'],
            'system_email' => $schoolData['system_email'],
            'current_session' => $schoolData['current_session'],
            'country' => 'Nepal',
            'currency' => 'NPR',
            'timezone' => 'Asia/Kathmandu',
            'calendar_display' => 'both',
            'lock_exam' => '0',
            'admissions_open' => '0',
            'entrance_results_visible' => '0',
            'installation_completed' => '1',
            'installed_at' => now()->toIso8601String(),
            'installed_by' => $adminEmail,
            'installation_version' => '1.0.0',
        ];

        foreach ($settings as $type => $value) {
            $exists = DB::connection($connection)->table('settings')->where('type', $type)->exists();
            if ($exists) {
                DB::connection($connection)->table('settings')->where('type', $type)->update(['description' => $value]);
            } else {
                DB::connection($connection)->table('settings')->insert(['type' => $type, 'description' => $value]);
            }
        }
    }

    /**
     * Ensure storage directories exist and clean out any demo files.
     */
    protected function verifyStorageStructure(): void
    {
        $directories = [
            storage_path('app/public'),
            storage_path('app/public/uploads'),
            storage_path('app/public/uploads/student'),
            storage_path('app/public/uploads/teacher'),
            storage_path('app/public/uploads/study_materials'),
            storage_path('app/backups'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        // Clean out demo student photos in public storage if present
        $demoStudentDir = storage_path('app/public/uploads/student');
        if (File::isDirectory($demoStudentDir)) {
            $subdirs = File::directories($demoStudentDir);
            foreach ($subdirs as $subdir) {
                File::deleteDirectory($subdir);
            }
        }

        // Ensure public storage link exists
        if (!File::exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
            } catch (Throwable $e) {
                // Ignore if symlink failed due to OS privilege
            }
        }
    }
}
