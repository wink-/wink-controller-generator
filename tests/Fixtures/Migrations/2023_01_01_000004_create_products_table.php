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
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable(); // length, width, height
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('attributes')->nullable(); // color, size, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index(['category_id', 'is_active']);
            $table->index('stock_quantity');
            $table->fullText(['name', 'description']);
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