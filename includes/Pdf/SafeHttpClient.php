<?php

namespace MatrixAddons\DocumentEngine\Pdf;

use MatrixAddons\DocumentEngine\Vendor\Mpdf\Http\ClientInterface;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\PsrHttpMessageShim\Response;
use MatrixAddons\DocumentEngine\Vendor\Psr\Http\Message\RequestInterface;

defined('ABSPATH') || exit;

/**
 * mPDF's HTTP client for everything it still fetches by itself (for example images inside an
 * SVG). Requests go through WordPress' SSRF-safe HTTP API: no private, loopback or link-local
 * addresses, on every redirect. Anything else gets an empty 403 response.
 */
class SafeHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request)
    {
        $url = (string)$request->getUri();
        if (strtoupper($request->getMethod()) !== 'GET' || !preg_match('#^https?://#i', $url) || !wp_http_validate_url($url)
            || !apply_filters('document_engine_pdf_allow_remote_asset', true, $url)) {
            return new Response(403);
        }
        $response = wp_safe_remote_get($url, array('timeout' => 8, 'redirection' => 3, 'limit_response_size' => 5 * MB_IN_BYTES, 'reject_unsafe_urls' => true));
        if (is_wp_error($response)) {
            return new Response(502);
        }
        $type = (string)wp_remote_retrieve_header($response, 'content-type');
        return new Response((int)wp_remote_retrieve_response_code($response), $type !== '' ? array('Content-Type' => $type) : array(), (string)wp_remote_retrieve_body($response));
    }

    /**
     * Container to pass to new Mpdf($config, $container).
     */
    public static function container()
    {
        return new \MatrixAddons\DocumentEngine\Vendor\Mpdf\Container\SimpleContainer(array('httpClient' => new self()));
    }
}
