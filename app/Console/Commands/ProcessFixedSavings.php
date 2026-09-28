<?php

namespace App\Console\Commands;

use App\Services\FixedSavingsService;
use Illuminate\Console\Command;
use Throwable;

class ProcessFixedSavings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixed-savings:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily Fixed Savings interest and mature eligible savings';

    /**
     * Execute the console command.
     */
    public function handle(FixedSavingsService $fixedSavingsService): int
    {
        $this->info('Starting Fixed Savings processing...');

        try {
            /*
             * Process daily interest first.
             *
             * This ensures any interest due up to the previous
             * business date is applied before a fixed matures.
             */
            $this->info('Processing daily Fixed Savings interest...');

            $fixedSavingsService->processDailyInterest();

            $this->info('Daily interest processing completed.');

            /*
             * Process fixed whose withdrawal date has arrived.
             */
            $this->info('Processing Fixed Savings maturities...');

            $fixedSavingsService->processMaturities();

            $this->info('Maturity processing completed.');

            $this->info('Fixed Savings processing completed successfully.');

            return self::SUCCESS;

        } catch (Throwable $e) {

            report($e);

            $this->error(
                'Fixed Savings processing failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}