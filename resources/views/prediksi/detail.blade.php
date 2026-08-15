@extends('layouts.app')

@section('title','Detail Riwayat Prediksi')

@section('content')

@php
    $telat = strtoupper($prediksi->hasil_prediksi) === 'TERLAMBAT';
    $prob  = (float) $prediksi->probabilitas;

    $warna = $telat
        ? ['bg' => 'bg-red-50',     'border' => 'border-red-200',     'text' => 'text-red-700',     'bar' => 'bg-red-500']
        : ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'bar' => 'bg-emerald-500'];

    $saranPekerja = null;
    if ($telat && $pekerjaIdeal) {
        $saranPekerja = $pekerjaIdeal <= $prediksi->jumlah_pekerja
            ? $prediksi->jumlah_pekerja + 1
            : $pekerjaIdeal;
    }
@endphp

<div class="max-w-5xl mx-auto">

    {{-- ============ KEPALA HALAMAN ============ --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('prediksi.history') }}"
               class="text-sm text-slate-500 hover:text-slate-700 font-medium">
                &larr; Kembali ke riwayat
            </a>
            <h1 class="text-3xl font-bold text-slate-800 mt-2">Detail Riwayat Prediksi</h1>
            <p class="text-slate-500 mt-1 text-sm">
                Prediksi ke-{{ $prediksi->id }} &middot; dibuat
                {{ $prediksi->created_at ? $prediksi->created_at->format('d/m/Y H:i') : '-' }}
            </p>
        </div>

        <form action="{{ route('prediksi.proses') }}" method="POST">
            @csrf
            <input type="hidden" name="qty" value="{{ $prediksi->qty }}">
            <input type="hidden" name="jenis_barang" value="{{ $prediksi->jenis_barang }}">
            <input type="hidden" name="jumlah_pekerja" value="{{ $prediksi->jumlah_pekerja }}">
            <input type="hidden" name="tanggal_order"
                   value="{{ \Carbon\Carbon::parse($prediksi->tanggal_order)->format('Y-m-d') }}">
            <input type="hidden" name="target_selesai"
                   value="{{ \Carbon\Carbon::parse($prediksi->target_selesai)->format('Y-m-d') }}">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl
                           font-semibold text-sm shadow-sm">
                Prediksi Ulang
            </button>
        </form>
    </div>

    {{-- ============ HASIL ============ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="{{ $warna['bg'] }} border-b {{ $warna['border'] }} px-8 py-8 text-center">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                Hasil Prediksi
            </p>
            <h2 class="text-4xl font-bold {{ $warna['text'] }} mt-2">
                {{ $telat ? 'TERLAMBAT' : 'TEPAT WAKTU' }}
            </h2>

            <div class="max-w-md mx-auto mt-6">
                <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                    <span>Peluang terlambat</span>
                    <span class="font-semibold text-slate-700">{{ number_format($prob, 2) }}%</span>
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
            @php
                $kartu = [
                    ['Qty Produksi', number_format($prediksi->qty), 'text-slate-800', 'pcs'],
                    ['Jumlah Pekerja', $prediksi->jumlah_pekerja, 'text-slate-800', 'orang'],
                    ['Durasi Target', $prediksi->durasi_target, 'text-blue-600', 'hari'],
                    ['Bobot Kesusahan', $bobot ? $bobot . ' / 5' : '-', 'text-orange-500', 'tingkat kesulitan'],
                ];
            @endphp
            @foreach($kartu as $k)
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 text-center">
                <p class="text-xs text-slate-500">{{ $k[0] }}</p>
                <h3 class="text-2xl font-bold {{ $k[2] }} mt-1.5">{{ $k[1] }}</h3>
                <p class="text-slate-400 mt-1" style="font-size:11px">{{ $k[3] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">

        {{-- ============ DATA PESANAN ============ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Data Pesanan</h3>

            <table class="w-full text-sm">
                <tbody>
                    @php
                        $baris = [
                            ['Jenis Barang',   $prediksi->jenis_barang],
                            ['Qty Produksi',   number_format($prediksi->qty) . ' pcs'],
                            ['Jumlah Pekerja', $prediksi->jumlah_pekerja . ' orang'],
                            ['Tanggal Order',  \Carbon\Carbon::parse($prediksi->tanggal_order)->format('d/m/Y')],
                            ['Target Selesai', \Carbon\Carbon::parse($prediksi->target_selesai)->format('d/m/Y')],
                            ['Durasi Target',  $prediksi->durasi_target . ' hari'],
                        ];
                    @endphp
                    @foreach($baris as $b)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 text-slate-500">{{ $b[0] }}</td>
                        <td class="py-3 text-right font-semibold text-slate-800">{{ $b[1] }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="py-3 text-slate-500">Waktu Prediksi</td>
                        <td class="py-3 text-right font-semibold text-slate-800">
                            {{ $prediksi->created_at ? $prediksi->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>

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
                    Kapasitas wajar satu pekerja sekitar 10 pcs ekuivalen per hari. Nilai di atas
                    angka tersebut menandakan beban pengerjaan terlalu padat.
                </p>
            </div>
            @endif
        </div>

        {{-- ============ CATATAN ============ --}}
        <div class="space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Kesimpulan</h3>
                <p class="text-sm text-slate-600 leading-7">
                    Pesanan {{ $prediksi->jenis_barang }} sebanyak
                    {{ number_format($prediksi->qty) }} pcs dengan
                    {{ $prediksi->jumlah_pekerja }} pekerja dan durasi target
                    {{ $prediksi->durasi_target }} hari diprediksi
                    <strong>{{ $telat ? 'mengalami keterlambatan' : 'selesai tepat waktu' }}</strong>
                    dengan peluang keterlambatan sebesar
                    <strong>{{ number_format($prob, 2) }}%</strong>.
                </p>
            </div>

            <div class="rounded-2xl border {{ $warna['border'] }} {{ $warna['bg'] }} p-6">
                <h3 class="text-lg font-semibold {{ $warna['text'] }} mb-3">
                    {{ $telat ? 'Tindakan yang Disarankan' : 'Catatan' }}
                </h3>

                @if($telat)
                <ul class="space-y-2.5 text-sm text-slate-700">
                    @if($saranPekerja)
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>
                            Tambah jumlah pekerja menjadi sekitar
                            <strong>{{ $saranPekerja }} orang</strong>
                            (saat ini {{ $prediksi->jumlah_pekerja }} orang).
                        </span>
                    </li>
                    @endif
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Negosiasikan perpanjangan target selesai sebelum pengerjaan dimulai.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-400">&bull;</span>
                        <span>Pastikan bahan baku tersedia agar tidak menambah waktu tunggu.</span>
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
                        <span>Prediksi belum memperhitungkan kejadian tak terduga, sehingga pemantauan
                            tetap diperlukan.</span>
                    </li>
                </ul>
                @endif
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                <p class="text-xs text-slate-500 leading-relaxed">
                    Hasil pada halaman ini adalah keluaran model saat prediksi dijalankan. Apabila
                    model telah dilatih ulang setelah tanggal tersebut, gunakan tombol Prediksi Ulang
                    untuk memperoleh hasil berdasarkan model terbaru.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
