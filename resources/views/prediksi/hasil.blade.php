@extends('layouts.app')

@section('title','Hasil Prediksi')

@section('content')

@php
    $terlambat = strtoupper($hasil['hasil'] ?? '') === 'TERLAMBAT';

    // Nilai tambahan dari predict.py versi terbaru (aman bila belum tersedia)
    $prob      = $hasil['probabilitas'] ?? 0;               // peluang terlambat
    $keyakinan = $hasil['keyakinan'] ?? null;               // keyakinan kelas terpilih
    $bobot     = $hasil['bobot_kesusahan'] ?? null;
    $beban     = $hasil['beban_kerja'] ?? null;
    $durasi    = $hasil['durasi_target'] ?? 0;

    $in        = $input ?? [];
    $qty       = $in['qty'] ?? null;
    $pekerja   = $in['jumlah_pekerja'] ?? null;

    // Saran jumlah pekerja bila diprediksi terlambat (kapasitas 10 pcs ekuivalen/pekerja/hari)
    $pekerjaIdeal = null;
    if ($terlambat && $qty && $bobot && $durasi > 0) {
        $pekerjaIdeal = (int) ceil(($qty * $bobot) / (10 * $durasi));
        if ($pekerja && $pekerjaIdeal <= $pekerja) {
            $pekerjaIdeal = $pekerja + 1;
        }
    }

    $warna = $terlambat
        ? ['bg' => 'bg-red-50',     'border' => 'border-red-200',     'text' => 'text-red-700',     'bar' => 'bg-red-500']
        : ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'bar' => 'bg-emerald-500'];
@endphp

