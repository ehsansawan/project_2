<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteExpiredAccountsCommand extends Command
{
    protected $signature = 'accounts:delete-expired';
    protected $description = 'Soft delete accounts whose expiry date passed more than 14 days ago';

    public function handle(): int
    {
        $threshold = now()->subDays(14);

        $users = User::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $threshold)
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $user->delete();
            $count++;
        }

        $this->info("Deleted {$count} expired account(s).");

        return Command::SUCCESS;
    }
}