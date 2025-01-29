<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->index('uuid');
                $table->string('first_name'); // user firtname
                $table->string('last_name'); // user lastname

                $table->string('phone')->nullable()->index('phone');  // user phone
                $table->string('profile_pic')->nullable();  // user profile pic

                $table->string('username')->nullable()->index('username');  // username login
                $table->string('pin')->nullable();  // user transaction Pin

                $table->string('bvn', 11)->nullable();  // user bvn
                $table->unsignedBigInteger('bvn_status_id')->nullable();
                $table->foreign('bvn_status_id')->references('id')->on('statuses'); // check if user have input 
                $table->string('nin', 11)->nullable();

                $table->string('email')->nullable(); //->unique(); 
                $table->date('dob')->nullable();

                $table->string('signature')->nullable();

                $table->string('address')->nullable();
                $table->string('sex', 10)->nullable();
                $table->string('otp', 4)->nullable();
                $table->dateTime('otp_expiration_date', $precision = 0)->nullable();

                $table->string('virtual_account')->nullable();
                $table->string('account_reference')->nullable()->index('account_reference');

                $table->unsignedBigInteger('bank_id')->nullable();

                $table->string('validID_file')->nullable();  // User valid ID document
                $table->string('utilityBillID_file')->nullable();  // Utility document
                $table->string('otherID_file')->nullable();  // other ID document

                $table->timestamp('email_verified_at')->nullable();
                $table->integer('company_id')->nullable()->index('company_id');
                // $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade'); // user company ID

                $table->unsignedBigInteger('user_type_id')->nullable(); //constrain to transaction_type table
                $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('cascade');
                $table->integer("login_fails");
                $table->integer("transaction_pin_fails")->nullable();
                $table->integer("last_ip_address")->nullable();
                $table->integer("balance_fails");

                $table->integer("payroll_setup")->default(0);

                $table->unsignedBigInteger('status_id')->nullable()->default(7);
                $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table

                $table->string('account_restrictions')->nullable();
                $table->string('verification_hash')->nullable();

                $table->unsignedBigInteger('whatsapp_status_id')->nullable();
                $table->foreign('whatsapp_status_id')->references('id')->on('ussd_status');

                $table->unsignedBigInteger('ussd_status_id')->nullable();
                $table->foreign('ussd_status_id')->references('id')->on('ussd_status');

                $table->unsignedBigInteger('phone_verification_status_id')->nullable();

                $table->unsignedBigInteger('account_officer_id')->nullable();
                $table->foreign('account_officer_id')->references('id')->on('users');

                $table->unsignedBigInteger('user_card_status_id')->default(1);
                $table->foreign('user_card_status_id')->references('id')->on('user_card_statuses');

                // $table->foreignId('account_typeid')->references('id')->on('account_type'); // user account type ID if it is personal or Company account (constrain from account_type table)

                $table->date('reg_date')->nullable();

                $table->string('password')->index('password');
                $table->string('type', 20);
                $table->boolean('sms');
                $table->boolean('whatsapp');
                $table->dateTime('last_login')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('old_wba_user')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Schema::table('users', function (Blueprint $table) {

        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');

        Schema::dropColumn('bvn_status_id');

        Schema::enableForeignKeyConstraints();
    }
}
