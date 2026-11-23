<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AprioriRun;
use App\Services\AprioriService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AprioriController extends Controller
{
    public function index(): View
    {
        return $this->render(AprioriRun::latestRun());
    }

    public function show(AprioriRun $run): View
    {
        return $this->render($run);
    }

    public function store(Request $request, AprioriService $apriori): RedirectResponse
    {
        $data = $request->validate([
            'min_support' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'min_confidence' => ['required', 'numeric', 'min:1', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $run = $apriori->run(
            (float) $data['min_support'] / 100,
            (float) $data['min_confidence'] / 100,
            isset($data['date_from']) ? Carbon::parse($data['date_from']) : null,
            isset($data['date_to']) ? Carbon::parse($data['date_to']) : null,
            $request->user(),
        );

        $message = $run->transaction_count === 0
            ? 'Tidak ada transaksi pada periode tersebut.'
            : "Perhitungan selesai: {$run->itemset_count} frequent itemset dan {$run->rule_count} aturan asosiasi ditemukan.";

        return redirect()->route('admin.apriori.show', $run)->with('success', $message);
    }

    public function destroy(AprioriRun $run): RedirectResponse
    {
        $run->delete();

        return redirect()->route('admin.apriori.index')->with('success', 'Riwayat perhitungan dihapus.');
    }

    private function render(?AprioriRun $run): View
    {
        $defaults = [
            'min_support' => $run ? $run->min_support * 100 : config('apriori.min_support') * 100,
            'min_confidence' => $run ? $run->min_confidence * 100 : config('apriori.min_confidence') * 100,
            'date_from' => $run ? $run->date_from?->toDateString() : today()->subDays(config('apriori.period_days') - 1)->toDateString(),
            'date_to' => $run ? $run->date_to?->toDateString() : today()->toDateString(),
        ];

        $itemsets = $run
            ? $run->itemsets()->orderBy('size')->orderByDesc('support_count')->get()->groupBy('size')
            : collect();

        $rules = $run
            ? $run->rules()->orderByDesc('confidence')->orderByDesc('lift')->get()
            : collect();

        $history = AprioriRun::with('user')->latest('id')->get();

        return view('admin.apriori.index', compact('run', 'defaults', 'itemsets', 'rules', 'history'));
    }
}
