<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGtbWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // {
        //     "transaction_reference": "TQPHCL83R/1656946647630_1",
        //     "virtual_account_number": "9392728245",
        //     "principal_amount": "3000.00",
        //     "settled_amount": "2985.00",
        //     "fee_charged": "15.00",
        //     "transaction_date": "2022-07-04T14:57:27.000Z",
        //     "customer_identifier": "TTPYY",
        //     "transaction_indicator": "C",
        //     "remarks": "Transfer from Ifeanyi Igweh | [TTPYY] to sandbox sandbox",
        //     "currency": "NGN",
        //     "channel": "virtual-account",
        //     "meta": {
        //       "freeze_transaction_ref": null,
        //       "reason_for_frozen_transaction": null
        //     },
        //     "encrypted_body": "l7o4180lvr+WbSvO8mr6HP89p9hpLpaX86Q94IA9M0UycNr6EDKjlOklICY2ueDdf3J9Yak7MJzYmSqC99B+XujY6WuzJyYWysKMlmZyeyz5UGsPZ00Y1Q3lbU5pWL88QIWol0PQL0vBzIQF7lekgs8YXX7NihmAJaphk/u8D044z1y31Hirn5VchzwkOfiYMBDJa6FLZV5X1lpH8KUnZVZiwMwhQBNyMgNOxyP2SP99yJwBuUErswOHEuufkFoeMe10MUAvoWxtr1TFN5CCtb3BX0E5xWKOGps3TF+qq0YZ5xwomeRJowLpKF7A6TNBXovpmN1dZHes6NoL3d8PFvfVRLueeXsWfmKGlpH0B54lFaLHUNnJMLNwEdXtYMu06up5UuCSZcyYnIgj31l7DD791M8ZWJp1e1rHzfROqyxu0JR8LLfHQjIZoBd0snerH2SYgOfmD/RnPsf8/UP2MO6Xp9bIwjnAmYAUuOYRg02C93H+U8XH14JeFls5hjxggWotT4lfNOFefEgngBwAwEEKI5v/hsK6mRUDrCtgNT76NIKamDaWBEC+zbgsRM0scbv7wEb4Yv/1YeC8POfB/87oDlrNobWCmdPzdsSJIpY="
        //   }

        Schema::create('gtb_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference');
            $table->string('virtual_account_number');
            $table->double('principal_amount', 12, 2);
            $table->double('settled_amount', 12, 2);
            $table->double('fee_charged', 10, 2);
            $table->date('transaction_date');
            $table->string('customer_identifier');
            $table->string('transaction_indicator');
            $table->string('remarks');
            $table->string('currency');
            $table->string('channel');
            $table->string('encrypted_body');
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
        Schema::dropIfExists('gtb_webhooks');
    }
}
