<?php

namespace App\Console\Commands;

use App\Modules\BuildIntake\PobImportRejected;
use App\Modules\BuildIntake\PobImportStore;
use App\Modules\BuildIntake\PobPolicyDenied;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use App\Modules\PolicyProvenance\LocalFixtureCapabilityPolicy;
use Illuminate\Console\Command;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Psr\Log\NullLogger;

class ImportPobFixture extends Command
{
    protected $signature = 'pob:import-fixture {path : Local plain-text fixture path}';

    protected $description = 'Import a local PoB fixture and print safe normalized JSON without external services';

    public function handle(PobImportCoordinator $importer): int
    {
        $path = $this->argument('path');

        if ($this->isRemotePath($path)) {
            $this->error('Fixture path must be a local filesystem path, not a URL, stream wrapper, or network share.');

            return self::FAILURE;
        }

        $resolvedPath = realpath($path);

        if (! is_string($resolvedPath) || ! is_file($resolvedPath) || is_link($path)) {
            $this->error('Fixture path must identify a regular local file.');

            return self::FAILURE;
        }

        $size = filesize($resolvedPath);

        if (! is_int($size) || $size > 1_048_576) {
            $this->error('Fixture exceeds the 1 MiB input limit.');

            return self::FAILURE;
        }

        $input = file_get_contents($resolvedPath, false, null, 0, 1_048_577);

        if (! is_string($input)) {
            $this->error('Fixture could not be read.');

            return self::FAILURE;
        }

        if (strlen($input) > 1_048_576) {
            $this->error('Fixture exceeds the 1 MiB input limit.');

            return self::FAILURE;
        }

        $gatedImporter = new PolicyGatedPobImporter(
            $importer,
            new DecideCapability(new LocalFixtureCapabilityPolicy((bool) config('policy.global_kill_switch'))),
            new PobImportStore,
            new NullLogger,
        );

        try {
            $result = $gatedImporter->handle($input, false)->result;
        } catch (PobImportRejected $exception) {
            $this->line(CanonicalJson::encode([
                'status' => 'rejected',
                'error' => $exception->domainError->code->value,
                'message' => $exception->domainError->message,
            ]));

            return self::FAILURE;
        } catch (PobPolicyDenied $exception) {
            $this->line(CanonicalJson::encode([
                'status' => 'policy_denied',
                'reason' => $exception->decision->reason->value,
            ]));

            return self::FAILURE;
        }

        $this->line(CanonicalJson::encode($result));

        return self::SUCCESS;
    }

    private function isRemotePath(string $path): bool
    {
        return preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $path) === 1
            || str_starts_with($path, '\\\\')
            || str_starts_with($path, '//');
    }
}
