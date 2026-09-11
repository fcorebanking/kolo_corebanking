<?php

namespace App\Console\Commands;

use App\Services\TargetSavingsService;
use Illuminate\Console\Command;
use Throwable;

class ProcessTargetSavings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'target-savings:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily Target Savings interest and mature eligible savings';

    /**
     * Execute the console command.
     */
    public function handle(TargetSavingsService $targetSavingsService): int
    {
        $this->info('Starting Target Savings processing...');

        try {
            /*
             * Process daily interest first.
             *
             * This ensures any interest due up to the previous
             * business date is applied before a target matures.
             */
            $this->info('Processing daily Target Savings interest...');

            $targetSavingsService->processDailyInterest();

            $this->info('Daily interest processing completed.');

            /*
             * Process targets whose withdrawal date has arrived.
             */
            $this->info('Processing Target Savings maturities...');

            $targetSavingsService->processMaturities();

            $this->info('Maturity processing completed.');

            $this->info('Target Savings processing completed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {

            report($e);

            $this->error(
                'Target Savings processing failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}