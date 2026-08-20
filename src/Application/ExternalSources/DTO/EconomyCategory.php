<?php

namespace Lootwright\Application\ExternalSources\DTO;

use InvalidArgumentException;

enum EconomyCategory: string
{
    case Currency = 'Currency'; case Fragment = 'Fragment'; case Tattoo = 'Tattoo'; case Omen = 'Omen'; case DivinationCard = 'DivinationCard'; case Oil = 'Oil'; case DeliriumOrb = 'DeliriumOrb'; case Scarab = 'Scarab'; case Fossil = 'Fossil'; case Resonator = 'Resonator'; case Essence = 'Essence';
    case UniqueWeapon = 'UniqueWeapon'; case UniqueArmour = 'UniqueArmour'; case UniqueAccessory = 'UniqueAccessory'; case UniqueFlask = 'UniqueFlask'; case UniqueJewel = 'UniqueJewel'; case ForbiddenJewel = 'ForbiddenJewel'; case SkillGem = 'SkillGem'; case ClusterJewel = 'ClusterJewel'; case BaseType = 'BaseType';

    public function isExchange(): bool { return in_array($this, self::exchange(), true); }
    public function isStash(): bool { return in_array($this, self::stash(), true); }
    /** @return list<self> */ public static function exchange(): array { return [self::Currency, self::Fragment, self::Tattoo, self::Omen, self::DivinationCard, self::Oil, self::DeliriumOrb, self::Scarab, self::Fossil, self::Resonator, self::Essence]; }
    /** @return list<self> */ public static function stash(): array { return [self::UniqueWeapon, self::UniqueArmour, self::UniqueAccessory, self::UniqueFlask, self::UniqueJewel, self::ForbiddenJewel, self::SkillGem, self::ClusterJewel, self::BaseType]; }
    /** @return list<string> */ public static function exchangeValues(): array { return array_map(static fn (self $item): string => $item->value, self::exchange()); }
    /** @return list<string> */ public static function stashValues(): array { return array_map(static fn (self $item): string => $item->value, self::stash()); }
    public static function exchangeFrom(string $value): self { $category = self::tryFrom($value); if ($category === null || ! $category->isExchange()) { throw new InvalidArgumentException('Unsupported poe.ninja exchange category.'); } return $category; }
    public static function stashFrom(string $value): self { $category = self::tryFrom($value); if ($category === null || ! $category->isStash()) { throw new InvalidArgumentException('Unsupported poe.ninja stash category.'); } return $category; }
}
