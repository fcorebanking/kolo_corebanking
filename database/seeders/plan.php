<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class plan extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default = [

            [
                'plan_name' => 'Free Trial',
                'desc' => 'Try out the features on our Banking Solution for free',
                'max_customers' => '5',
                'max_staff' => '2',
                'max_branch' => '1',
                'recommended' => '0',
                'duration' => '3',
                'period' => 'day',
                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '0',
                'daily_rates' => '0',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Starter',
                'desc' => 'This is suitable for small scale business owners',
                'max_customers' => '10000',
                'max_staff' => '4',
                'max_branch' => '1',
                'recommended' => '1',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '10',
                'daily_rates' => '0.32',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Starter Plus',
                'desc' => 'This plan is suitable for growing businesses',
                'max_customers' => '10000',
                'max_staff' => '6',
                'max_branch' => '1',
                'recommended' => '0',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '15',
                'daily_rates' => '0.48',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Business',
                'desc' => 'This is suitable for medium scale business owners',
                'max_customers' => '10000',
                'max_staff' => '8',
                'max_branch' => '2',
                'recommended' => '1',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '25',
                'daily_rates' => '0.80',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Company',
                'desc' => 'This plan is suitable for Business expansion',
                'max_customers' => '10000',
                'max_staff' => '12',
                'max_branch' => '3',
                'recommended' => '0',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '50',
                'daily_rates' => '1.61',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Enterpreneur',
                'desc' => 'This plan is suitable for Large scale Businesses',
                'max_customers' => '10000',
                'max_staff' => '25',
                'max_branch' => '5',
                'recommended' => '0',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '70',
                'daily_rates' => '2.25',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],

            [
                'plan_name' => 'Express',
                'desc' => 'This plan is suitable for Ultra Large scale Businesses',
                'max_customers' => '20000',
                'max_staff' => '200',
                'max_branch' => '50',
                'recommended' => '0',
                'duration' => '30',
                'period' => 'day',

                'min_sub_discount' => '0',
                'discount_percent' => '0',
                'plan_amount' => '100',
                'daily_rates' => '3.22',
                'modified_by' => '1',
                'created_at' => now(), 'updated_at' => now()
            ],


        ];

        DB::table('plans')->insert($default);
    }
}
