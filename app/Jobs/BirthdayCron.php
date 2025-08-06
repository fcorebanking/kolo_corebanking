<?php
namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BirthdayCron
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
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
            return 0;
        } catch (Exception $e) {
            \logInfo($e, 'Birthday Cron Job as error');
        }

    }

}
