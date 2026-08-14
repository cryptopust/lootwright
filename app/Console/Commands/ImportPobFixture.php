<?php

namespace App\Console\Commands;

use App\Modules\BuildIntake\PobImportRejected;
use App\Modules\BuildIntake\PobImportStore;
use App\Modules\BuildIntake\PobPolicyDenied;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use App\Modules\PolicyProvenance\LocalFixtureCapabilityPolicy;
use Illuminate\Console\Command;
use Lootwright\Application\BuildIntake\PobImportService;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Psr\Log\NullLogger;

class ImportPobFixture extends Command
{
    protected $signature = 'pob:import-fixture {path : Local plain-text fixture path}';

    protected $description = 'Import a local PoB fixture and print safe normalized JSON without external services';

    public function handle(PobImportService $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path) || is_link($path)) {
            $this->error('Fixture path must identify a regular local file.');

            return self::FAILURE;
        }

        $size = filesize($path);

        if (! is_int($size) || $size > 1_048_576) {
            $this->error('Fixture exceeds the 1 MiB input limit.');

            return self::FAILURE;
        }

        $input = file_get_contents($path);

        if (! is_string($input)) {
            $this->error('Fixture could not be read.');

            return self::FAILURE;
        }

        $gatedImporter = new PolicyGatedPobImporter(
            $importer,
            new DecideCapability(new LocalFixtureCapabilityPolicy),
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
}