<div class="max-w-5xl mx-auto">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Hasil Prediksi</h1>
        <p class="text-slate-500 mt-1">
            Hasil klasifikasi keterlambatan produksi menggunakan algoritma Random Forest.
        </p>
    </div>

    {{-- ============ KARTU HASIL UTAMA ============ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="{{ $warna['bg'] }} border-b {{ $warna['border'] }} px-8 py-8 text-center">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                Status Prediksi
            </p>
            <h2 class="text-4xl font-bold {{ $warna['text'] }} mt-2">
                {{ $terlambat ? 'TERLAMBAT' : 'TEPAT WAKTU' }}
            </h2>
            <p class="text-slate-600 mt-2 text-sm">
                {{ $terlambat
                    ? 'Pesanan diprediksi tidak selesai sebelum target selesai.'
                    : 'Pesanan diprediksi selesai sebelum target selesai.' }}
            </p>

            <div class="max-w-md mx-auto mt-6">
                <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                    <span>Peluang terlambat</span>
                    <span class="font-semibold text-slate-700">{{ $prob }}%</span>
                </div>
                <div class="h-3 bg-white rounded-full overflow-hidden border border-slate-200">
                    <div class="h-full {{ $warna['bar'] }} rounded-full"
                         style="width: {{ min(100, max(0, $prob)) }}%"></div>
                </div>
                <div class="flex justify-between text-slate-400 mt-1" style="font-size:11px">
                    <span>0%</span>
                    <span>ambang keputusan 50%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>

        {{-- ============ ANGKA PENTING ============ --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
            @php
                $kartu = [
                    ['Durasi Target', $durasi . ' hari', 'text-blue-600', 'Selisih target selesai dan tanggal order'],
                    ['Peluang Terlambat', $prob . '%', 'text-red-600', 'Rata-rata suara 500 pohon'],
                ];
                if ($keyakinan !== null) {
                    $kartu[] = ['Tingkat Keyakinan', $keyakinan . '%', 'text-indigo-600', 'Keyakinan pada kelas terpilih'];
                }
                if ($bobot !== null) {
                    $kartu[] = ['Bobot Kesusahan', $bobot . ' / 5', 'text-orange-500', 'Tingkat kesulitan jenis barang'];
                }
            @endphp
            @foreach($kartu as $k)
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 text-center">
                <p class="text-xs text-slate-500">{{ $k[0] }}</p>
                <h3 class="text-2xl font-bold {{ $k[2] }} mt-1.5">{{ $k[1] }}</h3>
                <p class="text-slate-400 mt-1.5 leading-snug" style="font-size:11px">{{ $k[3] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">

        {{-- ============ DATA MASUKAN ============ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Data Pesanan</h3>

            @if(count($in))
            <table class="w-full text-sm">
                <tbody>
                    @php
                        $baris = [
                            ['Jenis Barang',   $in['jenis_barang'] ?? '-'],
                            ['Qty Produksi',   $qty ? number_format($qty) . ' pcs' : '-'],
                            ['Jumlah Pekerja', $pekerja ? $pekerja . ' orang' : '-'],
                            ['Tanggal Order',  isset($in['tanggal_order']) ? date('d/m/Y', strtotime($in['tanggal_order'])) : '-'],
                            ['Target Selesai', isset($in['target_selesai']) ? date('d/m/Y', strtotime($in['target_selesai'])) : '-'],
                            ['Durasi Target',  $durasi . ' hari'],
                        ];
                    @endphp
                    @foreach($baris as $b)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 text-slate-500">{{ $b[0] }}</td>
                        <td class="py-3 text-right font-semibold text-slate-800">{{ $b[1] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-sm text-slate-500">
                Rincian data masukan tidak tersedia. Perbarui PrediksiController agar
                mengirimkan variabel input ke halaman ini.
            </p>
            @endif

            @if($beban !== null)
            <div class="mt-5 pt-5 border-t border-slate-100">
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Beban Kerja</p>
                        <p class="text-xs text-slate-500">pcs ekuivalen per pekerja per hari</p>
                    </div>
                    <p class="text-2xl font-bold {{ $beban > 10 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $beban }}
                    </p>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $beban > 10 ? 'bg-red-500' : 'bg-emerald-500' }} rounded-full"
                         style="width: {{ min(100, $beban / 20 * 100) }}%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Kapasitas wajar satu pekerja adalah sekitar 10 pcs ekuivalen per hari.
                    Nilai di atas angka tersebut menandakan beban pengerjaan terlalu padat.
                </p>
            </div>
            @endif
        </div>

        {{-- ============ KESIMPULAN & SARAN ============ --}}
        <div class="space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Kesimpulan</h3>
                <p class="text-sm text-slate-600 leading-7">
                    Berdasarkan hasil klasifikasi menggunakan algoritma
                    <strong>Random Forest</strong>, pesanan ini diprediksi
                    <strong>{{ $terlambat ? 'mengalami keterlambatan' : 'selesai tepat waktu' }}</strong>
                    dengan peluang keterlambatan sebesar <strong>{{ $prob }}%</strong>.
                    @if($keyakinan !== null)
                        Tingkat keyakinan model terhadap keputusan ini adalah
                        <strong>{{ $keyakinan }}%</strong>.
                    @endif
                    Prediksi dihasilkan dari penggabungan suara 500 pohon keputusan yang
                    dilatih menggunakan data produksi historis.
                </p>
            </div>

            <div class="rounded-2xl border {{ $warna['border'] }} {{ $warna['bg'] }} p-6">
                <h3 class="text-lg font-semibold {{ $warna['text'] }} mb-3">
                    {{ $terlambat ? 'Tindakan yang Disarankan' : 'Catatan' }}
                </h3>

                @if($terlambat)
                <ul class="space-y-2.5 text-sm text-slate-700">
                    @if($pekerjaIdeal)
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>
                            Tambah jumlah pekerja menjadi sekitar
                            <strong>{{ $pekerjaIdeal }} orang</strong>
                            @if($pekerja) (saat ini {{ $pekerja }} orang) @endif
                            agar beban per pekerja turun ke batas wajar.
                        </span>
                    </li>
                    @endif
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Negosiasikan perpanjangan target selesai dengan pelanggan sejak awal,
                            sebelum pengerjaan dimulai.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Pastikan bahan baku tersedia sebelum produksi dimulai untuk menghindari
                            waktu tunggu.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Pertimbangkan memecah pesanan menjadi beberapa tahap pengiriman
                            apabila pelanggan mengizinkan.</span>
                    </li>
                </ul>
                @else
                <ul class="space-y-2.5 text-sm text-slate-700">
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Pesanan dapat dijadwalkan sesuai rencana dengan alokasi pekerja saat ini.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Prediksi ini belum memperhitungkan kejadian tak terduga seperti
                            kerusakan mesin atau keterlambatan bahan, sehingga pemantauan tetap diperlukan.</span>
                    </li>
                </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-wrap justify-center gap-3 mt-8">
        <a href="{{ route('prediksi.index') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-sm">
            Prediksi Lagi
        </a>
        <a href="{{ route('prediksi.history') }}"
           class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700
                  px-6 py-3 rounded-xl font-semibold">
            Riwayat Prediksi
        </a>
    </div>
</div>

@endsection
