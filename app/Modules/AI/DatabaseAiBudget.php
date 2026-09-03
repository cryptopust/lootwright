<?php

namespace App\Modules\AI;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\AIGateway\DTO\AiBudgetReservation;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;
use Lootwright\Application\AIGateway\Ports\AiBudget;
use RuntimeException;

final readonly class DatabaseAiBudget implements AiBudget
{
    public function __construct(
        private int $perUserDailyMicroUsd,
        private int $perIpDailyMicroUsd,
        private int $globalDailyMicroUsd,
        private int $globalMonthlyMicroUsd,
    ) {}

    public function reserve(AiRequestContext $context, int $maximumMicroUsd): ?AiBudgetReservation
    {
        $limits = $this->limits($context);
        if ($maximumMicroUsd < 1 || in_array(true, array_map(static fn (int $limit): bool => $limit < 1, $limits), true)) {
            return null;
        }

        return DB::transaction(function () use ($context, $maximumMicroUsd, $limits): ?AiBudgetReservation {
            $now = CarbonImmutable::now('UTC');
            $scopes = [
                $this->scope('user_daily', $context->userHash, $now->startOfDay(), $now->endOfDay(), $limits['user_daily']),
                $this->scope('ip_daily', $context->ipHash, $now->startOfDay(), $now->endOfDay(), $limits['ip_daily']),
                $this->scope('global_daily', 'global', $now->startOfDay(), $now->endOfDay(), $limits['global_daily']),
                $this->scope('global_monthly', 'global', $now->startOfMonth(), $now->endOfMonth(), $limits['global_monthly']),
            ];

            foreach ($scopes as $scope) {
                DB::table('ai_budget_counters')->insertOrIgnore([
                    'scope_type' => $scope['type'],
                    'scope_key' => $scope['key'],
                    'period_start' => $scope['start'],
                    'period_end' => $scope['end'],
                    'spent_micro_usd' => 0,
                    'reserved_micro_usd' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $counter = DB::table('ai_budget_counters')
                    ->where('scope_type', $scope['type'])
                    ->where('scope_key', $scope['key'])
                    ->where('period_start', $scope['start'])
                    ->lockForUpdate()
                    ->first();

                if ($counter === null || ((int) $counter->spent_micro_usd + (int) $counter->reserved_micro_usd + $maximumMicroUsd) > $scope['limit']) {
                    return null;
                }
            }

            foreach ($scopes as $scope) {
                DB::table('ai_budget_counters')
                    ->where('scope_type', $scope['type'])
                    ->where('scope_key', $scope['key'])
                    ->where('period_start', $scope['start'])
                    ->increment('reserved_micro_usd', $maximumMicroUsd, ['updated_at' => now()]);
            }

            $id = (string) Str::uuid7();
            DB::table('ai_budget_reservations')->insert([
                'id' => $id,
                'reserved_micro_usd' => $maximumMicroUsd,
                'scopes' => json_encode($scopes, JSON_THROW_ON_ERROR),
                'status' => 'reserved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return new AiBudgetReservation($id, $maximumMicroUsd);
        }, 3);
    }

    public function settle(AiBudgetReservation $reservation, int $actualMicroUsd): void
    {
        $this->finish($reservation, max(0, $actualMicroUsd));
    }

    public function cancel(AiBudgetReservation $reservation): void
    {
        $this->finish($reservation, 0);
    }

    private function finish(AiBudgetReservation $reservation, int $actualMicroUsd): void
    {
        DB::transaction(function () use ($reservation, $actualMicroUsd): void {
            $record = DB::table('ai_budget_reservations')->where('id', $reservation->id)->lockForUpdate()->first();

            if ($record === null || $record->status !== 'reserved') {
                return;
            }

            $scopes = json_decode((string) $record->scopes, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($scopes)) {
                throw new RuntimeException('AI budget reservation scopes are invalid.');
            }

            foreach ($scopes as $scope) {
                DB::table('ai_budget_counters')
                    ->where('scope_type', $scope['type'])
                    ->where('scope_key', $scope['key'])
                    ->where('period_start', $scope['start'])
                    ->decrement('reserved_micro_usd', (int) $record->reserved_micro_usd, ['updated_at' => now()]);

                if ($actualMicroUsd > 0) {
                    DB::table('ai_budget_counters')
                        ->where('scope_type', $scope['type'])
                        ->where('scope_key', $scope['key'])
                        ->where('period_start', $scope['start'])
                        ->increment('spent_micro_usd', $actualMicroUsd, ['updated_at' => now()]);
                }
            }

            DB::table('ai_budget_reservations')->where('id', $reservation->id)->delete();
        }, 3);
    }

    /** @return array{user_daily:int,ip_daily:int,global_daily:int,global_monthly:int} */
    private function limits(AiRequestContext $context): array
    {
        $control = DB::table('ai_runtime_controls')->where('scope', 'global')->first();
        $userOverride = DB::table('ai_user_quota_overrides')->where('user_hash', $context->userHash)->value('daily_budget_micro_usd');

        return [
            'user_daily' => is_numeric($userOverride) ? min((int) $userOverride, $this->perUserDailyMicroUsd) : $this->perUserDailyMicroUsd,
            'ip_daily' => $this->perIpDailyMicroUsd,
            'global_daily' => is_numeric($control?->global_daily_budget_micro_usd) ? min((int) $control->global_daily_budget_micro_usd, $this->globalDailyMicroUsd) : $this->globalDailyMicroUsd,
            'global_monthly' => is_numeric($control?->global_monthly_budget_micro_usd) ? min((int) $control->global_monthly_budget_micro_usd, $this->globalMonthlyMicroUsd) : $this->globalMonthlyMicroUsd,
        ];
    }

    /** @return array{type: string, key: string, start: string, end: string, limit: int} */
    private function scope(string $type, string $key, CarbonImmutable $start, CarbonImmutable $end, int $limit): array
    {
        return [
            'type' => $type,
            'key' => $key,
            'start' => $start->format('Y-m-d H:i:sP'),
            'end' => $end->format('Y-m-d H:i:sP'),
            'limit' => $limit,
        ];
    }
}
