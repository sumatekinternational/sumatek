<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes raw identity-capture images older than the configured retention
 * window (§5/§9) — raw scans are never kept longer than policy allows.
 */
class PurgeRawCaptures extends Command
{
    protected $signature = 'identity:purge-raw-captures';

    protected $description = 'Delete raw identity capture images past their retention window.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $dir = 'identity-raw';

        if (! $disk->exists($dir)) {
            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes((int) config('identity.raw_image_retention_minutes', 10));
        $deleted = 0;

        foreach ($disk->allFiles($dir) as $file) {
            if (Carbon::createFromTimestamp($disk->lastModified($file))->lessThan($cutoff)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Purged {$deleted} raw capture(s).");

        return self::SUCCESS;
    }
}
