<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xorazorpay_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xorazorpay_contact_id');
            $table->foreignId('xorazorpay_request_id');
            $table->string('reference_id', 20)->nullable();
            $table->string('amount', 5);
            $table->tinyInteger('status'); 
            $table->string('transaction_id', 255)->nullable();
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
        Schema::dropIfExists('xorazorpay_payouts');
    }
};
