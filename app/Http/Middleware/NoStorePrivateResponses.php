<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoStorePrivateResponses
{
    /**
     * Prevent browsers and intermediate proxies from storing private or authenticated responses.
     *
     * Ensures student records, exam marks, financial invoices, attendances, and private account data
     * are never persisted in the browser disk cache or shared proxies.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apply strict anti-caching headers for sensitive/authenticated HTML, JSON, and file downloads/streams
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
