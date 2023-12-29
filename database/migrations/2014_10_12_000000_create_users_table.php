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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->enum('role',['user','vendor']);
            $table->integer('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('address')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('country_name')->nullable();
            $table->integer('state_id')->nullable();
            $table->string('state_name')->nullable();
            $table->integer('city_id')->nullable();
            $table->string('city_name')->nullable();
            $table->integer('zip_code')->nullable();
            $table->string('chatUserId')->nullable();
            $table->enum('is_published',['0','1'])->default('0');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
