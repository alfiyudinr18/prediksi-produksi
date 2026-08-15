@extends('layouts.app')

@section('title','Dashboard')

@section('content')

@php
    $adaData = $dataset > 0;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
        <p class="text-slate-500 mt-1">
            Ringkasan data historis produksi dan kinerja model prediksi keterlambatan.
        </p>
    </div>
    @if($model)
    <div class="text-sm bg-white border border-slate-200 rounded-xl px-4 py-2 text-slate-500">
        Model dilatih
        <span class="font-semibold text-slate-700">
            {{ $model['waktu_training'] ?? 'tidak tercatat' }}
        </span>
    </div>
    @endif
</div>

@if(!$adaData)
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6 text-sm">
    Belum ada data produksi. Silakan impor dataset terlebih dahulu melalui menu Data Produksi.
</div>
@endif

{{-- ==================== KARTU RINGKASAN ==================== --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    @php
        $kartu = [
            ['Dataset Produksi', number_format($dataset), 'text-slate-800', 'bg-blue-100',    'Total pesanan historis'],
            ['Tepat Waktu',      number_format($tepat),   'text-emerald-600', 'bg-emerald-100', ($dataset ? round($tepat / $dataset * 100, 1) : 0) . '% dari dataset'],
            ['Terlambat',        number_format($terlambat), 'text-red-600',   'bg-red-100',     $persenTerlambat . '% dari dataset'],
            ['History Prediksi', number_format($prediksi), 'text-indigo-600', 'bg-indigo-100',  'Prediksi yang pernah dijalankan'],
        ];
    @endphp

    @foreach($kartu as $k)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm text-slate-500">{{ $k[0] }}</p>
                <h2 class="text-4xl font-bold mt-2 {{ $k[2] }}">{{ $k[1] }}</h2>
                <p class="text-xs text-slate-400 mt-2">{{ $k[4] }}</p>
            </div>
            <div class="w-3 h-16 rounded-full {{ $k[3] }}"></div>
        </div>
    </div>
    @endforeach
</div>

{{-- ==================== STATISTIK RATA-RATA ==================== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
    @php
        $stat = [
            ['Rata-rata Qty',           number_format($statistik['qty']) . ' pcs'],
            ['Rata-rata Pekerja',       $statistik['pekerja'] . ' orang'],
            ['Rata-rata Durasi Target', $statistik['durasi_target'] . ' hari'],
            ['Qty Terbesar', number_format($statistik['qty_max']) . ' pcs'],
        ];
    @endphp
    @foreach($stat as $s)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-5 py-4">
        <p class="text-xs text-slate-500">{{ $s[0] }}</p>
        <p class="text-xl font-bold text-slate-800 mt-1">{{ $s[1] }}</p>
    </div>
    @endforeach
</div>

{{-- ==================== TREN BULANAN ==================== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Tren Pesanan dan Keterlambatan</h3>
            <p class="text-xs text-slate-500 mt-1">12 bulan terakhir yang tersedia pada dataset</p>
        </div>
        <div class="flex gap-4 text-xs text-slate-500">
            <span><span class="inline-block w-3 h-3 rounded bg-blue-500 mr-1"></span>Total pesanan</span>
            <span><span class="inline-block w-3 h-3 rounded bg-red-500 mr-1"></span>Terlambat</span>
            <span><span class="inline-block w-3 h-3 rounded-full bg-amber-500 mr-1"></span>% Keterlambatan</span>
        </div>
    </div>
    <div style="height: 300px">
        <canvas id="chartTren"></canvas>
    </div>
</div>

{{-- ==================== DISTRIBUSI STATUS + PER JENIS BARANG ==================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Distribusi Status Produksi</h3>
        <p class="text-xs text-slate-500 mt-1 mb-4">Komposisi data historis</p>
        <div style="height: 240px">
            <canvas id="chartStatus"></canvas>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>Tepat waktu
                </span>
                <span class="font-semibold text-slate-800">{{ number_format($tepat) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2"></span>Terlambat
                </span>
                <span class="font-semibold text-slate-800">{{ number_format($terlambat) }}</span>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Tingkat Keterlambatan per Jenis Barang</h3>
        <p class="text-xs text-slate-500 mt-1 mb-4">
            Persentase pesanan terlambat pada setiap jenis barang
        </p>
        <div style="height: {{ max(240, count($perJenis) * 34) }}px">
            <canvas id="chartJenis"></canvas>
        </div>
    </div>
</div>

{{-- ==================== TABEL PER JENIS BARANG ==================== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Rincian per Jenis Barang</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-500 border-b border-slate-200">
                    <th class="text-left py-2 font-semibold">Jenis Barang</th>
                    <th class="text-right py-2 font-semibold">Jumlah Pesanan</th>
                    <th class="text-right py-2 font-semibold">Terlambat</th>
                    <th class="text-right py-2 font-semibold">Tepat Waktu</th>
                    <th class="text-right py-2 font-semibold w-56">Tingkat Keterlambatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perJenis as $j)
                <tr class="border-b border-slate-100">
                    <td class="py-3 text-slate-700">{{ $j['jenis'] }}</td>
                    <td class="py-3 text-right text-slate-600">{{ $j['total'] }}</td>
                    <td class="py-3 text-right font-semibold text-red-600">{{ $j['telat'] }}</td>
                    <td class="py-3 text-right font-semibold text-emerald-600">{{ $j['total'] - $j['telat'] }}</td>
                    <td class="py-3">
                        <div class="flex items-center gap-3 justify-end">
                            <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full
                                    {{ $j['rate'] >= 30 ? 'bg-red-500' : ($j['rate'] >= 15 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                    style="width: {{ $j['rate'] }}%"></div>
                            </div>
                            <span class="font-semibold text-slate-700 w-14 text-right">{{ $j['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ==================== KINERJA MODEL + HASIL PREDIKSI ==================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-slate-800">Kinerja Model Terkini</h3>
            <a href="{{ route('training.index') }}"
               class="text-sm text-blue-600 hover:text-blue-700 font-semibold">
                Kelola model
            </a>
        </div>

        @if($model)
            @php
                $mk = [
                    ['Accuracy',  $model['testing_accuracy'] ?? 0, 'text-blue-600'],
                    ['Precision', $model['precision'] ?? 0,        'text-purple-600'],
                    ['Recall',    $model['recall'] ?? 0,           'text-orange-600'],
                    ['F1 Score',  $model['f1_score'] ?? 0,         'text-indigo-600'],
                ];
                $selisih = $model['overfitting'] ?? 0;
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($mk as $m)
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                    <p class="text-xs text-slate-500">{{ $m[0] }}</p>
                    <h2 class="text-2xl font-bold {{ $m[2] }} mt-1">{{ $m[1] }}%</h2>
                </div>
                @endforeach
            </div>
            <div class="mt-4 text-xs rounded-lg px-3 py-2
                {{ $selisih <= 10 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                Selisih akurasi data latih dan data uji {{ $selisih }}%.
                {{ $selisih <= 10 ? 'Model tidak menunjukkan indikasi overfitting.' : 'Model menunjukkan indikasi overfitting.' }}
            </div>
            @if(!empty($model['feature_importance']))
            <div class="mt-5 pt-5 border-t border-slate-100">
                <p class="text-sm text-slate-500 mb-3">Kontribusi variabel</p>
                @php
                    $fi = $model['feature_importance'];
                    arsort($fi);
                    $maxFi = count($fi) ? max($fi) : 1;
                @endphp
                <div class="space-y-3">
                    @foreach($fi as $fitur => $nilai)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-600">{{ ucfirst(str_replace('_',' ',$fitur)) }}</span>
                            <span class="font-semibold text-slate-700">{{ round($nilai * 100, 2) }}%</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full"
                                 style="width: {{ $maxFi > 0 ? round($nilai / $maxFi * 100, 2) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @else
            <div class="text-center py-10">
                <p class="text-slate-600 font-semibold">Model belum dilatih</p>
                <p class="text-sm text-slate-500 mt-1">
                    Jalankan training terlebih dahulu agar sistem dapat melakukan prediksi.
                </p>
                <a href="{{ route('training.index') }}"
                   class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl
                          font-semibold text-sm">
                    Buka Training Model
                </a>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Hasil Prediksi</h3>
        <p class="text-xs text-slate-500 mt-1 mb-4">Komposisi seluruh prediksi yang tersimpan</p>

        @if($prediksi > 0)
        <div style="height: 200px">
            <canvas id="chartPrediksi"></canvas>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>Tepat waktu
                </span>
                <span class="font-semibold text-slate-800">{{ $prediksiTepat }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2"></span>Terlambat
                </span>
                <span class="font-semibold text-slate-800">{{ $prediksiTerlambat }}</span>
            </div>
        </div>
        @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-500">Belum ada prediksi yang dijalankan.</p>
            <a href="{{ route('prediksi.index') }}"
               class="inline-block mt-4 text-sm text-blue-600 hover:text-blue-700 font-semibold">
                Buat prediksi pertama
            </a>
        </div>
        @endif
    </div>
</div>

{{-- ==================== PREDIKSI TERBARU ==================== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6 mb-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800">Prediksi Terbaru</h3>
        <a href="{{ route('prediksi.history') }}"
           class="text-sm text-blue-600 hover:text-blue-700 font-semibold">
            Lihat semua
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-500 border-b border-slate-200">
                    <th class="text-left py-2 font-semibold">Jenis Barang</th>
                    <th class="text-right py-2 font-semibold">Qty</th>
                    <th class="text-right py-2 font-semibold">Pekerja</th>
                    <th class="text-right py-2 font-semibold">Durasi</th>
                    <th class="text-center py-2 font-semibold">Hasil</th>
                    <th class="text-right py-2 font-semibold">Probabilitas</th>
                    <th class="text-right py-2 font-semibold">Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prediksiTerbaru as $p)
                @php $telat = strtoupper($p->hasil_prediksi) === 'TERLAMBAT'; @endphp
                <tr class="border-b border-slate-100">
                    <td class="py-3 text-slate-700">{{ $p->jenis_barang }}</td>
                    <td class="py-3 text-right text-slate-600">{{ number_format($p->qty) }}</td>
                    <td class="py-3 text-right text-slate-600">{{ $p->jumlah_pekerja }}</td>
                    <td class="py-3 text-right text-slate-600">{{ $p->durasi_target }} hari</td>
                    <td class="py-3 text-center">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg
                            {{ $telat ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $telat ? 'Terlambat' : 'Tepat Waktu' }}
                        </span>
                    </td>
                    <td class="py-3 text-right font-semibold text-slate-700">
                        {{ number_format($p->probabilitas, 2) }}%
                    </td>
                    <td class="py-3 text-right text-slate-400 text-xs">
                        {{ $p->created_at ? $p->created_at->format('d/m/Y H:i') : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-slate-400">
                        Belum ada riwayat prediksi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Hapus baris di bawah ini apabila Chart.js sudah dimuat pada layouts.app --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.color = '#64748b';

    var tren = @json($tren);

    // ---------- TREN BULANAN ----------
    var elTren = document.getElementById('chartTren');
    if (elTren && tren.label.length) {
        new Chart(elTren, {
            data: {
                labels: tren.label,
                datasets: [
                    {
                        type: 'bar', label: 'Total pesanan', data: tren.total,
                        backgroundColor: '#3b82f6', borderRadius: 5, order: 3, yAxisID: 'y'
                    },
                    {
                        type: 'bar', label: 'Terlambat', data: tren.telat,
                        backgroundColor: '#ef4444', borderRadius: 5, order: 2, yAxisID: 'y'
                    },
                    {
                        type: 'line', label: '% Keterlambatan', data: tren.rate,
                        borderColor: '#f59e0b', backgroundColor: '#f59e0b',
                        borderWidth: 2, tension: 0.3, pointRadius: 3, order: 1, yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                if (c.dataset.yAxisID === 'y1') return ' ' + c.dataset.label + ': ' + c.parsed.y + '%';
                                return ' ' + c.dataset.label + ': ' + c.parsed.y + ' pesanan';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, position: 'left',
                        title: { display: true, text: 'Jumlah pesanan' },
                        grid: { color: '#f1f5f9' }
                    },
                    y1: {
                        beginAtZero: true, max: 100, position: 'right',
                        title: { display: true, text: '% keterlambatan' },
                        grid: { drawOnChartArea: false },
                        ticks: { callback: function (v) { return v + '%'; } }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ---------- DISTRIBUSI STATUS ----------
    var elStatus = document.getElementById('chartStatus');
    if (elStatus) {
        new Chart(elStatus, {
            type: 'doughnut',
            data: {
                labels: ['Tepat Waktu', 'Terlambat'],
                datasets: [{
                    data: [{{ $tepat }}, {{ $terlambat }}],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0, hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                var total = c.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var p = total ? (c.parsed / total * 100).toFixed(1) : 0;
                                return ' ' + c.label + ': ' + c.parsed + ' (' + p + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ---------- PER JENIS BARANG ----------
    var jenis = @json($perJenis);
    var elJenis = document.getElementById('chartJenis');
    if (elJenis && jenis.length) {
        new Chart(elJenis, {
            type: 'bar',
            data: {
                labels: jenis.map(function (j) { return j.jenis; }),
                datasets: [{
                    label: '% Keterlambatan',
                    data: jenis.map(function (j) { return j.rate; }),
                    backgroundColor: jenis.map(function (j) {
                        return j.rate >= 30 ? '#ef4444' : (j.rate >= 15 ? '#f59e0b' : '#10b981');
                    }),
                    borderRadius: 5, barThickness: 16
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                var d = jenis[c.dataIndex];
                                return ' ' + d.rate + '% terlambat (' + d.telat + ' dari ' + d.total + ' pesanan)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true, max: 100,
                        ticks: { callback: function (v) { return v + '%'; } },
                        grid: { color: '#f1f5f9' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // ---------- HASIL PREDIKSI ----------
    var elPred = document.getElementById('chartPrediksi');
    if (elPred) {
        new Chart(elPred, {
            type: 'doughnut',
            data: {
                labels: ['Tepat Waktu', 'Terlambat'],
                datasets: [{
                    data: [{{ $prediksiTepat }}, {{ $prediksiTerlambat }}],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0, hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

@endsection
