<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SMSSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $products = [
            [
                'company_id' => 1,
                'api_url' => 'https://sms.vanso.com/rest/sms/submit',
                'name' => 'Type 1',
                'phone_variable' => 'dest',
                'message_variable' => 'text',
                'request_method_id' => '1',
                'access_setup' => '1',
                'smscharge' => '5',
                'sender_variable' => 'src',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 1,
                'api_url' => 'https://api.ng.termii.com/api/sms/send?api_key=TLOlK0kIgFUFSAueY59uRNvVYdDcwR5WdzdryZtygzNabjAkHnPrgqEL6N3gAP&type=plain&channel=generic',
                'name' => 'Type 2',
                'phone_variable' => 'to',
                'message_variable' => 'sms',
                'request_method_id' => '2',
                'access_setup' => '1',
                'smscharge' => '5',
                'sender_variable' => 'from',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 1,
                'api_url' => 'https://sms.hollatags.com/api/send/?user=shema&pass=shema@123',
                'name' => 'Type 3',
                'phone_variable' => 'to',
                'message_variable' => 'msg',
                'request_method_id' => '2',
                'access_setup' => '1',
                'sender_variable' => 'from',
                'smscharge' => '5',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'company_id' => 1,
                'api_url' => 'https://portal.nigeriabulksms.com/api/?username=dsshema4all@gmail.com&password=WBA@public&verbose=true',
                'name' => 'Type 4',
                'phone_variable' => 'mobiles',
                'message_variable' => 'message',
                'request_method_id' => '2',
                'access_setup' => '1',
                'smscharge' => '5',
                'sender_variable' => 'sender',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 1,
                'api_url' => 'https://www.smszambia.com/smsservice/httpapi?username=CreditsBail&password=denardbb1178**&type=0',
                'name' => 'Type 5',
                'phone_variable' => 'phone',
                'message_variable' => 'msg',
                'request_method_id' => '2',
                'access_setup' => '1',
                'smscharge' => '5',
                'sender_variable' => 'sender_id',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 1,
                'api_url' => 'https://www.bulksmsnigeria.com/api/v1/sms/create?api_token=Sm3qpbTsWBOyhxIzxUH9MporxAZZWxfBMIRCkNC83wJOU40POYHZ01PCAUXk&dnd=1',
                'name' => 'Type 6',
                'phone_variable' => 'to',
                'message_variable' => 'body',
                'request_method_id' => '2',
                'access_setup' => '1',
                'smscharge' => '5',
                'sender_variable' => 'from',
                'created_at' => now(),
                'updated_at' => now()
            ]

        ];

        DB::table('smsapis')->insert($products);
    }
}
