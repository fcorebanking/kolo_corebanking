<?php

namespace App\Console\Commands;

use App\Services\TargetSavingsService;
use Illuminate\Console\Command;
use Throwable;

class ProcessTargetSavings extends Command
{
    protected $signature = 'target-savings:process';

    protected $description = 'Process daily Target Savings interest and mature eligible savings';

    public function handle(TargetSavingsService $targetSavingsService): int
    {
        $this->info('Starting Target Savings daily processing...');

        try {
            $this->info('Processing daily Target Savings interest...');
            $targetSavingsService->processDailyInterest();
            $this->info('Daily interest processing completed.');

            $this->info('Processing Target Savings maturities...');
            $targetSavingsService->processMaturities();
            $this->info('Maturity processing completed.');

            $this->info('Target Savings daily processing completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error(
                'Target Savings daily processing failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
