<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PreserveListContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $context = $request->input('_ui_return');
        if ($request->isMethod('GET') || !$request->user() || !$response instanceof RedirectResponse
            || !is_string($context) || strlen($context) > 4096 || !str_starts_with($context, '/')
            || str_starts_with($context, '//') || str_contains($context, '\\') || preg_match('/[\r\n]/', $context)) {
            return $response;
        }
        $saved = parse_url($context);
        $target = parse_url($response->getTargetUrl());
        $reportReturn = ($target['path'] ?? '') === '/report'
            && preg_match('~^/report/(?!create$|export$|search$|global$)[a-z0-9_-]+$~i', $saved['path'] ?? '');
        if (!$saved || !$target || isset($saved['host']) || isset($saved['scheme'])
            || ($target['host'] ?? $request->getHost()) !== $request->getHost()
            || (($saved['path'] ?? '') !== ($target['path'] ?? '') && !$reportReturn)
            || !preg_match('~^/(contracts|employee|salary|attendance|schedules|leave|shift|profile/profileList|reimbursements|daily-reports|report(?:/[a-z0-9_-]+)?|branches|sites|site|categories|spareparts/all|inventory/[^/]+)/?$~', $saved['path'] ?? '')) {
            return $response;
        }
        parse_str($saved['query'] ?? '', $query);
        // Routing, credentials and arbitrary fields never become redirect parameters.
        $query = array_intersect_key($query, array_flip([
            'search', 'page', 'month', 'year', 'site_id', 'branch_id', 'condition', 'status', 'bank', 'bank_name', 'information', 'position', 'is_active', 'banner_month', 'start_date', 'end_date', 'employee_id', 'leave_type_id', 'category',
        ]));
        $query = array_filter($query, fn ($value) => is_scalar($value));
        parse_str($target['query'] ?? '', $explicit);
        $query = array_replace($query, $explicit);
        $base = $reportReturn ? url($saved['path']) : strtok($response->getTargetUrl(), '?');
        $response->setTargetUrl($base.(count($query) ? '?'.http_build_query($query) : ''));
        return $response;
    }
}
