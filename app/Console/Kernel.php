<?php
namespace App\Console;

//use App\Console\Commands\BankTranserCron;
//use App\Console\Commands\BirthdayCron;
use App\Console\Commands\CompanySettimentThreshold;
use App\Console\Commands\DomCron;
//use App\Console\Commands\InvestmentCron;
use App\Console\Commands\LoanCron;
use App\Console\Commands\LoanExtend;
//use App\Console\Commands\maintenance;
use App\Console\Commands\maintenancePackCron;
//use App\Console\Commands\pendingpenalty;
//use App\Console\Commands\PendingsmsCron;
//use App\Console\Commands\ResetSalaryPaymentCron;
//use App\Console\Commands\SalaryPaymentCron;
use App\Jobs\BankTranserCron;
use App\Jobs\BirthdayCron;
use App\Jobs\InvestmentCron;
use App\Jobs\Maintenance;
use App\Jobs\PendingPenalty;
use App\Jobs\PendingSmsCron;
use App\Jobs\PendingTierCron;
use App\Jobs\ResetSalaryPaymentCron;
use App\Jobs\SalaryPaymentCron;
use App\Jobs\WebCron;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // BankTranserCron::class,
        //  SalaryPaymentCron::class,
        //   ResetSalaryPaymentCron::class,
        //  BirthdayCron::class,
        // InvestmentCron::class,
        //   PendingsmsCron::class,
        LoanCron::class,
        LoanExtend::class,
        //  CompanySettimentThreshold::class,
        //   pendingpenalty::class,
        // savingscron::class,
        // SavingpackCron::class,
        //   maintenance::class,
        maintenancePackCron::class,
        DomCron::class,
        //  WebhookCron::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('bank_tranfer:cron')->everyFiveMinutes();
        $schedule->job(new BankTranserCron)->everyThirtyMinutes()->onSuccess(function () {
            Log::info('Your PendingPenalty Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your PendingPenalty Job failed: ' . $exception->getMessage());
            });

        //    $schedule->command('salary_payment:cron')->everyFiveMinutes();
        $schedule->job(new SalaryPaymentCron)->daily()->onSuccess(function () {
            Log::info('Your SalaryPaymentCron Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your SalaryPaymentCron Job failed: ' . $exception->getMessage());
            });

        //  $schedule->command('pendingsms:cron')->everyFiveMinutes();
        $schedule->job(new PendingSmsCron)->hourly()->onSuccess(function () {
            Log::info('Your PendingPenalty Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your PendingPenalty Job failed: ' . $exception->getMessage());
            });

        //   $schedule->command('reset_salary_payment:cron')->everyFiveMinutes();
        $schedule->job(new ResetSalaryPaymentCron)->everySixHours()->onSuccess(function () {
            Log::info('Your PendingPenalty Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your PendingPenalty Job failed: ' . $exception->getMessage());
            });

        //$schedule->command('pending_tier:cron')->everyTenMinutes();
        $schedule->job(new PendingTierCron)->everySixHours()->onSuccess(function () {
            Log::info('Your PendingTierCron Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your PendingTierCron Job failed: ' . $exception->getMessage());
            });

        //$schedule->command('investment:cron')->everyFiveMinutes();
        $schedule->job(new InvestmentCron)->everySixHours()->onSuccess(function () {
            Log::info('Your BirthdayCron Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your BirthdayCron Job failed: ' . $exception->getMessage());
            });

        //   $schedule->command('birthday:cron')->everyFiveMinutes();
        $schedule->job(new BirthdayCron)->everySixHours()->onSuccess(function () {
            Log::info('Your BirthdayCron Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your BirthdayCron Job failed: ' . $exception->getMessage());
            });

        $schedule->command('loan:cron')->everyFiveMinutes();
        $schedule->command('LoanExtend:cron')->everyFiveMinutes();

        //$schedule->command('CompanySettimentThreshold:cron')->everyFiveMinutes();
        $schedule->job(new CompanySettimentThreshold)->everyFiveMinutes()->onSuccess(function () {
            Log::info('Your CompanySettimentThreshold Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your CompanySettimentThreshold Job failed: ' . $exception->getMessage());
            });

        //  $schedule->command('pendingpenalty:cron')->everyFiveMinutes();
        $schedule->job(new PendingPenalty)->everyFiveMinutes()->onSuccess(function () {
            Log::info('Your PendingPenalty Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your PendingPenalty Job failed: ' . $exception->getMessage());
            });
        // $schedule->command('savings:name')->daily();
        // $schedule->command('savingpack:cron')->everyMinute();

        //  $schedule->command('maintenance:cron')->daily();
        $schedule->job(new Maintenance)->daily()->onSuccess(function () {
            Log::info('Your Maintenance Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your Maintenances Job failed: ' . $exception->getMessage());
            });

        $schedule->command('pendingmaintenance:charge')->everyFiveMinutes();

        //  $schedule->command('webhook:cron')->everyFiveMinutes();
        $schedule->job(new WebCron)->everyFiveMinutes()->onSuccess(function () {
            Log::info('Your WebCron Job executed successfully.');
        })
            ->onFailure(function ($exception) {
                Log::error('Your WebCron Job failed: ' . $exception->getMessage());
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
