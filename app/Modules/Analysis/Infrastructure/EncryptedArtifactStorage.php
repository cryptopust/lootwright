<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Throwable;

final class EncryptedArtifactStorage implements ArtifactStorage
{
    private const DISK = 'analysis-artifacts';

    public function put(string $key, string $contents): void
    {
        try {
            if (! Storage::disk(self::DISK)->put($key, Crypt::encryptString($contents))) {
                throw new TransientWorkflowFailure('The artifact store rejected the write.');
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
            $encrypted = Storage::disk(self::DISK)->get($key);

            return Crypt::decryptString($encrypted);
        } catch (Throwable $exception) {
            throw new TransientWorkflowFailure('The artifact could not be read from private storage.', previous: $exception);
        }
    }

    public function delete(string $key): void
    {
        try {
            Storage::disk(self::DISK)->delete($key);
        } catch (Throwable $exception) {
            throw new TransientWorkflowFailure('The artifact could not be deleted from private storage.', previous: $exception);
        }
    }
}
