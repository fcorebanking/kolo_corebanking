<?php
namespace App\Jobs;

use App\Models\DailySummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DailyHybridSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate = null, $toDate = null)
    {
        $this->fromDate = $fromDate ?? now()->toDateString();
        $this->toDate   = $toDate ?? now()->toDateString();
    }

    public function handle()
    {
        // Step 1: Aggregate in SQL
        $aggregatedRows = DB::table('transactions as t')
            ->select(
                't.company_id',
                't.branch_id',
                DB::raw('u.account_officer_id as officer_id'),
                't.transaction_type_id',
                DB::raw('DATE(t.created_at) as summary_date'),
                DB::raw('SUM(CASE WHEN t.transaction_type_id IN (7,8,93) THEN t.withdrawal_charge ELSE t.amount END) as profit')
            )
            ->leftJoin('wb_account_details as w', 'w.id', '=', 't.account_id')
            ->leftJoin('users as u', 'u.id', '=', 'w.user_id')
            ->where('t.transaction_status_id', 1)
            ->whereBetween('t.created_at', [$this->fromDate, $this->toDate])
            ->groupBy('t.company_id', 't.branch_id', 'u.account_officer_id', 't.transaction_type_id', DB::raw('DATE(t.created_at)'))
            ->get();

        // Step 2: Loop over aggregated rows for PHP-level logic / updateOrCreate
        foreach ($aggregatedRows as $row) {
            // Example: Add any custom PHP adjustments per row
            $adjustedProfit = $row->profit;
            // $adjustedProfit += customLogic($row);

            \Log::info('Start batch processing aggregation ----> ');
            \Log::info('officer_id', [$row->officer_id]);
            \Log::info('company_id', [$row->company_id]);
            \Log::info('branch_id', [$row->branch_id]);
            \Log::info('summary_date', [$row->summary_date]);
            \Log::info('amount', [$adjustedProfit]);
            \Log::info('end processing  aggregation ----> ');

            if (is_null($row->company_id) || is_null($row->branch_id) || is_null($row->officer_id)) {
                \logInfo(json_encode($row), "SKIPPED — company/branch/officer is null");
                continue; // 🚀 Do not insert invalid row
            } else {
                DailySummary::updateOrCreate(
                    [
                        'company_id'          => $row->company_id,
                        'branch_id'           => $row->branch_id,
                        'officer_id'          => $row->officer_id,
                        'transaction_type_id' => $row->transaction_type_id,
                        'summary_date'        => $row->summary_date,
                    ],
                    [
                        'amount' => $adjustedProfit,
                    ]
                );
            }

        }
    }
}
