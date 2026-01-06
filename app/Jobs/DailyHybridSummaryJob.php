<?php
namespace App\Jobs;

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
            \logInfo(json_encode($row->officer_id), "row->officer_id ");
            \logInfo(json_encode($row->company_id), "row->company_id ");
            \logInfo(json_encode($row->branch_id), "row->branch_id ");

            if ($row->officer_id == null) {
                $officer_id = 0;
            } else {
                $officer_id = $row->officer_id;
            }

            if ($row->company_id == null) {
                $company_id = 0;
            } else {
                $company_id = $row->company_id;
            }

            if ($row->branch_id == null) {
                $branch_id = 0;
            } else {
                $branch_id = $row->branch_id;
            }

            \logInfo(json_encode($officer_id), "officer_id ");
            \logInfo(json_encode($company_id), "company_id ");
            \logInfo(json_encode($branch_id), "branch_id ");
            \logInfo(json_encode($row->summary_date), "summary_date");
            \logInfo(json_encode($adjustedProfit), "amount ");

            DailyProfitSummary::updateOrCreate(
                [
                    'company_id'          => $company_id,
                    'branch_id'           => $branch_id,
                    'officer_id'          => $officer_id,
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
