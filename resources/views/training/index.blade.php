@extends('layouts.app')

@section('title','Training Model')

@section('content')

@php
    // Nilai aman apabila evaluation.json masih versi lama
    $ev        = $evaluation ?? null;
    $cm        = $ev['confusion_matrix'] ?? null;
    $tn        = $cm[0][0] ?? 0;
    $fp        = $cm[0][1] ?? 0;
    $fn        = $cm[1][0] ?? 0;
    $tp        = $cm[1][1] ?? 0;
    $totalUji  = $tn + $fp + $fn + $tp;
    $perKelas  = $ev['per_kelas']    ?? [];
    $macro     = $ev['macro_avg']    ?? null;
    $weighted  = $ev['weighted_avg'] ?? null;
    $cv        = $ev['cv_accuracy']  ?? [];
    $lengkap   = $ev && $macro && count($perKelas);
    $tepat     = $ev['tepat_waktu'] ?? 0;
    $terlambat = $ev['terlambat'] ?? 0;
    $totalData = $ev['jumlah_data'] ?? ($tepat + $terlambat);
    $pctTelat  = $totalData > 0 ? round($terlambat / $totalData * 100, 2) : 0;
@endphp

<div class="flex flex-wrap items-end justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Training Model</h1>
        <p class="text-slate-500 mt-1">
            Melatih ulang model Random Forest menggunakan dataset historis produksi.
        </p>
    </div>
    @if($ev)
        <div class="text-sm text-slate-500 bg-white border border-slate-200 rounded-xl px-4 py-2">
            Training terakhir
            <span class="font-semibold text-slate-700">
                {{ $ev['waktu_training'] ?? 'tidak tercatat' }}
            </span>
        </div>
    @endif
</div>

@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 mb-6">
    <p class="font-semibold mb-2">Training selesai</p>
    <pre class="text-xs leading-relaxed whitespace-pre-wrap max-h-64 overflow-auto">{{ session('success') }}</pre>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6">
    {{ session('error') }}
</div>
@endif

@if($ev && !$lengkap)
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6 text-sm">
    Hasil evaluasi masih berasal dari versi lama sehingga metrik per kelas belum tersedia.
    Jalankan Training Random Forest sekali lagi untuk memperbarui.
</div>
@endif

