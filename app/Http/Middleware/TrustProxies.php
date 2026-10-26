<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Configure specific proxy IPs/CIDRs via TRUSTED_PROXIES in production.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;

    /**
     * Create a new trusted proxies middleware instance.
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        parent::__construct($config);

        $trusted = env('TRUSTED_PROXIES');
        if ($trusted === '*' || $trusted === '**') {
            $this->proxies = $trusted;
        } elseif (!empty($trusted)) {
            $this->proxies = array_map('trim', explode(',', $trusted));
        } else {
            $this->proxies = null;
        }
    }
}
