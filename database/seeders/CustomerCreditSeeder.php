<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerCreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer_credits = [
            ["company_id"=>1,"name"=>"Karma-Loan-Check","method"=>"GET","url"=>"https://adjutor.lendsqr.com/verification/karma/","authorization"=>"Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1dWlkIjoiYTk4NDIyZWYtNjA2Ni00NmJjLWIzZmUtZjQwYzIxNTMzMTdlIiwiaWF0IjoxNjYwMzEzNjQ3fQ.cRMhqWNGcX4MxM0pUVGi-DLVb4XDDIsnMOLCo5B-uWA","body"=>"","callCost"=>"50","status"=>1,"access"=>1],
            ["company_id"=>1,"name"=>"CRC-Credit-Bureau","method"=>"GET","url"=>"https://adjutor.lendsqr.com/credit-check/crc/","authorization"=>"Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1dWlkIjoiYTk4NDIyZWYtNjA2Ni00NmJjLWIzZmUtZjQwYzIxNTMzMTdlIiwiaWF0IjoxNjYwMzEzNjQ3fQ.cRMhqWNGcX4MxM0pUVGi-DLVb4XDDIsnMOLCo5B-uWA","body"=>"","callCost"=>"150","status"=>1,"access"=>1],
            ["company_id"=>1,"name"=>"EcoSystem","method"=>"GET","url"=>"https://adjutor.lendsqr.com/verification/ecosystem/","authorization"=>"Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1dWlkIjoiYTk4NDIyZWYtNjA2Ni00NmJjLWIzZmUtZjQwYzIxNTMzMTdlIiwiaWF0IjoxNjYwMzEzNjQ3fQ.cRMhqWNGcX4MxM0pUVGi-DLVb4XDDIsnMOLCo5B-uWA","body"=>"","callCost"=>"50","status"=>1,"access"=>1],
        ];
        DB::table('customer_credits')->insert($customer_credits);
    }
}