{{-- ================= BARIS 1: AKSI + RINGKASAN KINERJA ================= --}}
<div class="grid lg:grid-cols-3 gap-6">

    <div class="space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Jalankan Training</h3>
            <p class="text-sm text-slate-500 mb-5">
                Seluruh data produksi pada basis data akan dibagi 80:20, lalu dipakai
                membangun model Random Forest baru. Model lama akan ditimpa.
            </p>
            <form action="{{ route('training.train') }}" method="POST" id="formTraining">
                @csrf
                <button id="btnTraining"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl
                           font-semibold transition">
                    <span id="btnLabel">Training Random Forest</span>
                </button>
            </form>
            <p class="text-xs text-slate-400 mt-3 text-center">
                Proses dapat berlangsung beberapa detik. Mohon tidak menutup halaman.
            </p>
        </div>

        @if($ev)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Dataset</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Total data</span>
                    <span class="font-semibold text-slate-800">{{ number_format($totalData) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Data latih (80%)</span>
                    <span class="font-semibold text-slate-800">{{ $ev['data_training'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Data uji (20%)</span>
                    <span class="font-semibold text-slate-800">{{ $ev['data_testing'] ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-5 pt-5 border-t border-slate-100">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-500">Komposisi kelas</span>
                    <span class="font-semibold text-slate-700">{{ $pctTelat }}% terlambat</span>
                </div>
                <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100">
                    <div class="bg-emerald-500" style="width: {{ 100 - $pctTelat }}%"></div>
                    <div class="bg-red-500" style="width: {{ $pctTelat }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-500 mt-2">
                    <span><span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></span>
                        Tepat waktu ({{ $tepat }})</span>
                    <span><span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span>
                        Terlambat ({{ $terlambat }})</span>
                </div>
            </div>

            @if(!empty($ev['fitur']))
            <div class="mt-5 pt-5 border-t border-slate-100">
                <p class="text-sm text-slate-500 mb-2">Variabel masukan</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($ev['fitur'] as $f)
                    <span class="text-xs bg-slate-100 text-slate-700 rounded-lg px-2.5 py-1 font-mono">
                        {{ $f }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>

    <div class="lg:col-span-2">
        @if(!$ev)
        <div class="bg-white rounded-2xl shadow-sm border border-dashed border-slate-300 p-12 text-center h-full
                    flex flex-col items-center justify-center">
            <h3 class="text-lg font-semibold text-slate-700">Belum ada model</h3>
            <p class="text-slate-500 mt-2 text-sm max-w-sm">
                Jalankan Training Random Forest terlebih dahulu untuk melihat hasil evaluasi model.
            </p>
        </div>
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-full">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-slate-800">Ringkasan Kinerja Model</h3>
                <span class="text-xs text-slate-500">kelas positif: Terlambat</span>
            </div>

            @php
                $kartu = [
                    ['Testing Accuracy', $ev['testing_accuracy'] ?? 0, 'text-blue-600',   'Prediksi benar pada data uji'],
                    ['Precision',        $ev['precision'] ?? 0,        'text-purple-600', 'Ketepatan peringatan terlambat'],
                    ['Recall',           $ev['recall'] ?? 0,           'text-orange-600', 'Keterlambatan yang terdeteksi'],
                    ['F1 Score',         $ev['f1_score'] ?? 0,         'text-indigo-600', 'Rata-rata precision & recall'],
                    ['Specificity',      $ev['specificity'] ?? 0,      'text-teal-600',   'Ketepatan pada kelas tepat waktu'],
                    ['AUC',              $ev['auc'] ?? 0,              'text-cyan-600',   'Kemampuan memisahkan dua kelas'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($kartu as $k)
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                    <p class="text-xs text-slate-500">{{ $k[0] }}</p>
                    <h2 class="text-2xl font-bold {{ $k[2] }} mt-1">{{ $k[1] }}%</h2>
                    <p class="text-slate-400 mt-1 leading-snug" style="font-size:11px">{{ $k[3] }}</p>
                </div>
                @endforeach
            </div>

            @php $selisih = $ev['overfitting'] ?? 0; $aman = $selisih <= 10; @endphp
            <div class="mt-5 pt-5 border-t border-slate-100 grid sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Training Accuracy</p>
                    <p class="font-bold text-slate-800 text-lg">{{ $ev['training_accuracy'] ?? 0 }}%</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Testing Accuracy</p>
                    <p class="font-bold text-slate-800 text-lg">{{ $ev['testing_accuracy'] ?? 0 }}%</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Selisih Accuracy</p>
                    <p class="font-bold text-lg {{ $aman ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $selisih }}%
                    </p>
                </div>
            </div>
            <div class="mt-3 text-xs rounded-lg px-3 py-2 {{ $aman ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                {{ $aman
                    ? 'Selisih akurasi di bawah 10%, model tidak menunjukkan indikasi overfitting.'
                    : 'Selisih akurasi melebihi 10%, model menunjukkan indikasi overfitting.' }}
            </div>
        </div>
        @endif
    </div>
</div>

@if($ev)

{{-- ============ BARIS 2: CONFUSION MATRIX + METRIK PER KELAS ============ --}}
<div class="grid lg:grid-cols-2 gap-6 mt-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Confusion Matrix</h3>
        <p class="text-xs text-slate-500 mt-1 mb-5">
            Hasil pengujian {{ $totalUji }} data uji (20% dari dataset)
        </p>

        <table class="w-full text-sm text-center border-collapse">
            <thead>
                <tr>
                    <th class="p-2"></th>
                    <th class="p-2 text-xs font-semibold text-slate-500">Prediksi 0<br>Tepat Waktu</th>
                    <th class="p-2 text-xs font-semibold text-slate-500">Prediksi 1<br>Terlambat</th>
                    <th class="p-2 text-xs font-semibold text-slate-400">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th class="p-2 text-xs font-semibold text-slate-500 text-right">Aktual 0<br>Tepat Waktu</th>
                    <td class="p-1">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg py-4">
                            <p class="text-3xl font-bold text-emerald-700">{{ $tn }}</p>
                            <p class="text-emerald-600 mt-1" style="font-size:10px">True Negative</p>
                        </div>
                    </td>
                    <td class="p-1">
                        <div class="bg-red-50 border border-red-200 rounded-lg py-4">
                            <p class="text-3xl font-bold text-red-700">{{ $fp }}</p>
                            <p class="text-red-600 mt-1" style="font-size:10px">False Positive</p>
                        </div>
                    </td>
                    <td class="p-1 font-semibold text-slate-500">{{ $tn + $fp }}</td>
                </tr>
                <tr>
                    <th class="p-2 text-xs font-semibold text-slate-500 text-right">Aktual 1<br>Terlambat</th>
                    <td class="p-1">
                        <div class="bg-red-50 border border-red-200 rounded-lg py-4">
                            <p class="text-3xl font-bold text-red-700">{{ $fn }}</p>
                            <p class="text-red-600 mt-1" style="font-size:10px">False Negative</p>
                        </div>
                    </td>
                    <td class="p-1">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg py-4">
                            <p class="text-3xl font-bold text-emerald-700">{{ $tp }}</p>
                            <p class="text-emerald-600 mt-1" style="font-size:10px">True Positive</p>
                        </div>
                    </td>
                    <td class="p-1 font-semibold text-slate-500">{{ $fn + $tp }}</td>
                </tr>
                <tr>
                    <th class="p-2 text-xs font-semibold text-slate-400 text-right">Total</th>
                    <td class="p-1 font-semibold text-slate-500">{{ $tn + $fn }}</td>
                    <td class="p-1 font-semibold text-slate-500">{{ $fp + $tp }}</td>
                    <td class="p-1 font-semibold text-slate-700">{{ $totalUji }}</td>
                </tr>
            </tbody>
        </table>

        <p class="text-xs text-slate-500 mt-5 leading-relaxed">
            Dari {{ $fn + $tp }} pesanan yang benar-benar terlambat, {{ $tp }} berhasil dikenali
            dan {{ $fn }} tidak terdeteksi. Terdapat {{ $fp }} peringatan keterlambatan
            yang sebenarnya tepat waktu.
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Metrik per Kelas</h3>
        <p class="text-xs text-slate-500 mt-1 mb-5">Setara classification report scikit-learn</p>

        @if($lengkap)
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-500 border-b border-slate-200">
                    <th class="text-left py-2 font-semibold">Kelas</th>
                    <th class="text-right py-2 font-semibold">Precision</th>
                    <th class="text-right py-2 font-semibold">Recall</th>
                    <th class="text-right py-2 font-semibold">F1</th>
                    <th class="text-right py-2 font-semibold">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perKelas as $nama => $d)
                <tr class="border-b border-slate-100">
                    <td class="py-3">
                        <span class="inline-block w-2 h-2 rounded-full mr-2
                            {{ $nama === 'Terlambat' ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                        {{ $nama }}
                    </td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $d['precision'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $d['recall'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $d['f1_score'] }}%</td>
                    <td class="py-3 text-right text-slate-500">{{ $d['support'] }}</td>
                </tr>
                @endforeach

                <tr class="border-b border-slate-100 bg-slate-50">
                    <td class="py-3 font-semibold text-slate-600">Accuracy</td>
                    <td class="py-3 text-right text-slate-400">&mdash;</td>
                    <td class="py-3 text-right text-slate-400">&mdash;</td>
                    <td class="py-3 text-right font-bold text-blue-600">
                        {{ $ev['testing_accuracy'] ?? 0 }}%
                    </td>
                    <td class="py-3 text-right text-slate-500">{{ $totalUji }}</td>
                </tr>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <td class="py-3 font-semibold text-slate-600">Macro average</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $macro['precision'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $macro['recall'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $macro['f1_score'] }}%</td>
                    <td class="py-3 text-right text-slate-500">{{ $macro['support'] }}</td>
                </tr>
                <tr class="bg-slate-50">
                    <td class="py-3 font-semibold text-slate-600">Weighted average</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $weighted['precision'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $weighted['recall'] }}%</td>
                    <td class="py-3 text-right font-semibold text-slate-700">{{ $weighted['f1_score'] }}%</td>
                    <td class="py-3 text-right text-slate-500">{{ $weighted['support'] }}</td>
                </tr>
            </tbody>
        </table>
        <p class="text-xs text-slate-500 mt-5 leading-relaxed">
            Macro average merupakan rata-rata sederhana kedua kelas sehingga kelas minoritas
            dihitung sama penting, sedangkan weighted average ditimbang menurut jumlah data
            setiap kelas.
        </p>
        @else
        <p class="text-sm text-slate-500">
            Metrik per kelas belum tersedia. Jalankan training ulang untuk memunculkannya.
        </p>
        @endif
    </div>
</div>

{{-- ============ BARIS 3: FEATURE IMPORTANCE + CROSS VALIDATION ============ --}}
<div class="grid lg:grid-cols-2 gap-6 mt-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Feature Importance</h3>
        <p class="text-xs text-slate-500 mt-1 mb-5">Kontribusi setiap variabel pada model</p>

        @php
            $fi = $ev['feature_importance'] ?? [];
            arsort($fi);
            $maxFi = count($fi) ? max($fi) : 1;
        @endphp
        <div class="space-y-4">
            @foreach($fi as $fitur => $nilai)
            <div>
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="text-slate-600">{{ ucfirst(str_replace('_',' ',$fitur)) }}</span>
                    <span class="font-semibold text-slate-800">{{ round($nilai * 100, 2) }}%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full"
                         style="width: {{ $maxFi > 0 ? round($nilai / $maxFi * 100, 2) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-5 leading-relaxed">
            Nilai dihitung dari total penurunan Gini impurity yang disumbangkan setiap
            variabel pada seluruh pohon.
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-800">Cross Validation 5-Fold</h3>
        <p class="text-xs text-slate-500 mt-1 mb-5">Uji kestabilan model pada seluruh dataset</p>

        @if(count($cv))
        <div class="flex items-end gap-3 h-40 mb-4">
            @foreach($cv as $i => $akurasi)
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                <span class="font-semibold text-slate-600 mb-1" style="font-size:11px">{{ $akurasi }}%</span>
                <div class="w-full bg-blue-500 rounded-t-md" style="height: {{ $akurasi }}%"></div>
                <span class="text-slate-500 mt-1.5" style="font-size:11px">Fold {{ $i + 1 }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <span class="text-sm text-slate-500">Rata-rata akurasi</span>
            <span class="text-xl font-bold text-blue-600">{{ $ev['cv_accuracy_mean'] ?? 0 }}%</span>
        </div>
        <p class="text-xs text-slate-500 mt-3 leading-relaxed">
            Rata-rata akurasi validasi silang yang mendekati testing accuracy
            ({{ $ev['testing_accuracy'] ?? 0 }}%) menunjukkan kinerja model konsisten
            pada pembagian data yang berbeda.
        </p>
        @else
        <p class="text-sm text-slate-500">Data validasi silang belum tersedia.</p>
        @endif
    </div>
</div>

@endif

<script>
    document.getElementById('formTraining').addEventListener('submit', function () {
        var btn = document.getElementById('btnTraining');
        btn.disabled = true;
        btn.className = btn.className + ' opacity-60 cursor-wait';
        document.getElementById('btnLabel').textContent = 'Memproses training...';
    });
</script>

@endsection
