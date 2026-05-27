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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
        
            // Store 49.99 as 4999.
            $table->unsignedBigInteger('price');
        
            $table->string('title', 255);
            $table->text('content');
        
            $table->string('image')->nullable();
        
            $table->boolean('is_active')->default(true);
        
            $table->timestamps();
        
            $table->index(['is_active', 'id']);
            $table->index(['price', 'id']);
            $table->index(['created_at', 'id']);
        
            $table->fullText(['title', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
