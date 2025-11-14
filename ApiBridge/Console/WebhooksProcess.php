<?php

namespace Modules\ApiBridge\Console;

use Illuminate\Console\Command;
use Modules\ApiBridge\Entities\WebhookLog;
use Modules\ApiBridge\Services\WebhookDispatcher;

class WebhooksProcess extends Command
{
    protected $signature = 'apibridge:webhooks-process';

    protected $description = 'Retry failed ApiBridge webhooks';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        WebhookLog::query()
            ->where('finished', false)
            ->chunkById(50, function ($logs) use ($dispatcher) {
                foreach ($logs as $log) {
                    if (!$log->webhook) {
                        $log->markFinished('Missing webhook');
                        continue;
                    }

                    $backoffMinutes = max(1, ($log->attempts - 1) * 2);
                    $nextRunAt = $log->updated_at->copy()->addMinutes($backoffMinutes);

                    if (now()->lessThan($nextRunAt)) {
                        continue;
                    }

                    $success = $dispatcher->dispatch(
                        $log->webhook,
                        $log->event,
                        $log->payload,
                        $log
                    );

                    if ($success) {
                        $log->markFinished();
                    } else {
                        $log->incrementAttempts();
                    }
                }
            });

        return 0;
    }
}


