<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->date('expired_at')->nullable();   // null = tidak ada kadaluarsa
            $table->date('received_at');
            $table->timestamps();

            $table->index(['product_id', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};