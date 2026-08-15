<?php

namespace App\Console\Commands;

use App\Modules\Analysis\Infrastructure\LaravelWorkflowDispatcher;
use Illuminate\Console\Command;

final class DispatchWorkflowOutbox extends Command
{
    protected $signature = 'workflow:dispatch-outbox {--limit=100 : Maximum pending records to dispatch}';

    protected $description = 'Dispatch pending durable workflow jobs to their isolated Horizon queues';

    public function handle(LaravelWorkflowDispatcher $dispatcher): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if (! is_int($limit) || $limit < 1 || $limit > 500) {
            $this->error('The outbox limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $count = $dispatcher->flushPending($limit);
        $this->info("Dispatched {$count} workflow outbox record(s).");

        return self::SUCCESS;
    }
}
