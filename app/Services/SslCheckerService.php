<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;

class SslCheckerService
{
    /**
     * Inspect SSL certificate for a given URL or hostname.
     *
     * @param string $url
     * @param int $timeoutSeconds
     * @return array
     */
    public function checkUrl(string $url, int $timeoutSeconds = 10): array
    {
        $url = trim($url);
        if (empty($url)) {
            return [
                'success' => false,
                'status' => 'unsupported',
                'error' => 'URL is empty.',
            ];
        }

        // Add scheme if missing
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (!$host) {
            return [
                'success' => false,
                'status' => 'unsupported',
                'error' => 'Invalid domain or host in URL.',
            ];
        }

        // SSL is only applicable to HTTPS/TLS
        if ($scheme !== 'https' && $port !== 443) {
            return [
                'success' => false,
                'status' => 'unsupported',
                'error' => 'Target endpoint does not use HTTPS/SSL.',
            ];
        }

        try {
            $streamContext = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $errno = 0;
            $errstr = '';
            $client = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $timeoutSeconds,
                STREAM_CLIENT_CONNECT,
                $streamContext
            );

            if (!$client) {
                return [
                    'success' => false,
                    'status' => 'unsupported',
                    'error' => "Failed to establish SSL handshake: " . ($errstr ?: "Connection timed out or refused (code {$errno})"),
                ];
            }

            $params = stream_context_get_params($client);
            fclose($client);

            if (empty($params['options']['ssl']['peer_certificate'])) {
                return [
                    'success' => false,
                    'status' => 'unsupported',
                    'error' => 'No peer certificate found during SSL handshake.',
                ];
            }

            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            if (!$cert) {
                return [
                    'success' => false,
                    'status' => 'unsupported',
                    'error' => 'Unable to parse X.509 certificate data.',
                ];
            }

            $validFromUnix = $cert['validFrom_time_t'] ?? null;
            $validToUnix = $cert['validTo_time_t'] ?? null;

            $validFrom = $validFromUnix ? Carbon::createFromTimestamp($validFromUnix) : null;
            $validTo = $validToUnix ? Carbon::createFromTimestamp($validToUnix) : null;

            $issuer = $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown Issuer';
            if (is_array($issuer)) {
                $issuer = implode(', ', $issuer);
            }

            $domain = $cert['subject']['CN'] ?? $host;
            if (is_array($domain)) {
                $domain = $domain[0] ?? $host;
            }

            $daysLeft = $validTo ? (int) floor(now()->diffInDays($validTo, false)) : 0;

            $status = match (true) {
                $daysLeft <= 0 => 'expired',
                $daysLeft <= 7 => 'critical',
                $daysLeft <= 30 => 'warning',
                default => 'valid',
            };

            return [
                'success' => true,
                'status' => $status,
                'issuer' => $issuer,
                'domain' => $domain,
                'valid_from' => $validFrom ? $validFrom->toDateTimeString() : null,
                'valid_to' => $validTo ? $validTo->toDateTimeString() : null,
                'days_left' => $daysLeft,
                'error' => null,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status' => 'unsupported',
                'error' => 'SSL inspection error: ' . $e->getMessage(),
            ];
        }
    }
}
