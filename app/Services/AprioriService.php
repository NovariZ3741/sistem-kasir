<?php

namespace App\Services;

use App\Models\AprioriRun;
use App\Models\Product;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implementasi Algoritma Apriori untuk Market Basket Analysis.
 *
 * Alur:
 *  1. Ambil data transaksi -> setiap transaksi menjadi "keranjang" berisi product_id unik.
 *  2. Cari frequent itemset secara bertahap (L1, L2, L3, ...):
 *     - Kandidat Ck dibentuk dari gabungan (join) dua itemset L(k-1) yang prefix-nya sama.
 *     - Kandidat dipangkas (prune) bila ada subset berukuran k-1 yang tidak frequent (prinsip Apriori).
 *     - Hitung support tiap kandidat, simpan yang memenuhi minimum support.
 *  3. Bentuk aturan asosiasi A -> B dari setiap frequent itemset (ukuran >= 2),
 *     simpan yang memenuhi minimum confidence, lalu hitung lift-nya.
 */
class AprioriService
{
    private const EPSILON = 1e-9;

    /**
     * Jalankan Apriori pada data transaksi di database lalu simpan hasilnya.
     */
    public function run(
        float $minSupport,
        float $minConfidence,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        ?User $user = null,
    ): AprioriRun {
        $started = microtime(true);

        $baskets = $this->loadBaskets($from, $to);
        $result = $this->mine($baskets, $minSupport, $minConfidence, (int) config('apriori.max_itemset_size', 4));
        $names = $this->productNames();

        $label = fn (array $items) => implode(', ', array_map(fn ($id) => $names[$id] ?? "Produk #{$id}", $items));

        $run = DB::transaction(function () use ($result, $minSupport, $minConfidence, $from, $to, $user, $label, $started) {
            $run = AprioriRun::create([
                'user_id' => $user?->id,
                'min_support' => $minSupport,
                'min_confidence' => $minConfidence,
                'date_from' => $from?->toDateString(),
                'date_to' => $to?->toDateString(),
                'transaction_count' => $result['transaction_count'],
                'min_support_count' => $result['min_support_count'],
                'itemset_count' => count($result['itemsets']),
                'rule_count' => count($result['rules']),
                'duration_ms' => 0,
            ]);

            $itemsetRows = array_map(fn (array $set) => [
                'apriori_run_id' => $run->id,
                'size' => count($set['items']),
                'items' => json_encode($set['items']),
                'label' => $label($set['items']),
                'support_count' => $set['count'],
                'support' => round($set['support'], 6),
            ], $result['itemsets']);

            foreach (array_chunk($itemsetRows, 200) as $chunk) {
                DB::table('apriori_itemsets')->insert($chunk);
            }

            $ruleRows = array_map(fn (array $rule) => [
                'apriori_run_id' => $run->id,
                'antecedent' => json_encode($rule['antecedent']),
                'consequent' => json_encode($rule['consequent']),
                'antecedent_label' => $label($rule['antecedent']),
                'consequent_label' => $label($rule['consequent']),
                'support_count' => $rule['count'],
                'support' => round($rule['support'], 6),
                'confidence' => round($rule['confidence'], 6),
                'lift' => round($rule['lift'], 4),
            ], $result['rules']);

            foreach (array_chunk($ruleRows, 200) as $chunk) {
                DB::table('association_rules')->insert($chunk);
            }

            $run->update(['duration_ms' => (int) round((microtime(true) - $started) * 1000)]);

            return $run;
        });

        $this->pruneOldRuns();

        return $run;
    }

