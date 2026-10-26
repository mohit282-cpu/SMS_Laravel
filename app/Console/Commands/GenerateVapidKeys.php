<?php

namespace App\Console\Commands;

use Base64Url\Base64Url;
use Illuminate\Console\Command;
use Jose\Component\KeyManagement\JWKFactory;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:vapid
                            {--show : Display the currently configured VAPID public key and subject}
                            {--show-secret : Explicitly display the generated private key in terminal output}
                            {--save : Automatically update the .env file with generated keys}
                            {--force : Overwrite existing VAPID keys in .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and configure VAPID keys for PWA Web Push notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->ensureOpenSslConfig();

        if ($this->option('show')) {
            $publicKey = config('services.pwa.vapid_public_key');
            $privateKey = config('services.pwa.vapid_private_key');
            $subject = config('services.pwa.vapid_subject');

            $this->info('=== Current PWA VAPID Configuration ===');
            $this->line('VAPID Public Key:  ' . ($publicKey ?: '<Not Configured>'));
            $this->line('VAPID Private Key: ' . ($privateKey ? '<Configured>' : '<Not Configured>'));
            $this->line('VAPID Subject:     ' . ($subject ?: '<Not Configured>'));

            if (app()->configurationIsCached()) {
                $this->comment('Note: Application configuration is currently cached.');
            }

            return 0;
        }

        $envPath = base_path('.env');
        $envExists = file_exists($envPath);
        $existingPublicKey = config('services.pwa.vapid_public_key');
        $existingPrivateKey = config('services.pwa.vapid_private_key');

        if ($envExists && !$this->option('force')) {
            $envContent = file_get_contents($envPath);
            $hasEnvPub = preg_match('/^VAPID_PUBLIC_KEY=(.+)$/m', $envContent, $pubMatches);
            $hasEnvPriv = preg_match('/^VAPID_PRIVATE_KEY=(.+)$/m', $envContent, $privMatches);

            if (($existingPublicKey || ($hasEnvPub && !empty(trim($pubMatches[1])))) && ($existingPrivateKey || ($hasEnvPriv && !empty(trim($privMatches[1]))))) {
                if ($this->option('save')) {
                    $this->warn('VAPID keys are already configured in your environment.');
                    $this->comment('Overwriting keys will invalidate existing browser push subscriptions.');
                    $this->comment('Use --force to overwrite existing keys: php artisan pwa:vapid --save --force');
                    return 0;
                }
            }
        }

        try {
            $keys = $this->createKeys();
        } catch (\Exception $e) {
            $this->error('Failed to generate VAPID keys: ' . $e->getMessage());
            return 1;
        }

        $publicKey = $keys['publicKey'];
        $privateKey = $keys['privateKey'];
        $defaultSubject = 'mailto:' . (config('mail.from.address') ?: 'admin@' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'));

        $this->info('=== Generated Web Push VAPID Keypair ===');
        $this->warn('SECURITY NOTICE: The VAPID private key is a sensitive secret. Never commit it to Git or expose it in client-side code.');
        $this->line('<comment>VAPID_PUBLIC_KEY=</comment>' . $publicKey);

        if ($this->option('show-secret')) {
            $this->line('<comment>VAPID_PRIVATE_KEY=</comment>' . $privateKey);
        } else {
            $this->line('<comment>VAPID_PRIVATE_KEY=</comment>[HIDDEN] (Use --show-secret to print secret to terminal)');
        }

        $this->line('<comment>VAPID_SUBJECT=</comment>' . $defaultSubject);
        $this->newLine();

        if ($this->option('save')) {
            if (!$envExists) {
                $this->error('.env file not found. Please create it first.');
                return 1;
            }

            if (!is_writable($envPath)) {
                $this->error('.env file is not writable.');
                return 1;
            }

            $envContent = file_get_contents($envPath);

            $envContent = $this->setEnvValue($envContent, 'VAPID_PUBLIC_KEY', $publicKey);
            $envContent = $this->setEnvValue($envContent, 'VAPID_PRIVATE_KEY', $privateKey);

            if (strpos($envContent, 'VAPID_SUBJECT=') === false) {
                $envContent .= "\nVAPID_SUBJECT=" . $defaultSubject . "\n";
            }

            file_put_contents($envPath, $envContent);

            // Restrict permissions on platforms that support POSIX permissions
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && function_exists('chmod')) {
                @chmod($envPath, 0600);
            }

            if (app()->configurationIsCached()) {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                $this->info('✓ Laravel configuration cache cleared to load new keys.');
            }

            $this->info('✓ VAPID keys securely saved to .env file without echoing private key material.');
        } else {
            $this->comment('To save these keys to your .env file securely, run: php artisan pwa:vapid --save');
        }

        return 0;
    }

    /**
     * Ensure Windows OpenSSL configuration is present
     */
    protected function ensureOpenSslConfig(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && empty(getenv('OPENSSL_CONF'))) {
            $candidates = [
                'Z:/Xampp/php/extras/ssl/openssl.cnf',
                'C:/xampp/php/extras/ssl/openssl.cnf',
                'Z:/Xampp/apache/bin/openssl.cnf',
                'C:/xampp/apache/bin/openssl.cnf',
            ];

            foreach ($candidates as $cnf) {
                if (file_exists($cnf)) {
                    putenv("OPENSSL_CONF={$cnf}");
                    $_ENV['OPENSSL_CONF'] = $cnf;
                    break;
                }
            }
        }
    }

    /**
     * Update or append an environment key in env string
     */
    protected function setEnvValue(string $envContent, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, "{$key}={$value}", $envContent);
        }

        return rtrim($envContent) . "\n{$key}={$value}\n";
    }

    /**
     * Create VAPID keypair with cross-platform fallback
     */
    protected function createKeys(): array
    {
        try {
            return VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            // Fallback for Windows environments where OpenSSL configuration must be specified explicitly
            $cnf = getenv('OPENSSL_CONF');
            if (!$cnf || !file_exists($cnf)) {
                $candidates = [
                    'Z:/Xampp/php/extras/ssl/openssl.cnf',
                    'C:/xampp/php/extras/ssl/openssl.cnf',
                ];
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate)) {
                        $cnf = $candidate;
                        break;
                    }
                }
            }

            $options = [
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ];
            if ($cnf && file_exists($cnf)) {
                $options['config'] = $cnf;
            }

            $res = openssl_pkey_new($options);
            if ($res === false) {
                throw new \RuntimeException('Unable to create OpenSSL EC key: ' . openssl_error_string());
            }

            $exportSuccess = openssl_pkey_export($res, $pem, null, $options);
            if (!$exportSuccess || empty($pem)) {
                throw new \RuntimeException('Unable to export OpenSSL EC key: ' . openssl_error_string());
            }

            $jwk = JWKFactory::createFromKey($pem, null, [
                'kty' => 'EC',
                'crv' => 'P-256',
            ]);

            $binaryPublicKey = hex2bin(Utils::serializePublicKeyFromJWK($jwk));
            $binaryPrivateKey = hex2bin(str_pad(bin2hex(Base64Url::decode($jwk->get('d'))), 2 * 32, '0', STR_PAD_LEFT));

            return [
                'publicKey' => Base64Url::encode($binaryPublicKey),
                'privateKey' => Base64Url::encode($binaryPrivateKey),
            ];
        }
    }
}
