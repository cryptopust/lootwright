<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePobImportRequest;
use App\Modules\BuildIntake\PobImportConflict;
use App\Modules\BuildIntake\PobImportRejected;
use App\Modules\BuildIntake\PobPolicyDenied;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use RuntimeException;

class PobImportController extends Controller
{
    public function __invoke(
        StorePobImportRequest $request,
        PolicyGatedPobImporter $importer,
    ): JsonResponse {
        $input = $this->input($request);
        $actorIdentifier = $request->user()?->getAuthIdentifier();
        $actorId = is_int($actorIdentifier) || is_string($actorIdentifier)
            ? (string) $actorIdentifier
            : null;

        try {
            $execution = $importer->handle(
                $input,
                $request->boolean('persist'),
                $request->integer('retention_hours') ?: null,
                $request->header('Idempotency-Key'),
                $actorId,
            );
        } catch (PobImportConflict) {
            return response()->json([
                'status' => 'idempotency_conflict',
            ], 409);
        } catch (PobPolicyDenied $exception) {
            return response()->json([
                'status' => 'policy_denied',
                'policy' => $exception->decision,
            ], 403);
        } catch (PobImportRejected $exception) {
            $status = in_array($exception->domainError->code, [
                DomainErrorCode::InputTooLarge,
                DomainErrorCode::DecompressionLimit,
            ], true) ? 413 : 422;

            return response()->json([
                'status' => 'rejected',
                'error' => $exception->domainError,
            ], $status);
        }

        $stored = $execution->storedImport;

        return response()->json([
            'status' => 'normalized',
            'import' => $execution->result,
            'retention' => $stored === null ? [
                'persisted' => false,
            ] : [
                'persisted' => true,
                'id' => $stored->id,
                'expires_at' => $stored->expiresAt,
                'deletion_token' => $stored->deletionToken,
                'idempotent_replay' => $stored->replayed,
            ],
        ], $stored === null || $stored->replayed ? 200 : 201, [
            'Cache-Control' => 'no-store',
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    private function input(StorePobImportRequest $request): string
    {
        $text = $request->input('input');

        if (is_string($text)) {
            return $text;
        }

        $file = $request->file('build_file');

        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('The validated build file is unavailable.');
        }

        $contents = file_get_contents($file->getRealPath());

        if (! is_string($contents)) {
            throw new RuntimeException('The validated build file could not be read.');
        }

        return $contents;
    }
}
