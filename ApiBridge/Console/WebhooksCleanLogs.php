<?php

namespace Modules\ApiBridge\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\ApiBridge\Entities\WebhookLog;

class WebhooksCleanLogs extends Command
{
    protected $signature = 'apibridge:webhooks-clean-logs {--days=30 : Remove logs older than this number of days}';

    protected $description = 'Prune ApiBridge webhook logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days ?: 30);

        $deleted = WebhookLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} webhook log entries older than {$cutoff->toDateTimeString()}.");

        return 0;
    }
}


