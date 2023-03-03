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
            $table->string('fromAddress');
            $table->string('toAddress');
            $table->string('date');
            $table->string('ticket')->nullable();
            $table->string('weight');
            $table->string('price');
            $table->string('desc')->nullable();
            $table->string('messageId')->nullable();
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
