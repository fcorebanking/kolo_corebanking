<?php
namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

ini_set('memory_limit', '500000M');

class BirthdayCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron sends happy birthday to users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \logInfo("Birthday Cron Job is Working currently", 'Cron Job as started');
        try {
            $todayUsers = User::whereMonth('dob', '=', Carbon::now()->format('m'))
                ->whereDay('dob', '=', Carbon::now()->format('d'))
                ->whereYear('last_sent_birthday_date', '<', date('Y'))
                ->get();
            if ($todayUsers->count() > 0) {
                foreach ($todayUsers as $user) {
                    $company                       = $user->company;
                    $setting                       = $company->setting;
                    $channel                       = $setting->birthday_msg_channel;
                    $msg                           = $setting->birthday_msg;
                    $user->last_sent_birthday_date = date('Y-m-d');
                    $user->save();
                    sendSMS($user->id, $msg, true, true, 'Happy Birthday', '', 0, false, $channel, []);
                }
                $return = 'Birthday messages sent successfully';
            } else {
                $return = 'No users with birthdays today or they have already received the message this year.';
            }

        } catch (Exception $e) {
            \logInfo($e, 'Birthday Cron Job as error');
            // send output on failure

        }
    }
}
