<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu kali proses perhitungan Apriori beserta parameternya
        Schema::create('apriori_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = dijalankan scheduler
            $table->decimal('min_support', 6, 4);       // 0.0500 = 5%
            $table->decimal('min_confidence', 6, 4);    // 0.5000 = 50%
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->unsignedInteger('transaction_count')->default(0);
            $table->unsignedInteger('min_support_count')->default(0);
            $table->unsignedInteger('itemset_count')->default(0);
            $table->unsignedInteger('rule_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
        });

        // Frequent itemset (L1, L2, L3, ...) hasil tiap iterasi
        Schema::create('apriori_itemsets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apriori_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('size');        // k pada Lk
            $table->json('items');                      // [product_id, ...]
            $table->text('label');                      // "Roti, Susu"
            $table->unsignedInteger('support_count');
            $table->decimal('support', 8, 6);
        });

        // Aturan asosiasi A -> B
        Schema::create('association_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apriori_run_id')->constrained()->cascadeOnDelete();
            $table->json('antecedent');                 // A: [product_id, ...]
            $table->json('consequent');                 // B: [product_id, ...]
            $table->text('antecedent_label');
            $table->text('consequent_label');
            $table->unsignedInteger('support_count');
            $table->decimal('support', 8, 6);
            $table->decimal('confidence', 8, 6);
            $table->decimal('lift', 10, 4);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_rules');
        Schema::dropIfExists('apriori_itemsets');
        Schema::dropIfExists('apriori_runs');
    }
};
