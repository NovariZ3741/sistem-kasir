<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parameter default Algoritma Apriori
    |--------------------------------------------------------------------------
    |
    | min_support    : proporsi minimal transaksi yang memuat itemset (0.05 = 5%)
    | min_confidence : peluang minimal aturan A -> B terpenuhi (0.5 = 50%)
    | period_days    : rentang data transaksi (hari ke belakang) untuk perhitungan otomatis
    | max_itemset_size : batas ukuran kombinasi produk agar perhitungan tetap ringan
    |
    */

    'min_support' => (float) env('APRIORI_MIN_SUPPORT', 0.05),

    'min_confidence' => (float) env('APRIORI_MIN_CONFIDENCE', 0.5),

    'period_days' => (int) env('APRIORI_PERIOD_DAYS', 90),

    'max_itemset_size' => (int) env('APRIORI_MAX_ITEMSET_SIZE', 4),

    // Jumlah riwayat perhitungan yang disimpan (yang lebih lama dihapus otomatis)
    'keep_runs' => (int) env('APRIORI_KEEP_RUNS', 10),

    // Jumlah maksimal rekomendasi produk yang tampil di halaman kasir
    'pos_recommendations' => 5,

];
