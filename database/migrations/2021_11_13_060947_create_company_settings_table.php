<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');

            $table->unsignedBigInteger('teller_option_id'); // derive from teller_option table
            $table->foreign('teller_option_id')->references('id')->on('teller_options');

            //
            $table->unsignedBigInteger('company_type_id'); // derive from company_types table  
            $table->foreign('company_type_id')->references('id')->on('company_types');


            $table->unsignedBigInteger('esusu_type_id')->default(1)->comment('1=type 1')->nullable(); // derive from esusu_types table


            $table->unsignedBigInteger('plan_id'); // derive from plan table
            $table->foreign('plan_id')->references('id')->on('plans');


            $table->unsignedBigInteger('auto_renewal_id');  // derive from auto_renewals table
            $table->foreign('auto_renewal_id')->references('id')->on('auto_renewals');

            $table->integer('date_control')->default(1)->comment('1= On date to deposit and withdraw,0=off date'); // 
            $table->integer('company_prefix')->default(1)->comment('1= On ,0=off'); // 

            $table->double('sms_charge', 10, 2); //  
            $table->double('maintenance_change_amount', 10, 2); //  

            $table->unsignedBigInteger('sms_charge_frequencie_id'); //
            $table->foreign('sms_charge_frequencie_id')->references('id')->on('charge_customer_statuses');

            $table->integer('withdraw_charge_state')->default(1)->comment('1=percentage or 0=flat'); // percentage or flat

            $table->integer('incoming_transfer_charge_state')->default(0)->comment('1=percentage or 0=flat'); // percentage or flat
            $table->double('incoming_transfer_charge_amount', 9, 2)->nullable(); //  
            $table->double('incoming_transfer_charge_percentage', 9, 2)->nullable(); //  
            $table->double('incoming_transfer_charge_capped_at', 9, 2)->nullable(); //  

            $table->tinyInteger('investment_termination_control')->default(1);
            $table->tinyInteger('internal_account_creation')->default(1);

            $table->double("new_physical_card_charge")->default(1000);
            $table->double("physical_card_maintenance_charge")->default(50);

            $table->tinyInteger('whatsapp_control')->default(2);
            $table->double('whatsapp_charge', 10, 2)->default(0.0);
            $table->integer('dom_account_duration')->default(10);
            $table->double('min_withdraw_charge', 10, 2); // 
            $table->double('max_withdraw_charge', 10, 2); // 

            $table->integer('restrict_customers')->default(1)->comment('1=Restrict or 0= Allows Customers'); // percentage or flat
            $table->integer('bank_transfer_charge_state')->default(1)->comment('1=percentage or 0=flat'); // percentage or flat
            $table->double('min_bank_transfer_charge_or_percentage', 10, 2); // 
            $table->double('max_bank_transfer_charge', 10, 2); // 

            $table->unsignedBigInteger('charge_customer_maintenance_id');  // derive from charge_customer_statuses table
            $table->foreign('charge_customer_maintenance_id')->references('id')->on('charge_customer_statuses');

            $table->integer('preferred_virtual_card_currency')->default(1)->comment('1=NGN or 3=USD'); // percentage or flat

            $table->unsignedBigInteger('preferred_bank_id');  // derive from charge_customer_statuses table
            $table->foreign('preferred_bank_id')->references('id')->on('banks');

            $table->float('teller_commission', 9, 2); // 56666.00

            $table->double('additional_virtual_card_creation_fee', 7, 4); // // ussd charge_amount 
            $table->double('additional_virtual_card_funding_fee', 7, 4); // // ussd charge_amount 
            
            $table->double('additional_pos_charge_percent', 6, 4); // // ussd charge_amount 

            $table->double('pos_charge_capped_at', 6, 4); // // ussd charge_amount 
            $table->double('ussd_charge', 10, 2); // // ussd charge_amount 
            $table->double('minimum_balance', 10, 2)->nullable(); // ussd charge_amount 
            $table->integer('ussd_control')->default(1)->comment('1=On,0=Off'); // // on or off USSD
            $table->integer('allsms_state')->default(0)->comment('1=On,0=Off'); // // on or off SMS for all sms

            $table->double('min_transfer_amount', 10, 2)->nullable(); // // min_transfer_amount delete

            $table->double('registration_fee', 10, 2); //registration_fee
            $table->double('max_localtransfer_amount', 10, 2)->nullable(); // max_transfer_amount delete
            $table->double('max_onlinetransfer_amount', 10, 2)->nullable(); // max_transfer_amount delete
            $table->integer('daily_transfer_limit_count')->nullable(); // daily_transfer_limit_count delete
            $table->tinyInteger('physical_card_withdrawal_charge')->nullable()->comment('0=company,1=customer');; // daily_transfer_limit_count delete

            $table->integer('who_to_pay_incoming_transfer_charge')->default(0)->comment('0=company,1=customer'); // who_to_pay_onlinetxn_charge
            $table->integer('account_approval')->default(0)->comment('0=manual approval,1=automatic approval'); // who_to_pay_onlinetxn_charge

            $table->tinyInteger('can_create_virtual_account')->default(1)->comment('0 - Cannot create, 1 - Can create');  // derive from bank table
            $table->unsignedBigInteger('back_date_status_id');  // derive from bank table
            $table->foreign('back_date_status_id')->references('id')->on('back_date_statuses');
            $table->unsignedBigInteger('smsapi_id')->default(1)->comment('company active smsapi');

            $table->tinyInteger('birthday_msg_channel')->default(1)->comment('1=all,2=sms,3=email');
            $table->text('birthday_msg')->nullable(); // 
            $table->integer('bills_payment_charge')->default(0); // daily_transfer_limit_count delete

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_settings');
    }
}
