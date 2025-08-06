<?php
namespace App\Console;

use App\Console\Commands\BankTranserCron;
use App\Console\Commands\BirthdayCron;
use App\Console\Commands\CompanySettimentThreshold;
use App\Console\Commands\DomCron;
use App\Console\Commands\InvestmentCron;
use App\Console\Commands\LoanCron;
use App\Console\Commands\LoanExtend;
use App\Console\Commands\maintenance;
use App\Console\Commands\maintenancePackCron;
use App\Console\Commands\pendingpenalty;
use App\Console\Commands\PendingsmsCron;
use App\Console\Commands\ResetSalaryPaymentCron;
use App\Console\Commands\SalaryPaymentCron;
use App\Console\Commands\WebhookCron;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

ini_set('memory_limit', '500000M');

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        BankTranserCron::class,
        SalaryPaymentCron::class,
        ResetSalaryPaymentCron::class,
        BirthdayCron::class,
        InvestmentCron::class,
        PendingsmsCron::class,
        LoanCron::class,
        LoanExtend::class,
        CompanySettimentThreshold::class,
        pendingpenalty::class,
        // savingscron::class,
        // SavingpackCron::class,
        maintenance::class,
        maintenancePackCron::class,
        DomCron::class,
        WebhookCron::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('bank_tranfer:cron')
            ->everyFiveMinutes();
        $schedule->command('salary_payment:cron')
            ->everyFiveMinutes();
        $schedule->command('pendingsms:cron')
            ->everyFiveMinutes();
        $schedule->command('reset_salary_payment:cron')
            ->everyFiveMinutes();
        $schedule->command('pending_tier:cron')
            ->everyTenMinutes();
        $schedule->command('investment:cron')
            ->everyFiveMinutes();
        $schedule->command('birthday:cron')
            ->everyFiveMinutes();
        $schedule->command('loan:cron')
            ->everyFiveMinutes();
        $schedule->command('LoanExtend:cron')
            ->everyFiveMinutes();
        $schedule->command('CompanySettimentThreshold:cron')
            ->everyFiveMinutes();
        $schedule->command('pendingpenalty:cron')
            ->everyFiveMinutes();
        // $schedule->command('savings:name')->daily();
        // $schedule->command('savingpack:cron')->everyMinute();
        $schedule->command('maintenance:cron')
            ->daily();
        $schedule->command('pendingmaintenance:charge')
            ->everyFiveMinutes();

        $schedule->command('webhook:cron')
            ->everyFiveMinutes();
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
