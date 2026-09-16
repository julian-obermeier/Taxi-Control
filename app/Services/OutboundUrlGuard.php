<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class OutboundUrlGuard
{
    /** @return array{host:string,port:int,ips:array<int,string>} */
    public function validateHttps(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = (int) ($parts['port'] ?? 443);

        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass']) || $port < 1 || $port > 65535) {
            throw ValidationException::withMessages(['endpoint' => 'Webhook-Ziele müssen gültige HTTPS-URLs ohne Zugangsdaten sein.']);
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw ValidationException::withMessages(['endpoint' => 'Lokale oder interne Webhook-Ziele sind nicht erlaubt.']);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);
        if ($ips === []) {
            throw ValidationException::withMessages(['endpoint' => 'Der Webhook-Hostname konnte nicht aufgelöst werden.']);
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw ValidationException::withMessages(['endpoint' => 'Webhook-Ziele in privaten oder reservierten Netzen sind nicht erlaubt.']);
            }
        }

        return ['host' => $host, 'port' => $port, 'ips' => array_values(array_unique($ips))];
    }

    /** @return array<int,string> */
    private function resolve(string $host): array
    {
        if (! function_exists('dns_get_record')) {
            $ipv4 = gethostbynamel($host) ?: [];
            return array_values(array_filter($ipv4, fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false));
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $ips = [];
        foreach ($records as $record) {
            if (! empty($record['ip'])) $ips[] = $record['ip'];
            if (! empty($record['ipv6'])) $ips[] = $record['ipv6'];
        }
        return $ips;
    }
}
