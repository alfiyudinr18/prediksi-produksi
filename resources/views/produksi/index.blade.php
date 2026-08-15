@extends('layouts.app')

@section('title','Data Produksi')

@section('content')

@php
    $r      = $ringkasan ?? ['total' => 0, 'terlambat' => 0, 'tepat' => 0, 'durasi_target' => 0, 'qty' => 0, 'awal' => null, 'akhir' => null];
    $pctTel = $r['total'] > 0 ? round($r['terlambat'] / $r['total'] * 100, 1) : 0;
    $adaFilter = request('cari') || request('status') !== null && request('status') !== '' || request('jenis');
@endphp

<div class="flex flex-wrap justify-between items-end gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Data Produksi</h1>
        <p class="text-slate-500 mt-1">
            Dataset historis produksi yang dipakai untuk melatih model prediksi.
        </p>
    </div>
    <button type="button" onclick="document.getElementById('panelUpload').classList.toggle('hidden')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl shadow-sm font-semibold">
        Import Dataset
    </button>
</div>

@if(session('success'))
<div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
    {{ session('error') }}
</div>
@endif

{{-- ============ RINGKASAN HASIL IMPORT ============ --}}
@if(session('import'))
@php $imp = session('import'); @endphp
<div class="mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm p-5">

    <p class="text-sm font-semibold text-slate-800 mb-4">Ringkasan Hasil Import</p>

    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
            <p class="text-xs text-emerald-700">Data baru ditambahkan</p>
            <p class="text-2xl font-bold text-emerald-700 mt-0.5">{{ $imp['baru'] }}</p>
        </div>
        <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-3">
            <p class="text-xs text-blue-700">Data lama diperbarui</p>
            <p class="text-2xl font-bold text-blue-700 mt-0.5">{{ $imp['diperbarui'] }}</p>
        </div>
        <div class="rounded-xl {{ $imp['dilewati'] > 0 ? 'bg-amber-50 border-amber-100' : 'bg-slate-50 border-slate-100' }} border px-4 py-3">
            <p class="text-xs {{ $imp['dilewati'] > 0 ? 'text-amber-700' : 'text-slate-500' }}">Baris dilewati</p>
            <p class="text-2xl font-bold mt-0.5 {{ $imp['dilewati'] > 0 ? 'text-amber-700' : 'text-slate-500' }}">
                {{ $imp['dilewati'] }}
            </p>
        </div>
    </div>

    @if(!empty($imp['catatan']))
    <div class="mt-5 pt-5 border-t border-slate-100">
        <p class="text-sm font-semibold text-slate-700 mb-2">Baris yang dilewati</p>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="text-left py-2 font-semibold w-20">Baris</th>
                        <th class="text-left py-2 font-semibold w-40">No PO</th>
                        <th class="text-left py-2 font-semibold">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($imp['catatan'] as $c)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 text-slate-600">{{ $c['baris'] }}</td>
                        <td class="py-2 font-mono text-slate-600">{{ $c['no_po'] }}</td>
                        <td class="py-2 text-slate-600">{{ $c['alasan'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($imp['dilewati'] > count($imp['catatan']))
        <p class="text-xs text-slate-400 mt-2">
            Menampilkan {{ count($imp['catatan']) }} dari {{ $imp['dilewati'] }} baris yang dilewati.
        </p>
        @endif
    </div>
    @endif
</div>
@endif

@if($errors->any())
<div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
    @foreach($errors->all() as $e)
        <p>{{ $e }}</p>
    @endforeach
</div>
@endif

{{-- ==================== PANEL IMPORT ==================== --}}
<div id="panelUpload"
     class="{{ session('error') || $errors->any() || session('import') ? '' : 'hidden' }}
            bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">

    <div class="grid lg:grid-cols-2 gap-6">

        <div>
            <h3 class="text-lg font-semibold text-slate-800 mb-1">Import Dataset</h3>
            <p class="text-xs text-slate-500 mb-4">
                Unggah berkas Excel hasil pembersihan data. Format yang diterima xlsx, xls, atau csv.
            </p>

            <form action="{{ route('produksi.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="file" name="dataset" required
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm bg-white
                           file:mr-4 file:px-4 file:py-2 file:rounded-lg file:border-0
                           file:bg-slate-100 file:text-slate-700 file:text-sm file:font-semibold">

                <div class="mt-4 rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 text-xs text-blue-800 leading-relaxed">
                    Data dicocokkan berdasarkan <strong>no_po</strong>. Pesanan yang belum ada akan
                    ditambahkan, sedangkan pesanan yang sudah ada akan diperbarui isinya, sehingga
                    berkas yang sama dapat diunggah ulang tanpa menimbulkan data ganda.
                </div>

                <div class="flex gap-3 mt-5">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl
                                   font-semibold text-sm">
                        Upload Dataset
                    </button>
                    <button type="button"
                        onclick="document.getElementById('panelUpload').classList.add('hidden')"
                        class="px-5 py-3 rounded-xl font-semibold text-sm text-slate-600 hover:bg-slate-100">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5">
            <p class="text-sm font-semibold text-slate-700 mb-3">
                Kolom yang wajib ada pada berkas
            </p>
            <p class="text-xs text-slate-500 mb-2">Wajib ada</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-slate-700 font-mono mb-4">
                <span>no_po</span>
                <span>tanggal_order</span>
                <span>keterangan_barang</span>
                <span>qty</span>
                <span>pekerja</span>
                <span>target_selesai</span>
                <span>terlambat</span>
            </div>

            <p class="text-xs text-slate-500 mb-2">Opsional</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-slate-500 font-mono">
                <span>warna</span>
                <span>durasi_target_hari</span>
            </div>

            <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                Nama kolom harus berada pada baris pertama berkas dan ditulis persis seperti
                di atas. Kolom durasi_target_hari akan dihitung otomatis dari selisih tanggal
                apabila tidak diisi. Kolom terlambat diisi 0 untuk tepat waktu dan 1 untuk terlambat.
            </p>
        </div>
    </div>
</div>

{{-- ==================== RINGKASAN ==================== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Total Data</p>
        <h2 class="text-3xl font-bold text-slate-800 mt-1">{{ number_format($r['total']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">
            @if($r['awal'] && $r['akhir'])
                {{ \Carbon\Carbon::parse($r['awal'])->format('M Y') }}
                &ndash;
                {{ \Carbon\Carbon::parse($r['akhir'])->format('M Y') }}
            @else
                Belum ada data
            @endif
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Tepat Waktu</p>
        <h2 class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($r['tepat']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">{{ 100 - $pctTel }}% dari dataset</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Terlambat</p>
        <h2 class="text-3xl font-bold text-red-600 mt-1">{{ number_format($r['terlambat']) }}</h2>
        <p class="text-xs text-slate-400 mt-2">{{ $pctTel }}% dari dataset</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Rata-rata Durasi Target</p>
        <h2 class="text-3xl font-bold text-blue-600 mt-1">{{ $r['durasi_target'] }}</h2>
        <p class="text-xs text-slate-400 mt-2">hari per pesanan</p>
    </div>
</div>

{{-- ==================== TABEL ==================== --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

    <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-lg text-slate-800">Dataset Historis Produksi</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Menampilkan {{ $produksi->firstItem() ?? 0 }}&ndash;{{ $produksi->lastItem() ?? 0 }}
                dari {{ number_format($produksi->total()) }} data
            </p>
        </div>

        <form method="GET" action="{{ route('produksi.index') }}"
              class="flex flex-wrap items-center gap-2">

            <input type="text" name="cari" value="{{ request('cari') }}"
                placeholder="Cari no PO, jenis, warna"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm w-52
                       focus:ring-2 focus:ring-blue-500 outline-none">

            <select name="status"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white
                       focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Semua status</option>
                <option value="1" @selected(request('status')==='1')>Terlambat</option>
                <option value="0" @selected(request('status')==='0')>Tepat Waktu</option>
            </select>

            @if(isset($jenisList) && count($jenisList))
            <select name="jenis"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white
                       focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Semua jenis</option>
                @foreach($jenisList as $j)
                <option value="{{ $j }}" @selected(request('jenis')==$j)>{{ $j }}</option>
                @endforeach
            </select>
            @endif

            <select name="per_page" onchange="this.form.submit()"
                class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white
                       focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach([15,25,50,100] as $n)
                <option value="{{ $n }}" @selected(request('per_page', 15)==$n)>{{ $n }} / hal</option>
                @endforeach
            </select>

            <button class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold
                           px-4 py-2 rounded-xl">
                Filter
            </button>

            @if($adaFilter)
            <a href="{{ route('produksi.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700 px-2">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm whitespace-nowrap">
            <thead class="bg-slate-50">
                <tr class="text-xs text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left font-semibold">No</th>
                    <th class="px-4 py-3 text-left font-semibold">No PO</th>
                    <th class="px-4 py-3 text-left font-semibold">Tgl Order</th>
                    <th class="px-4 py-3 text-left font-semibold">Jenis Barang</th>
                    <th class="px-4 py-3 text-left font-semibold">Warna</th>
                    <th class="px-4 py-3 text-right font-semibold">Qty</th>
                    <th class="px-4 py-3 text-right font-semibold">Pekerja</th>
                    <th class="px-4 py-3 text-left font-semibold">Target Selesai</th>
                    <th class="px-4 py-3 text-right font-semibold">Durasi</th>
                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($produksi as $item)
                @php $telat = (int) $item->terlambat === 1; @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                    <td class="px-4 py-3 text-slate-400">
                        {{ $produksi->firstItem() + $loop->index }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">
                        {{ $item->no_po }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $item->tanggal_order ? $item->tanggal_order->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-700">
                        {{ $item->jenis_barang }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $item->warna ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700 font-semibold">
                        {{ number_format($item->qty) }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-600">
                        {{ $item->jumlah_pekerja }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $item->target_selesai ? $item->target_selesai->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-600">
                        {{ $item->durasi_target }} hari
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex text-xs px-3 py-1 rounded-lg font-semibold
                            {{ $telat ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $telat ? 'Terlambat' : 'Tepat Waktu' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-14">
                        <p class="text-slate-600 font-semibold">Belum ada data produksi</p>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $adaFilter
                                ? 'Tidak ada data yang sesuai dengan filter yang dipilih.'
                                : 'Import dataset terlebih dahulu untuk mulai menggunakan sistem.' }}
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($produksi->hasPages())
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
        {{ $produksi->links() }}
    </div>
    @endif
</div>

<p class="text-xs text-slate-400 mt-4">
    Kolom Durasi menampilkan durasi target, yaitu selisih target selesai dengan tanggal order.
    Kolom Status merupakan catatan keterlambatan pesanan yang menjadi variabel target model.
</p>

@endsection
