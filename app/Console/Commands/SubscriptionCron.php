<?php
namespace App\Console\Commands;

use App\Models\sub;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\WebhookServer\WebhookCall;

class SubscriptionCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbscription:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        

            $generator = (function ()  {
                foreach (sub::orderBy('id')->cursor() as $record) {
                    yield $this->processRecord($record);
                }
            })();

            // Consume the generator
            iterator_to_array($generator);
    }

    public function processRecord($record)
    {
        // Example processing logic
        DB::connection('reports')->table('users')->updateOrInsert(
            ['id'        => $record->id,
                'created_at' => $record->created_at,
            ],

            [
                'user_id'            => $record->id,
                'username'           => $record->username,
                'name'               => $record->name,
                'dob'                => $record->dob,
                'gender'             => $record->gender,
                'tell'               => $record->tell,
                'email'              => $record->email ?? "john@mylottohub.com",
                'date_created_at'    => $record->date ?? Carbon::parse($record->date)->format('Y-m-d H:i:s'),
                'wallet'             => $record->wallet,
                'user_type'          => $record->type,
                'wwallet'            => $record->wwallet,
                'bwallet'            => $record->bwallet,
                'gl_bwallet'         => $record->gl_bwallet,
                'sl_bwallet'         => $record->sl_bwallet,
                'gh_bwallet'         => $record->gh_bwallet,
                'lm_bwallet'         => $record->lm_bwallet,
                'we_bwallet'         => $record->we_bwallet,
                'bank'               => $record->bank,
                'bname'              => $record->bname,
                'accno'              => $record->accno,
                'accname'            => $record->accname,
                'state'              => $record->state,
                'pix'                => $record->pix,
                'lga'                => $record->lga,
                'status'             => $record->status,
                'country'            => $record->country,
                'referral'           => $record->ref,
                'user_andriod_token' => $record->games,
                'first_name'         => $record->first_name,
                'last_name'          => $record->last_name,
                'address'            => $record->address,
                'created_at'         => $record->created_at,
                'updated_at'         => $record->updated_at,
            ]

        );
    }
}
