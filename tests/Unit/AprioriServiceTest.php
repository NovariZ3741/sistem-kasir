<?php

namespace Tests\Unit;

use App\Services\AprioriService;
use PHPUnit\Framework\TestCase;

class AprioriServiceTest extends TestCase
{
    private const ROTI = 1;

    private const SUSU = 2;

    private const SELAI = 3;

    private const KOPI = 4;

    private const GULA = 5;

    /**
     * @param  list<list<int>>  $baskets
     * @return list<array<int, true>>
     */
    private function baskets(array $baskets): array
    {
        return array_map(fn (array $items) => array_fill_keys($items, true), $baskets);
    }

    /**
     * Contoh 5 transaksi: Roti-Susu, Roti-Selai-Susu, Kopi-Gula, Roti-Susu-Gula, Roti-Selai.
     *
     * @return list<array<int, true>>
     */
    private function sampleBaskets(): array
    {
        return $this->baskets([
            [self::ROTI, self::SUSU],
            [self::ROTI, self::SELAI, self::SUSU],
            [self::KOPI, self::GULA],
            [self::ROTI, self::SUSU, self::GULA],
            [self::ROTI, self::SELAI],
        ]);
    }

    public function test_it_finds_frequent_itemsets_and_prunes_infrequent_candidates(): void
    {
        $result = (new AprioriService)->mine($this->sampleBaskets(), 0.4, 0.6);

        $this->assertSame(5, $result['transaction_count']);
        $this->assertSame(2, $result['min_support_count']);

        $itemsets = array_map(fn (array $set) => [$set['items'], $set['count']], $result['itemsets']);

        $this->assertSame([
            [[self::ROTI], 4],
            [[self::SUSU], 3],
            [[self::SELAI], 2],
            [[self::GULA], 2],
            [[self::ROTI, self::SUSU], 3],
            [[self::ROTI, self::SELAI], 2],
        ], $itemsets);
    }

    public function test_it_builds_rules_with_confidence_and_lift(): void
    {
        $result = (new AprioriService)->mine($this->sampleBaskets(), 0.4, 0.6);

        $rules = array_map(fn (array $rule) => [
            $rule['antecedent'],
            $rule['consequent'],
            round($rule['support'], 4),
            round($rule['confidence'], 4),
            round($rule['lift'], 4),
        ], $result['rules']);

        $this->assertSame([
            [[self::SUSU], [self::ROTI], 0.6, 1.0, 1.25],
            [[self::SELAI], [self::ROTI], 0.4, 1.0, 1.25],
            [[self::ROTI], [self::SUSU], 0.6, 0.75, 1.25],
        ], $rules);
    }

    public function test_it_finds_three_item_combinations_when_all_subsets_are_frequent(): void
    {
        $baskets = $this->baskets([
            [self::ROTI, self::SUSU, self::SELAI],
            [self::ROTI, self::SUSU, self::SELAI],
            [self::ROTI, self::SUSU],
            [self::KOPI],
        ]);

        $result = (new AprioriService)->mine($baskets, 0.5, 0.9);

        $this->assertContains(
            ['items' => [self::ROTI, self::SUSU, self::SELAI], 'count' => 2, 'support' => 0.5],
            $result['itemsets'],
        );

        $rules = array_map(fn (array $rule) => [$rule['antecedent'], $rule['consequent']], $result['rules']);
        $this->assertContains([[self::ROTI, self::SELAI], [self::SUSU]], $rules);
        $this->assertNotContains([[self::ROTI], [self::SUSU, self::SELAI]], $rules); // confidence 2/3 < 90%
    }

    public function test_it_stops_at_the_maximum_itemset_size(): void
    {
        $baskets = $this->baskets(array_fill(0, 3, [self::ROTI, self::SUSU, self::SELAI]));

        $result = (new AprioriService)->mine($baskets, 0.5, 0.5, 2);

        $this->assertSame(2, max(array_map(fn (array $set) => count($set['items']), $result['itemsets'])));
    }

    public function test_it_returns_empty_result_without_transactions(): void
    {
        $result = (new AprioriService)->mine([], 0.1, 0.5);

        $this->assertSame(0, $result['transaction_count']);
        $this->assertSame([], $result['itemsets']);
        $this->assertSame([], $result['rules']);
    }
}
