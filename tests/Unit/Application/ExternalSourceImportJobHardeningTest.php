<?php

namespace Tests\Unit\Application;

use App\Modules\ExternalSources\Jobs\RunExternalSourceImportJob;
use DomainException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Domain\Shared\Game\GameEdition;
use Tests\TestCase;

final class ExternalSourceImportJobHardeningTest extends TestCase
{
    public function test_source_import_job_has_bounded_timeout_retry_and_unique_identity(): void
    {
        $job = new RunExternalSourceImportJob('GGG-POE1-SKILLTREE-001');

        self::assertInstanceOf(ShouldBeUnique::class, $job);
        self::assertSame(1, $job->tries);
        self::assertSame(600, $job->timeout);
        self::assertSame(900, $job->uniqueFor);
        self::assertTrue($job->failOnTimeout);
        self::assertSame('source-import:'.hash('sha256', 'GGG-POE1-SKILLTREE-001'), $job->uniqueId());
    }

    public function test_overlapping_execution_is_a_safe_noop_and_invalid_payload_never_calls_an_adapter(): void
    {
        $adapter = new RecordingSourceAdapter(true);
        $catalog = new FixedTestSourceCatalog($adapter);
        $lock = Cache::lock('external-source:manual-import:GGG-POE1-SKILLTREE-001', 900);
        self::assertTrue($lock->get());

        try {
            (new RunExternalSourceImportJob('GGG-POE1-SKILLTREE-001'))->handle($catalog);
            (new RunExternalSourceImportJob("BAD\nSOURCE"))->handle($catalog);
        } finally {
            $lock->release();
        }

        self::assertSame(0, $adapter->imports);
    }

    public function test_failed_import_is_terminal_and_does_not_expose_adapter_details(): void
    {
        $adapter = new RecordingSourceAdapter(false);

        try {
            (new RunExternalSourceImportJob('GGG-POE1-SKILLTREE-001'))->handle(new FixedTestSourceCatalog($adapter));
            self::fail('A failed source import must fail the queue job.');
        } catch (DomainException $exception) {
            self::assertSame('The source import failed.', $exception->getMessage());
        }

        self::assertSame(1, $adapter->imports);
    }
}

final class FixedTestSourceCatalog implements ExternalSourceAdapterCatalog
{
    public function __construct(private readonly ExternalSourceAdapter $adapter) {}

    public function all(): array
    {
        return [$this->adapter];
    }

    public function find(string $sourceCode): ?ExternalSourceAdapter
    {
        return $sourceCode === 'GGG-POE1-SKILLTREE-001' ? $this->adapter : null;
    }
}

final class RecordingSourceAdapter implements ExternalSourceAdapter
{
    public int $imports = 0;

    public function __construct(private readonly bool $success) {}

    public function status(): SourceAdapterStatus
    {
        return new SourceAdapterStatus(
            'GGG-POE1-SKILLTREE-001',
            'fixture',
            [GameEdition::Poe1],
            ['operator_import'],
            true,
            null,
        );
    }

    public function import(): SourceAdapterRunResult
    {
        $this->imports++;

        return new SourceAdapterRunResult($this->success, $this->success ? 1 : 0, $this->success ? null : 'private-upstream-detail');
    }
}
