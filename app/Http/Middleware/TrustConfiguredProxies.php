<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustConfiguredProxies extends TrustProxies
{
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        // Resolve configuration during a request, after the application has booted.
        // Trust only explicit addresses, without host-based wildcard fallbacks.
        $this->setTrustedProxyIpAddressesToSpecificIps($request, config('security.trusted_proxies', []));
    }
}
