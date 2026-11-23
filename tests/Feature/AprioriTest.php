<?php

namespace Tests\Feature;

use App\Models\AprioriRun;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\AprioriDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AprioriTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $roti;

    private Product $susu;

    private Product $kopi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->roti = Product::factory()->create(['name' => 'Roti Tawar']);
        $this->susu = Product::factory()->create(['name' => 'Susu UHT']);
        $this->kopi = Product::factory()->create(['name' => 'Kopi Bubuk']);
    }

    /**
     * @param  list<Product>  $products
     */
    private function sale(array $products, ?Carbon $at = null): Transaction
    {
        $transaction = new Transaction([
            'invoice_no' => 'TEST-'.fake()->unique()->numerify('######'),
            'user_id' => $this->admin->id,
            'subtotal' => 0,
            'total' => 0,
            'paid' => 0,
        ]);
        $transaction->created_at = $at ?? now();
        $transaction->save();

        foreach ($products as $product) {
            $transaction->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => 1,
                'subtotal' => $product->price,
            ]);
        }

        return $transaction;
    }

    private function seedPattern(): void
    {
        $this->sale([$this->roti, $this->susu]);
        $this->sale([$this->roti, $this->susu]);
        $this->sale([$this->roti, $this->susu]);
        $this->sale([$this->roti]);
        $this->sale([$this->kopi]);
    }

    public function test_admin_can_run_analysis_and_rules_are_stored_with_product_names(): void
    {
        $this->seedPattern();

        $response = $this->actingAs($this->admin)->post(route('admin.apriori.store'), [
            'min_support' => 40,
            'min_confidence' => 60,
        ]);

        $run = AprioriRun::latestRun();
        $response->assertRedirect(route('admin.apriori.show', $run));

        $this->assertSame(5, $run->transaction_count);
        $this->assertSame(2, $run->min_support_count);
        $this->assertSame($this->admin->id, $run->user_id);
        $this->assertDatabaseHas('association_rules', [
            'apriori_run_id' => $run->id,
            'antecedent_label' => 'Susu UHT',
            'consequent_label' => 'Roti Tawar',
            'confidence' => 1,
        ]);

        $this->actingAs($this->admin)->get(route('admin.apriori.show', $run))
            ->assertOk()
            ->assertSeeText('Susu UHT')
            ->assertSeeText('Roti Tawar');
    }

    public function test_analysis_only_uses_transactions_in_selected_period(): void
    {
        $this->seedPattern();
        $this->sale([$this->kopi, $this->susu], now()->subDays(30));

        $this->actingAs($this->admin)->post(route('admin.apriori.store'), [
            'min_support' => 10,
            'min_confidence' => 10,
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $run = AprioriRun::latestRun();
        $this->assertSame(5, $run->transaction_count);
        $this->assertDatabaseMissing('association_rules', ['apriori_run_id' => $run->id, 'antecedent_label' => 'Kopi Bubuk']);
    }

    public function test_analysis_parameters_are_validated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.apriori.store'), [
            'min_support' => 0,
            'min_confidence' => 150,
            'date_from' => '2026-11-10',
            'date_to' => '2026-11-01',
        ])->assertSessionHasErrors(['min_support', 'min_confidence', 'date_to']);

        $this->assertSame(0, AprioriRun::count());
    }

    public function test_cashier_cannot_access_analysis_page(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($kasir)->get(route('admin.apriori.index'))->assertForbidden();
        $this->actingAs($kasir)->post(route('admin.apriori.store'), ['min_support' => 10, 'min_confidence' => 50])
            ->assertForbidden();
    }

    public function test_pos_receives_only_positive_rules_from_latest_run(): void
    {
        $this->seedPattern();
        $this->artisan('apriori:run', ['--support' => 40, '--confidence' => 50, '--days' => 0])->assertSuccessful();

        $kasir = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($kasir)->get(route('pos.index'))
            ->assertOk()
            ->assertViewHas('recommendationRules', function (array $rules): bool {
                return $rules !== []
                    && collect($rules)->every(fn (array $rule) => $rule['lift'] > 1)
                    && collect($rules)->contains(fn (array $rule) => $rule['a'] === [$this->susu->id] && $rule['c'] === [$this->roti->id]);
            });
    }

    public function test_old_runs_are_pruned_beyond_configured_limit(): void
    {
        config(['apriori.keep_runs' => 2]);
        $this->seedPattern();

        foreach (range(1, 3) as $_) {
            $this->artisan('apriori:run', ['--days' => 0])->assertSuccessful();
        }

        $this->assertSame(2, AprioriRun::count());
        $this->assertDatabaseCount('association_rules', (int) AprioriRun::sum('rule_count'));
    }

    public function test_admin_can_delete_a_run(): void
    {
        $this->seedPattern();
        $this->artisan('apriori:run', ['--days' => 0])->assertSuccessful();
        $run = AprioriRun::latestRun();

        $this->actingAs($this->admin)->delete(route('admin.apriori.destroy', $run))
            ->assertRedirect(route('admin.apriori.index'));

        $this->assertModelMissing($run);
        $this->assertDatabaseCount('association_rules', 0);
    }

    public function test_demo_seeder_produces_meaningful_patterns(): void
    {
        $this->seed(AprioriDemoSeeder::class);

        $this->artisan('apriori:run', ['--days' => 0])->assertSuccessful();

        $run = AprioriRun::latestRun();
        $this->assertSame(300, $run->transaction_count);
        $this->assertTrue($run->rules()->where('antecedent_label', 'Indomie Goreng')->where('consequent_label', 'Telur Ayam 1kg')->exists());
    }

    public function test_dashboard_shows_strongest_patterns(): void
    {
        $this->seedPattern();
        $this->artisan('apriori:run', ['--support' => 40, '--confidence' => 50, '--days' => 0])->assertSuccessful();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Pola Belanja (Apriori)')
            ->assertSeeText('Susu UHT');
    }
}
