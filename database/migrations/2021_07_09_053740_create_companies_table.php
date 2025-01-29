<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('company_name', 100); // company NAme
            $table->string('short_name', 12); //wba3
            $table->string('transaction_pin', 4)->nullable();
            $table->string('company_route', 20)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();  //constrained to currencies type         
            $table->foreign("currency_id")->references("id")->on("currencies");
            $table->unsignedBigInteger('countries_id');  //constrained to currencies type                   
            $table->foreign("countries_id")->references("id")->on("contries");
            $table->unsignedBigInteger('verification_status_id')->default(3);  //constrained to currencies type                   
            $table->foreign("verification_status_id")->references("id")->on("verification_statuses");
            $table->string('address');
            $table->string('cac_no', 11)->nullable();
            $table->string('referer_phone', 15)->nullable();
            $table->string('company_phone', 15)->nullable();
            $table->string('company_email', 70)->nullable();
            $table->integer('ussd_string')->nullable();
            $table->string('license_no', 50)->nullable();
            $table->string('company_reference')->nullable();
            $table->string('license_certificate_image')->nullable();
            $table->string('logo')->nullable();
            $table->tinyInteger('allow_transaction_setting')->default(1)->comment("1 - Allow All transactions, 2 - Block Transaction Transfer, 3 - Block All Transactions, 4 - Fraud");
            $table->longText('signature')->nullable();

            $table->unsignedBigInteger('status_id')->nullable()->default(7);
            $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table
            $table->unsignedBigInteger('developer_api_status')->default(0)->comment('0 - Not enabled, 1 - Enabled');

            $table->unsignedBigInteger('partner_id')->nullable(); // user derive from users table
            $table->unsignedBigInteger('created_by')->nullable(); // user table constrain
            $table->unsignedBigInteger('modified_by')->nullable(); // user table constrain
            $table->foreign("modified_by")->references("id")->on("users");
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
        Schema::dropIfExists('company');
    }
}