    /**
     * Ubah data transaksi menjadi daftar keranjang.
     *
     * @return list<array<int, true>> setiap keranjang berbentuk [product_id => true]
     */
    public function loadBaskets(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $rows = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereNotNull('transaction_items.product_id')
            ->when($from, fn ($q) => $q->whereDate('transactions.created_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('transactions.created_at', '<=', $to->toDateString()))
            ->select('transaction_items.transaction_id', 'transaction_items.product_id')
            ->toBase()
            ->cursor();

        $baskets = [];
        foreach ($rows as $row) {
            $baskets[$row->transaction_id][(int) $row->product_id] = true;
        }

        return array_values($baskets);
    }

    /**
     * Inti Algoritma Apriori (tanpa database, mudah diuji).
     *
     * @param  list<array<int, true>>  $baskets
     * @return array{
     *     transaction_count: int,
     *     min_support_count: int,
     *     itemsets: list<array{items: list<int>, count: int, support: float}>,
     *     rules: list<array{antecedent: list<int>, consequent: list<int>, count: int, support: float, confidence: float, lift: float}>
     * }
     */
    public function mine(array $baskets, float $minSupport, float $minConfidence, int $maxSize = 0): array
    {
        $n = count($baskets);
        $result = ['transaction_count' => $n, 'min_support_count' => 0, 'itemsets' => [], 'rules' => []];

        if ($n === 0) {
            return $result;
        }

        // Support minimum dalam bentuk jumlah transaksi
        $minCount = max(1, (int) ceil($minSupport * $n - self::EPSILON));
        $result['min_support_count'] = $minCount;

        // ---- Iterasi 1: hitung support setiap produk (C1 -> L1)
        $counts = [];
        foreach ($baskets as $basket) {
            foreach ($basket as $item => $_) {
                $counts[$item] = ($counts[$item] ?? 0) + 1;
            }
        }

        $current = []; // Lk: list of [items, count]
        foreach ($counts as $item => $count) {
            if ($count >= $minCount) {
                $current[] = [[(int) $item], $count];
            }
        }

        $frequent = []; // key "1,5,9" => support count (semua level)
        $k = 1;

        while ($current) {
            usort($current, fn ($a, $b) => $a[0] <=> $b[0]);

            foreach ($current as [$items, $count]) {
                $frequent[$this->key($items)] = $count;
                $result['itemsets'][] = ['items' => $items, 'count' => $count, 'support' => $count / $n];
            }

            if ($maxSize > 0 && $k >= $maxSize) {
                break;
            }

            // ---- Bentuk kandidat C(k+1) dari Lk (join + prune)
            $candidates = $this->generateCandidates(array_column($current, 0), $frequent);
            if (! $candidates) {
                break;
            }

            // ---- Hitung support kandidat dengan memindai seluruh transaksi
            $candidateCounts = array_fill(0, count($candidates), 0);
            foreach ($baskets as $basket) {
                if (count($basket) <= $k) {
                    continue; // keranjang terlalu kecil untuk memuat kandidat berukuran k+1
                }
                foreach ($candidates as $i => $items) {
                    foreach ($items as $item) {
                        if (! isset($basket[$item])) {
                            continue 2;
                        }
                    }
                    $candidateCounts[$i]++;
                }
            }

            $current = [];
            foreach ($candidates as $i => $items) {
                if ($candidateCounts[$i] >= $minCount) {
                    $current[] = [$items, $candidateCounts[$i]];
                }
            }

            $k++;
        }

        $result['rules'] = $this->generateRules($result['itemsets'], $frequent, $n, $minConfidence);

        return $result;
    }

    /**
     * Join: gabungkan dua itemset berukuran k yang k-1 item pertamanya sama.
     * Prune: buang kandidat yang memiliki subset tidak frequent.
     *
     * @param  list<list<int>>  $itemsets  terurut
     * @param  array<string, int>  $frequent
     * @return list<list<int>>
     */
    private function generateCandidates(array $itemsets, array $frequent): array
    {
        $candidates = [];
        $total = count($itemsets);

        for ($i = 0; $i < $total; $i++) {
            $a = $itemsets[$i];
            $prefix = array_slice($a, 0, -1);

            for ($j = $i + 1; $j < $total; $j++) {
                $b = $itemsets[$j];

                if (array_slice($b, 0, -1) !== $prefix) {
                    break; // karena terurut, prefix berikutnya pasti berbeda
                }

                $candidate = [...$a, end($b)];

                if ($this->allSubsetsFrequent($candidate, $frequent)) {
                    $candidates[] = $candidate;
                }
            }
        }

        return $candidates;
    }

    private function allSubsetsFrequent(array $candidate, array $frequent): bool
    {
        foreach (array_keys($candidate) as $skip) {
            $subset = $candidate;
            unset($subset[$skip]);

            if (! isset($frequent[$this->key(array_values($subset))])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bentuk aturan A -> B dari setiap frequent itemset berukuran >= 2.
     *
     * confidence(A -> B) = support(A ∪ B) / support(A)
     * lift(A -> B)       = confidence(A -> B) / support(B)
     */
    private function generateRules(array $itemsets, array $frequent, int $n, float $minConfidence): array
    {
        $rules = [];

        foreach ($itemsets as $set) {
            $items = $set['items'];
            $size = count($items);

            if ($size < 2) {
                continue;
            }

            // Semua subset tidak kosong dan bukan himpunan penuh sebagai antecedent
            for ($mask = 1; $mask < (1 << $size) - 1; $mask++) {
                $antecedent = [];
                $consequent = [];

                foreach ($items as $bit => $item) {
                    if ($mask & (1 << $bit)) {
                        $antecedent[] = $item;
                    } else {
                        $consequent[] = $item;
                    }
                }

                $confidence = $set['count'] / $frequent[$this->key($antecedent)];

                if ($confidence + self::EPSILON < $minConfidence) {
                    continue;
                }

                $consequentSupport = $frequent[$this->key($consequent)] / $n;

                $rules[] = [
                    'antecedent' => $antecedent,
                    'consequent' => $consequent,
                    'count' => $set['count'],
                    'support' => $set['support'],
                    'confidence' => $confidence,
                    'lift' => $confidence / $consequentSupport,
                ];
            }
        }

        usort($rules, fn ($a, $b) => [$b['confidence'], $b['lift'], $b['support']]
            <=> [$a['confidence'], $a['lift'], $a['support']]);

        return $rules;
    }

    private function key(array $items): string
    {
        return implode(',', $items);
    }

    /**
     * Nama produk saat ini; produk yang sudah dihapus memakai nama snapshot di transaksi.
     *
     * @return array<int, string>
     */
    private function productNames(): array
    {
        $snapshot = TransactionItem::query()
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->pluck('product_name', 'product_id')
            ->all();

        return Product::query()->pluck('name', 'id')->all() + $snapshot;
    }

    private function pruneOldRuns(): void
    {
        $keep = max(1, (int) config('apriori.keep_runs', 10));

        $oldIds = AprioriRun::query()->latest('id')->skip($keep)->take(PHP_INT_MAX)->pluck('id');

        if ($oldIds->isNotEmpty()) {
            AprioriRun::query()->whereIn('id', $oldIds)->delete();
        }
    }
}
