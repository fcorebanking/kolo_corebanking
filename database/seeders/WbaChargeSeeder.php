<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WbaChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $charges = [
            ["title" => "Bank Transfer Charge",  "charge_by" => "0",  "charge" => "20", "minimum_charge_amount" => "20",  "maximum_charge_amount" => "250"],
            ["title" => "Kuda Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "1", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "1000"],
            ["title" => "Coronation Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "30",  "maximum_charge_amount" => "200"],
            ["title" => "Wema Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "30",  "maximum_charge_amount" => "200"],
            ["title" => "Sparkle Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "30",  "maximum_charge_amount" => "200"],
            ["title" => "Asset Matrix Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "200"],
            ["title" => "VFD Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "50",  "maximum_charge_amount" => "300"],
            ["title" => "GTB Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "1000"],
            ["title" => "Providus Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "0.75", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "200"],
            ["title" => "Monnify/Moniepoint Incoming Transfer Charge",  "charge_by" => "1",  "charge" => "1", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "500"],
            ["title" => "POS Transfer Charge",  "charge_by" => "1",  "charge" => "6.5", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "85"],
            ["title" => "Cable TV Charge",  "charge_by" => "0",  "charge" => "20", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Electricity Charge",  "charge_by" => "0",  "charge" => "20", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "USSD Charge",  "charge_by" => "0",  "charge" => "6.5", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Whatsapp Charge",  "charge_by" => "0",  "charge" => "1.5", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "SMS Charge",  "charge_by" => "0",  "charge" => "3", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Physical Card Request Charge",  "charge_by" => "0",  "charge" => "1100", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Physical Card Maintenance Charge",  "charge_by" => "0",  "charge" => "50", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "CRC Credit Bureau Charge",  "charge_by" => "0",  "charge" => "90", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "CRC Credit Bureau Charge",  "charge_by" => "0",  "charge" => "90", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "First Central Search Charge",  "charge_by" => "0",  "charge" => "100", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Eco System Search Charge",  "charge_by" => "0",  "charge" => "30", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Karma Search Charge",  "charge_by" => "0",  "charge" => "30", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"],
            ["title" => "Topup Beting",  "charge_by" => "0",  "charge" => "30", "minimum_charge_amount" => "0",  "maximum_charge_amount" => "0"]
        ];

        DB::table('wba_charges')->insert($charges);
    }
}
