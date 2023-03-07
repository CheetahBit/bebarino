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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId');
            $table->string('fromCountry');
            $table->string('fromCity');
            $table->string('fromAddress',120);
            $table->string('toCountry');
            $table->string('toCity');
            $table->string('toAddress',120);
            $table->string('date');
            $table->string('ticket')->nullable();
            $table->string('weight');
            $table->string('price');
            $table->string('desc')->nullable();
            $table->string('messageId')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
