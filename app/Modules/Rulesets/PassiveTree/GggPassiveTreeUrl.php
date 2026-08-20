<?php

namespace App\Modules\Rulesets\PassiveTree;

use InvalidArgumentException;

final class GggPassiveTreeUrl
{
    private const PATTERN = '#\Ahttps://raw\.githubusercontent\.com/grindinggear/skilltree-export/([0-9a-f]{40})/data\.json\z#D';

    private function __construct() {}

    public static function revision(string $url): string
    {
        if (preg_match(self::PATTERN, $url, $matches) !== 1) {
            throw new InvalidArgumentException('URL must be an exact commit-pinned official GGG data.json raw URL.');
        }

        return $matches[1];
    }

    public static function forRevision(string $revision): string
    {
        if (preg_match('/^[0-9a-f]{40}$/D', $revision) !== 1) {
            throw new InvalidArgumentException('The upstream revision must be a lowercase 40-character commit hash.');
        }

        return "https://raw.githubusercontent.com/grindinggear/skilltree-export/{$revision}/data.json";
    }
}
