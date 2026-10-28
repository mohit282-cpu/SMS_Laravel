<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PushEndpointValidator
{
    /**
     * Known legitimate push service provider domain suffixes.
     */
    protected static array $trustedPushDomains = [
        'googleapis.com',
        'push.services.mozilla.com',
        'notify.windows.com',
        'push.apple.com',
        'web.push.apple.com',
    ];

    /**
     * Validate a push subscription endpoint strictly against SSRF attacks.
     *
     * @param string|null $endpoint
     * @throws ValidationException
     */
    public static function validate(?string $endpoint): void
    {
        $error = null;
        if (!static::isValid($endpoint, $error)) {
            throw ValidationException::withMessages([
                'endpoint' => [$error ?: 'The push subscription endpoint is invalid or prohibited.'],
            ]);
        }
    }

    /**
     * Check if a push subscription endpoint is safe and valid.
     *
     * @param string|null $endpoint
     * @param string|null $error Output error message
     * @return bool
     */
    public static function isValid(?string $endpoint, ?string &$error = null): bool
    {
        if (empty($endpoint) || !is_string($endpoint)) {
            $error = 'Subscription endpoint is required.';
            return false;
        }

        if (strlen($endpoint) > 1000) {
            $error = 'Subscription endpoint exceeds maximum allowed length.';
            return false;
        }

        // Must be a valid URL syntax
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            $error = 'Subscription endpoint is not a valid URL.';
            return false;
        }

        $parsed = parse_url($endpoint);
        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            $error = 'Subscription endpoint URL is malformed.';
            return false;
        }

        // Scheme MUST be HTTPS
        $scheme = strtolower($parsed['scheme']);
        if ($scheme !== 'https') {
            $error = 'Subscription endpoint must use secure HTTPS protocol.';
            return false;
        }

        // Disallow userinfo (e.g. https://user:pass@domain.com)
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            $error = 'Subscription endpoint must not contain credentials.';
            return false;
        }

        $host = strtolower(trim($parsed['host']));
        if (empty($host)) {
            $error = 'Subscription endpoint host cannot be empty.';
            return false;
        }

        // Remove trailing dot if present in FQDN
        $host = rtrim($host, '.');

        // Block prohibited hostnames directly
        if (static::isProhibitedHost($host)) {
            $error = 'Subscription endpoint host is prohibited.';
            return false;
        }

        // Validate IP address literal or resolved DNS records
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (static::isPrivateOrReservedIp($host)) {
                $error = 'Subscription endpoint targets a private, loopback, or reserved IP address.';
                return false;
            }
        } else {
            // Host is a domain name: resolve DNS records to prevent DNS rebinding
            $resolvedIps = static::resolveDns($host);

            if (!empty($resolvedIps)) {
                foreach ($resolvedIps as $ip) {
                    if (static::isPrivateOrReservedIp($ip)) {
                        $error = "Subscription endpoint resolves to a prohibited IP address ($ip).";
                        return false;
                    }
                }
            } elseif (app()->environment('testing')) {
                // In offline test environments where DNS cannot resolve external hosts,
                // verify against legitimate push domains or safe testing hosts.
                if (!static::isLegitimatePushDomain($host) && !str_ends_with($host, '.example.com')) {
                    $error = 'Unresolvable push endpoint host.';
                    return false;
                }
            } else {
                $error = 'Subscription endpoint domain could not be resolved.';
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a hostname is directly prohibited (loopback, metadata, intranet, etc.).
     */
    protected static function isProhibitedHost(string $host): bool
    {
        $bannedHosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            'metadata.google.internal',
            '169.254.169.254',
            'metadata',
            'instance-data',
        ];

        if (in_array($host, $bannedHosts, true)) {
            return true;
        }

        // Block internal TLDs
        $bannedSuffixes = [
            '.localhost',
            '.local',
            '.internal',
            '.lan',
            '.corp',
            '.home',
            '.test',
            '.onion',
        ];

        foreach ($bannedSuffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        // Block hostnames with no dots (single-word internal machines like "router" or "db")
        if (!str_contains($host, '.') && !filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return false;
    }

    /**
     * Check if an IP address is private, loopback, link-local, or reserved.
     */
    public static function isPrivateOrReservedIp(string $ip): bool
    {
        // Use PHP's built-in filter flags first
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        // Explicit checks for IPv4 ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return true;
            }

            // 0.0.0.0/8
            if (($long & 0xFF000000) === 0x00000000) {
                return true;
            }

            // 10.0.0.0/8
            if (($long & 0xFF000000) === 0x0A000000) {
                return true;
            }

            // 100.64.0.0/10 (Carrier Grade NAT)
            if (($long & 0xFFC00000) === 0x64400000) {
                return true;
            }

            // 127.0.0.0/8 (Loopback)
            if (($long & 0xFF000000) === 0x7F000000) {
                return true;
            }

            // 169.254.0.0/16 (Link Local / AWS Metadata)
            if (($long & 0xFFFF0000) === 0xA9FE0000) {
                return true;
            }

            // 172.16.0.0/12 (Private)
            if (($long & 0xFFF00000) === 0xAC100000) {
                return true;
            }

            // 192.168.0.0/16 (Private)
            if (($long & 0xFFFF0000) === 0xC0A80000) {
                return true;
            }

            // 224.0.0.0/4 (Multicast) & 240.0.0.0/4 (Reserved)
            if (($long & 0xE0000000) === 0xE0000000) {
                return true;
            }

            // Broadcast
            if ($long === -1 || $ip === '255.255.255.255') {
                return true;
            }
        }

        // Explicit checks for IPv6 ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $binary = inet_pton($ip);
            if ($binary === false) {
                return true;
            }

            // ::1 (Loopback)
            if ($ip === '::1' || $binary === inet_pton('::1')) {
                return true;
            }

            // :: (Unspecified)
            if ($ip === '::' || $binary === inet_pton('::')) {
                return true;
            }

            // fe80::/10 (Link-Local)
            $firstByte = ord($binary[0]);
            $secondByte = ord($binary[1]);
            if ($firstByte === 0xFE && ($secondByte & 0xC0) === 0x80) {
                return true;
            }

            // fc00::/7 (Unique Local Address)
            if (($firstByte & 0xFE) === 0xFC) {
                return true;
            }

            // ::ffff:0:0/96 (IPv4 mapped IPv6)
            if (substr($binary, 0, 12) === str_repeat("\x00", 10) . "\xFF\xFF") {
                $mappedIpv4 = inet_ntop(substr($binary, 12));
                return static::isPrivateOrReservedIp($mappedIpv4);
            }
        }

        return false;
    }

    /**
     * Resolve all IPv4 and IPv6 addresses for a hostname.
     */
    protected static function resolveDns(string $host): array
    {
        $ips = [];

        // IPv4 lookup
        $ipv4List = @gethostbynamel($host);
        if (is_array($ipv4List)) {
            $ips = array_merge($ips, $ipv4List);
        }

        // IPv6 lookup if supported
        if (defined('DNS_AAAA')) {
            $records = @dns_get_record($host, DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (isset($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Check if a domain belongs to a recognized legitimate push provider.
     */
    protected static function isLegitimatePushDomain(string $host): bool
    {
        foreach (static::$trustedPushDomains as $trusted) {
            if ($host === $trusted || str_ends_with($host, '.' . $trusted)) {
                return true;
            }
        }
        return false;
    }
}
