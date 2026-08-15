<?php

namespace App\Security;

use Closure;

final readonly class OutboundRequestGuard
{
    /** @var Closure(string): list<string> */
    private Closure $resolver;

    /**
     * @param  array<string, array{scheme: string, host: string, port: int, path: string}>  $targets
     * @param  null|Closure(string): list<string>  $resolver
     */
    public function __construct(
        private bool $enabled,
        private array $targets,
        ?Closure $resolver = null,
    ) {
        $this->resolver = $resolver ?? static function (string $host): array {
            $records = dns_get_record($host, DNS_A | DNS_AAAA);

            if (! is_array($records)) {
                return [];
            }

            $addresses = [];
            foreach ($records as $record) {
                $address = $record['ip'] ?? $record['ipv6'] ?? null;
                if (is_string($address)) {
                    $addresses[] = $address;
                }
            }

            return array_values(array_unique($addresses));
        };
    }

    public function assertAllowed(string $operation, string $url): void
    {
        $target = $this->targets[$operation] ?? null;
        $parts = parse_url($url);

        if (! $this->enabled || ! is_array($target) || ! is_array($parts)) {
            throw new OutboundRequestDenied('Outbound network access is disabled.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 0));
        $path = (string) ($parts['path'] ?? '/');

        if ($scheme !== $target['scheme'] || $host !== $target['host']
            || $port !== $target['port'] || $path !== $target['path']
            || isset($parts['query']) || isset($parts['fragment'])
            || isset($parts['user']) || isset($parts['pass'])
        ) {
            throw new OutboundRequestDenied('The outbound destination is not allowlisted.');
        }

        $addresses = ($this->resolver)($host);
        if ($addresses === []) {
            throw new OutboundRequestDenied('The outbound destination did not resolve.');
        }

        foreach ($addresses as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                throw new OutboundRequestDenied('The outbound destination resolved to a non-public address.');
            }
        }
    }
}
