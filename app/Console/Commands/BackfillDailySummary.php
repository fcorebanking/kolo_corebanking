<?php
namespace App\Console\Commands;

use App\Jobs\DailyHybridSummaryJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summary:backfill {from} {to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill summaries between dates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $from = $this->argument('from');
        $to   = $this->argument('to');

        Log::info("Profit backfill queued from {$from} to {$to}");

        DailyHybridSummaryJob::dispatch($from, $to);

        Log::info("Profit backfill success from {$from} to {$to}");
        return Command::SUCCESS;
    }
}
