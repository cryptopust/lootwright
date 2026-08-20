<?php

namespace App\Modules\ExternalSources\PoeNinja;

use InvalidArgumentException;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;

/** Configuration-owned endpoint factory. No user input reaches an outbound URL. */
final class PoeNinjaEndpoint
{
    private const BASE_URL = 'https://poe.ninja';

    public static function leagues(): string
    {
        return self::BASE_URL.'/poe1/api/economy/leagues';
    }

    public static function overview(string $league, EconomyCategory $category): string
    {
        if (trim($league) === '' || mb_strlen($league) > 128 || preg_match('/^[\pL\pN][\pL\pN ._\-\']*$/u', $league) !== 1) {
            throw new InvalidArgumentException('The league must be fetched and normalized before it can be used.');
        }

        $path = $category->isExchange()
            ? '/poe1/api/economy/exchange/current/overview'
            : '/poe1/api/economy/stash/current/item/overview';

        return self::BASE_URL.$path.'?'.http_build_query(['league' => $league, 'type' => $category->value], '', '&', PHP_QUERY_RFC3986);
    }

    public static function assertAllowed(string $url): void
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'poe.ninja'
            || isset($parts['port']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('The poe.ninja destination is not allowlisted.');
        }

        $path = $parts['path'] ?? '';
        if ($path === '/poe1/api/economy/leagues' && ! isset($parts['query'])) {
            return;
        }

        if (! in_array($path, ['/poe1/api/economy/exchange/current/overview', '/poe1/api/economy/stash/current/item/overview'], true)) {
            throw new InvalidArgumentException('The poe.ninja endpoint is not allowlisted.');
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        if (array_keys($query) !== ['league', 'type'] || ! is_string($query['league'] ?? null) || ! is_string($query['type'] ?? null)) {
            throw new InvalidArgumentException('The poe.ninja endpoint parameters are not allowlisted.');
        }

        $category = EconomyCategory::tryFrom($query['type']);
        if ($category === null || ($path === '/poe1/api/economy/exchange/current/overview' && ! $category->isExchange()) || ($path === '/poe1/api/economy/stash/current/item/overview' && ! $category->isStash())) {
            throw new InvalidArgumentException('The poe.ninja category is not allowlisted.');
        }
    }
}
