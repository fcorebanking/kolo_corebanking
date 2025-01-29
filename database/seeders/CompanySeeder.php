<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $companies = [
            [
                "company_name" => "Wired Banking Africa",
                "short_name" => "wba",
                "company_route" => "company_route",
                "currency_id" => 1,
                "countries_id" => 1,
                "address" => "Galadimawa Abuja",
                "transaction_pin" => "7777",
                "cac_no" => "JDKS8992",
                "license_no" => "90340DKF",
                "company_reference" => generateAccountReference(),
                "logo" => "public/company/profile/check_logo.png",
                "license_certificate_image" => "public/company/profile/check_logo.png",
                "status_id" => "7",
                "verification_status_id" => "1",
                "created_by" => "1",
                "created_at" => Carbon::now()
            ],
            [
                "company_name" => "City Second Limited",
                "short_name" => "csl",
                "company_route" => "company_route",
                "currency_id" => 1,
                "countries_id" => 1,
                "address" => "No 18 Kirika road Port harcourt",
                "cac_no" => "SK394FJFK9",
                "transaction_pin" => "7777",
                "license_no" => "90340DKF",
                "company_reference" => generateAccountReference(),
                "logo" => "public/company/profile///check_logo.png",
                "license_certificate_image" => "public/company/profile/check_logo.png",
                "status_id" => "7",
                "verification_status_id" => "1",
                "created_by" => "2",
                "created_at" => Carbon::now()
            ],
            [
                "company_name" => "Exotic Juice Microfinance Ltd",
                "short_name" => "ejm",
                "company_route" => "company_route",
                "currency_id" => 2,
                "countries_id" => 2,
                "transaction_pin" => "7777",
                "address" => "No 27 Kazim road Lusaka Zambia",
                "cac_no" => "DK3029394",
                "license_no" => "UYJHK5949",
                "company_reference" => generateAccountReference(),
                "logo" => "public/company/profile///check_logo.png",
                "license_certificate_image" => "public/company/profile/check_logo.png",
                "status_id" => "7",
                "verification_status_id" => "1",

                "created_by" => "3",
                "created_at" => Carbon::now()
            ]
        ];

        $user = DB::table('company')->insert($companies);
    }
}
