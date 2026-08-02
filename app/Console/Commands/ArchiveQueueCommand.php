<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveQueueTickets;
use Illuminate\Console\Command;

class ArchiveQueueCommand extends Command
{
    protected $signature = 'queue:archive {--force : Skip confirmation prompt}';
    protected $description = 'Archive old queue tickets (completed, no_show, waiting, serving)';

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will archive all old queue tickets. Continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Starting queue archival...');
        
        ArchiveQueueTickets::dispatchSync();
        
        $this->info('Queue archival completed successfully!');
        
        return Command::SUCCESS;
    }
}