<?php

namespace App\Console\Commands;

use App\Services\TargetSavingsService;
use Illuminate\Console\Command;
use Throwable;

class ProcessRecurringTargetSavings extends Command
{
    protected $signature = 'target-savings:process-recurring';

    protected $description = 'Send reminders and process due automatic Target Savings deposits';

    public function handle(TargetSavingsService $targetSavingsService): int
    {
        $this->info('Starting recurring Target Savings processing...');

        try {
            $notifications = $targetSavingsService->notifyScheduledDeposits();

            $this->info(
                sprintf(
                    'SMS reminders: %d notified, %d failed, %d skipped.',
                    $notifications['notified'] ?? 0,
                    $notifications['failed'] ?? 0,
                    $notifications['skipped'] ?? 0
                )
            );

            $deposits = $targetSavingsService->processScheduledDeposits();

            $this->info(
                sprintf(
                    'Automatic deposits: %d processed, %d failed, %d skipped.',
                    $deposits['processed'] ?? 0,
                    $deposits['failed'] ?? 0,
                    $deposits['skipped'] ?? 0
                )
            );

            $this->info('Recurring Target Savings processing completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error(
                'Recurring Target Savings processing failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
