<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Add security headers to protect against common attacks.
 *
 * This hook adds various security headers to HTTP responses to help protect
 * against XSS, clickjacking, MIME sniffing, and other attacks.
 */
function add_security_headers(): void
{
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Prevent clickjacking attacks. Booking pages may be framed by the origins listed in EMBED_ALLOWED_ORIGINS, in which
    // case X-Frame-Options is dropped and a frame-ancestors directive is returned for the policy below. Every other
    // response keeps X-Frame-Options: SAMEORIGIN.
    $frame_ancestors = apply_frame_embedding_headers();

    // Content Security Policy. The pages use inline scripts and styles, so those stay allowed, but plugins are blocked
    // outright. The themes load their web fonts from Google Fonts, and the Google Analytics and Matomo integrations load
    // their scripts and send their tracking requests to external hosts (Matomo to any host the admin configures), so
    // scripts, images and connections may come from any HTTPS origin.
    $csp_directives = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https:",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "img-src 'self' data: https:",
        "font-src 'self' data: https://fonts.gstatic.com",
        "connect-src 'self' https:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ];

    if ($frame_ancestors !== null) {
        $csp_directives[] = $frame_ancestors;
    }

    header('Content-Security-Policy: ' . implode('; ', $csp_directives));

    // Referrer policy for privacy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy - restrict sensitive features
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // If the app is served over HTTPS, add HSTS header
    if (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        // Strict Transport Security - enforce HTTPS for 1 year
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
