<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Throwable;

/** Durable encrypted artifact storage for runtimes without a mounted disk. */
final class DatabaseArtifactStorage implements ArtifactStorage
{
    public function put(string $key, string $contents): void
    {
        try {
            $updated = DB::table('build_artifacts')->where('blob_key', $key)->update([
                'raw_contents_encrypted' => Crypt::encryptString($contents),
            ]);
            if ($updated !== 1) {
                throw new TransientWorkflowFailure('The artifact record was not found.');
            }
        } catch (TransientWorkflowFailure $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransientWorkflowFailure('The artifact store is temporarily unavailable.', previous: $exception);
        }
    }

    public function get(string $key): string
    {
        try {
            $encrypted = DB::table('build_artifacts')->where('blob_key', $key)->value('raw_contents_encrypted');
            if (! is_string($encrypted) || $encrypted === '') {
                throw new TransientWorkflowFailure('The artifact could not be read from private storage.');
            }

            return Crypt::decryptString($encrypted);
        } catch (TransientWorkflowFailure $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransientWorkflowFailure('The artifact could not be read from private storage.', previous: $exception);
        }
    }

    public function delete(string $key): void
    {
        try {
            DB::table('build_artifacts')->where('blob_key', $key)->update(['raw_contents_encrypted' => null]);
        } catch (Throwable $exception) {
            throw new TransientWorkflowFailure('The artifact could not be deleted from private storage.', previous: $exception);
        }
    }
}
