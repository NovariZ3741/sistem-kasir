<?php

namespace App\Console\Commands;

use App\Services\AprioriService;
use Illuminate\Console\Command;

class RunApriori extends Command
{
    protected $signature = 'apriori:run
        {--support= : Minimum support dalam persen (mis. 5)}
        {--confidence= : Minimum confidence dalam persen (mis. 50)}
        {--days= : Ambil transaksi N hari terakhir (0 = semua data)}';

    protected $description = 'Hitung aturan asosiasi produk dengan Algoritma Apriori';

    public function handle(AprioriService $apriori): int
    {
        $support = $this->option('support') !== null
            ? (float) $this->option('support') / 100
            : (float) config('apriori.min_support');

        $confidence = $this->option('confidence') !== null
            ? (float) $this->option('confidence') / 100
            : (float) config('apriori.min_confidence');

        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('apriori.period_days');

        if ($support <= 0 || $support > 1 || $confidence <= 0 || $confidence > 1) {
            $this->error('Support dan confidence harus di antara 0 dan 100 persen.');

            return self::FAILURE;
        }

        $from = $days > 0 ? today()->subDays($days - 1) : null;

        $run = $apriori->run($support, $confidence, $from, $days > 0 ? today() : null);

        $this->info("Selesai dalam {$run->duration_ms} ms.");
        $this->table(['Transaksi', 'Min. support count', 'Frequent itemset', 'Aturan'], [[
            $run->transaction_count, $run->min_support_count, $run->itemset_count, $run->rule_count,
        ]]);

        return self::SUCCESS;
    }
}
