<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrivatizeApplicationAttachments extends Command
{
    protected $signature = 'applications:privatize-attachments {--execute : Copy, verify, update references, and remove old public files}';
    protected $description = 'Preview or safely migrate legacy application files into private storage';

    public function handle(): int
    {
        $count = 0;
        $failures = 0;
        $publicPaths = [];

        Application::whereNotNull('attachments')->chunkById(100, function ($applications) use (&$count, &$failures, &$publicPaths) {
            foreach ($applications as $application) {
                $attachments = $application->attachments ?: [];
                $changed = false;
                foreach ($attachments as $index => $attachment) {
                    $storage = Application::attachmentStorage($attachment);
                    if (!$storage || $storage['disk'] !== 'public') {
                        continue;
                    }
                    $count++;
                    if (!$this->option('execute')) {
                        continue;
                    }
                    $path = $storage['path'];
                    try {
                        if (!Storage::disk('public')->exists($path)) {
                            throw new \RuntimeException('Legacy file is missing.');
                        }
                        $contents = Storage::disk('public')->get($path);
                        $backup = 'attachment-backups/'.hash('sha256', $path).'/'.basename($path);
                        $destination = 'applications/legacy/'.hash('sha256', $path).'/'.basename($path);
                        foreach ([$backup, $destination] as $copy) {
                            if (!Storage::disk('local')->put($copy, $contents)
                                || !hash_equals(hash('sha256', $contents), hash('sha256', Storage::disk('local')->get($copy)))) {
                                throw new \RuntimeException('Private copy verification failed.');
                            }
                        }
                        $attachments[$index] = array_merge($attachment, ['disk' => 'local', 'path' => $destination]);
                        unset($attachments[$index]['url']);
                        $publicPaths[$path] = true;
                        $changed = true;
                    } catch (\Throwable $exception) {
                        $failures++;
                        $this->error("Application {$application->id}: {$exception->getMessage()}");
                    }
                }
                if ($changed) {
                    DB::transaction(function () use ($application, $attachments) {
                        $current = Application::lockForUpdate()->findOrFail($application->id);
                        if ($current->attachments !== $application->attachments) {
                            throw new \RuntimeException('Attachments changed during migration. Retry the command.');
                        }
                        $current->update(['attachments' => $attachments]);
                    });
                }
            }
        });

        if ($this->option('execute')) {
            // A legacy file can be shared by several records. Remove it only once all references migrated.
            Application::whereNotNull('attachments')->chunkById(100, function ($applications) use (&$publicPaths) {
                foreach ($applications as $application) {
                    foreach ($application->attachments ?: [] as $attachment) {
                        $storage = Application::attachmentStorage($attachment);
                        if ($storage && $storage['disk'] === 'public') {
                            unset($publicPaths[$storage['path']]);
                        }
                    }
                }
            });
            foreach (array_keys($publicPaths) as $path) {
                if (!Storage::disk('public')->delete($path)) {
                    $failures++;
                    $this->error('A migrated public file could not be removed.');
                }
            }
        }

        $this->info($this->option('execute') ? "Processed {$count} legacy attachments; {$failures} failures." : "Dry run: {$count} legacy attachments. Use --execute after taking a database and storage backup.");
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
