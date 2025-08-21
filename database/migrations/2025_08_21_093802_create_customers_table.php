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
        Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('external_id')->index(); // id từ BotCake/Pancake
        $table->string('page_id')->nullable()->index();
        $table->string('name')->nullable();
        $table->string('phone')->nullable();
        $table->json('meta')->nullable();
        $table->timestamps();
        $table->unique(['external_id','page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
