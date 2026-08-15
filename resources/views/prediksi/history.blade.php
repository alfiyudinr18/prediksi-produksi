@extends('layouts.app')

@section('title','Riwayat Prediksi')

@section('content')

@php
    $r      = $ringkasan ?? ['total' => 0, 'terlambat' => 0, 'tepat' => 0];
    $pctTel = $r['total'] > 0 ? round($r['terlambat'] / $r['total'] * 100, 1) : 0;
@endphp

<div class="flex flex-wrap justify-between items-end gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Riwayat Prediksi</h1>
        <p class="text-slate-500 mt-1">
            Seluruh hasil prediksi keterlambatan produksi yang telah dijalankan.
        </p>
    </div>
    <a href="{{ route('prediksi.index') }}"
       class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl shadow-sm font-semibold">
        Prediksi Baru
    </a>
</div>

{{-- ============ RINGKASAN ============ --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm text-slate-500">Total Prediksi</p>
        <h2 class="text-3xl font-bold text-slate-800 mt-1">{{ number_format($r['total']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">Sejak sistem digunakan</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm text-slate-500">Diprediksi Terlambat</p>
        <h2 class="text-3xl font-bold text-red-600 mt-1">{{ number_format($r['terlambat']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">{{ $pctTel }}% dari seluruh prediksi</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <p class="text-sm text-slate-500">Diprediksi Tepat Waktu</p>
        <h2 class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($r['tepat']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">{{ 100 - $pctTel }}% dari seluruh prediksi</p>
    </div>
</div>

{{-- ============ TABEL ============ --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

    <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-lg text-slate-800">Data Riwayat Prediksi</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan {{ $data->count() }} dari {{ $data->total() }} data
            </p>
        </div>

        <form method="GET" action="{{ route('prediksi.history') }}" class="flex flex-wrap items-center gap-2">
            <select name="hasil"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white
                       focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Semua hasil</option>
                <option value="TERLAMBAT" @selected(request('hasil')=='TERLAMBAT')>Terlambat</option>
                <option value="TEPAT WAKTU" @selected(request('hasil')=='TEPAT WAKTU')>Tepat Waktu</option>
            </select>

            @if(isset($jenisList) && count($jenisList))
            <select name="jenis"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white
                       focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Semua jenis barang</option>
                @foreach($jenisList as $j)
                <option value="{{ $j }}" @selected(request('jenis')==$j)>{{ $j }}</option>
                @endforeach
            </select>
            @endif

            <button class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold
                           px-4 py-2 rounded-xl">
                Filter
            </button>

            @if(request('hasil') || request('jenis'))
            <a href="{{ route('prediksi.history') }}"
               class="text-sm text-slate-500 hover:text-slate-700 px-2">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3 text-left font-semibold">No</th>
                    <th class="px-5 py-3 text-left font-semibold">Jenis Barang</th>
                    <th class="px-5 py-3 text-right font-semibold">Qty</th>
                    <th class="px-5 py-3 text-right font-semibold">Pekerja</th>
                    <th class="px-5 py-3 text-right font-semibold">Durasi</th>
                    <th class="px-5 py-3 text-center font-semibold">Hasil</th>
                    <th class="px-5 py-3 text-center font-semibold">Peluang Terlambat</th>
                    <th class="px-5 py-3 text-right font-semibold">Tanggal Prediksi</th>
                    <th class="px-5 py-3 text-center font-semibold">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                @php
                    $telat = strtoupper($item->hasil_prediksi) === 'TERLAMBAT';
                    $prob  = (float) $item->probabilitas;
                @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                    <td class="px-5 py-4 text-slate-400">
                        {{ $data->firstItem() + $loop->index }}
                    </td>
                    <td class="px-5 py-4 font-medium text-slate-700">
                        {{ $item->jenis_barang }}
                    </td>
                    <td class="px-5 py-4 text-right text-slate-600">
                        {{ number_format($item->qty) }}
                    </td>
                    <td class="px-5 py-4 text-right text-slate-600">
                        {{ $item->jumlah_pekerja }}
                    </td>
                    <td class="px-5 py-4 text-right text-slate-600">
                        {{ $item->durasi_target }} hari
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex text-xs px-3 py-1 rounded-lg font-semibold
                            {{ $telat ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $telat ? 'Terlambat' : 'Tepat Waktu' }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3 justify-end">
                            <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $telat ? 'bg-red-500' : 'bg-emerald-500' }}"
                                     style="width: {{ min(100, max(0, $prob)) }}%"></div>
                            </div>
                            <span class="font-semibold text-slate-700 w-14 text-right">
                                {{ number_format($prob, 2) }}%
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right text-slate-500 text-xs">
                        {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td class="px-5 py-4 text-center">
                        <a href="{{ route('prediksi.show', $item->id) }}"
                           class="inline-flex text-xs font-semibold text-blue-600 hover:text-blue-700
                                  hover:bg-blue-50 px-3 py-1.5 rounded-lg transition">
                            Lihat
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-14">
                        <p class="text-slate-600 font-semibold">Belum ada riwayat prediksi</p>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ (request('hasil') || request('jenis'))
                                ? 'Tidak ada data yang sesuai dengan filter yang dipilih.'
                                : 'Jalankan prediksi terlebih dahulu melalui menu Prediksi.' }}
                        </p>
                        <a href="{{ route('prediksi.index') }}"
                           class="inline-block mt-4 text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Buat prediksi baru
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
        {{ $data->links() }}
    </div>
    @endif
</div>

<p class="text-xs text-slate-400 mt-4">
    Kolom Peluang Terlambat menunjukkan rata-rata suara 500 pohon keputusan.
    Nilai 50% atau lebih diklasifikasikan sebagai Terlambat.
</p>

@endsection
