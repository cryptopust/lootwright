<?php

namespace App\Modules\ExternalSources\Ggg;

/** Interfaces/configuration only: GGG application registration is currently unavailable. */
interface GggLeaguesProvider {}
interface GggCurrencyExchangeHistoryProvider {}
interface GggAccountCharactersProvider {}
interface GggAccountStashesProvider {}
final class DisabledGggApiAdapters implements GggAccountCharactersProvider, GggAccountStashesProvider, GggCurrencyExchangeHistoryProvider, GggLeaguesProvider {}
