<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id')->references('id')->on('vendor_services')->onDelete('cascade');
            $table->unsignedBigInteger('slot_id');
            $table->foreign('slot_id')->references('id')->on('vendor_slots')->onDelete('cascade');
            $table->unsignedBigInteger('review_id');
            $table->foreign('review_id')->references('id')->on('vendor_reviews')->onDelete('cascade');
            $table->double('amount', 10, 2);
            $table->double('disbursement_fee', 10, 2)->default(10.00);
            $table->double('processing_fee', 10, 2)->nullable();
            $table->string('address');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->enum('order_status',['1','2','3','4','5','6','7','8'])->default('1');
            $table->date('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
