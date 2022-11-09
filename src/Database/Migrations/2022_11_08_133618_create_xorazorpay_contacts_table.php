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
        Schema::create('xorazorpay_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email')->nullable();
            $table->string('prefix', 10);
            $table->string('mobile', 10);
            $table->string('upi_id', 100)->unique();
            $table->string('razorpay_contact_id', 255)->nullable();
            $table->string('razorpay_fund_account_id', 255)->nullable();
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
        Schema::dropIfExists('xorazorpay_contacts');
    }
};
