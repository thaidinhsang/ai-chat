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
        Schema::create('customer_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('product_code')->default('combo')->index();
            $table->decimal('price', 12, 2); // deal price cho KH này
            $table->string('currency', 8)->default('VND');
            $table->timestamp('expires_at')->nullable();
            $table->string('source')->nullable(); // ai-bot / agent / campaign...
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'product_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_deals');
    }
};
