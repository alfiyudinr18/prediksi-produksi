@extends('layouts.app')

@section('title','Prediksi Produksi')

@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800">Prediksi Keterlambatan Produksi</h1>
    <p class="text-slate-500 mt-1">
        Masukkan data pesanan untuk mengetahui prediksi keterlambatan menggunakan
        algoritma Random Forest.
    </p>
</div>

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6">
    <p class="font-semibold mb-1">Prediksi tidak dapat diproses</p>
    <ul class="text-sm list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">

    {{-- ============ FORM ============ --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200">

        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-800">Form Prediksi</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Semua kolom wajib diisi sesuai kondisi pesanan yang akan dikerjakan.
            </p>
        </div>

        <form action="{{ route('prediksi.proses') }}" method="POST" class="p-6" id="formPrediksi">
            @csrf

            <div class="grid md:grid-cols-2 gap-5">

                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-semibold text-slate-700">
                        Jenis Barang
                    </label>
                    <select name="jenis_barang" required
                        class="w-full border rounded-xl px-4 py-3 bg-white outline-none transition
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               @error('jenis_barang') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <option value="">-- Pilih Jenis Barang --</option>
                        <optgroup label="Tingkat kesulitan 1 (sangat mudah)">
                            <option value="Bahan / Kain" @selected(old('jenis_barang')=='Bahan / Kain')>Bahan / Kain</option>
                            <option value="Kaos Oblong" @selected(old('jenis_barang')=='Kaos Oblong')>Kaos Oblong</option>
                            <option value="Kaos Tangan Pendek" @selected(old('jenis_barang')=='Kaos Tangan Pendek')>Kaos Tangan Pendek</option>
                            <option value="Topi" @selected(old('jenis_barang')=='Topi')>Topi</option>
                        </optgroup>
                        <optgroup label="Tingkat kesulitan 2 (mudah)">
                            <option value="Kaos Tangan Panjang" @selected(old('jenis_barang')=='Kaos Tangan Panjang')>Kaos Tangan Panjang</option>
                            <option value="Tas" @selected(old('jenis_barang')=='Tas')>Tas</option>
                            <option value="Wangky Tangan Pendek" @selected(old('jenis_barang')=='Wangky Tangan Pendek')>Wangky Tangan Pendek</option>
                        </optgroup>
                        <optgroup label="Tingkat kesulitan 3 (sedang)">
                            <option value="Celana" @selected(old('jenis_barang')=='Celana')>Celana</option>
                            <option value="Rompi" @selected(old('jenis_barang')=='Rompi')>Rompi</option>
                            <option value="Seragam & Rok" @selected(old('jenis_barang')=='Seragam & Rok')>Seragam &amp; Rok</option>
                            <option value="Wangky Tangan Panjang" @selected(old('jenis_barang')=='Wangky Tangan Panjang')>Wangky Tangan Panjang</option>
                        </optgroup>
                        <optgroup label="Tingkat kesulitan 4 (sulit)">
                            <option value="Kemeja Tangan Pendek" @selected(old('jenis_barang')=='Kemeja Tangan Pendek')>Kemeja Tangan Pendek</option>
                            <option value="Kemeja Tangan Panjang" @selected(old('jenis_barang')=='Kemeja Tangan Panjang')>Kemeja Tangan Panjang</option>
                        </optgroup>
                        <optgroup label="Tingkat kesulitan 5 (sangat sulit)">
                            <option value="Hoodie & Jaket" @selected(old('jenis_barang')=='Hoodie & Jaket')>Hoodie &amp; Jaket</option>
                        </optgroup>
                    </select>
                    @error('jenis_barang')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @else
                        <p class="text-xs text-slate-400 mt-1.5">
                            Jenis barang menentukan bobot kesusahan pengerjaan yang dipakai model.
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">
                        Qty Produksi
                    </label>
                    <div class="relative">
                        <input type="number" name="qty" id="qty" min="1" max="100000" required
                            value="{{ old('qty') }}" placeholder="mis. 500"
                            class="w-full border rounded-xl px-4 py-3 pr-14 outline-none transition
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                   @error('qty') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400">pcs</span>
                    </div>
                    @error('qty')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">
                        Jumlah Pekerja
                    </label>
                    <div class="relative">
                        <input type="number" name="jumlah_pekerja" id="pekerja" min="1" max="100" required
                            value="{{ old('jumlah_pekerja') }}" placeholder="mis. 5"
                            class="w-full border rounded-xl px-4 py-3 pr-16 outline-none transition
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                   @error('jumlah_pekerja') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400">orang</span>
                    </div>
                    @error('jumlah_pekerja')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">
                        Tanggal Order
                    </label>
                    <input type="date" name="tanggal_order" id="tglOrder" required
                        value="{{ old('tanggal_order') }}"
                        class="w-full border rounded-xl px-4 py-3 outline-none transition
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               @error('tanggal_order') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                    @error('tanggal_order')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-slate-700">
                        Target Selesai
                    </label>
                    <input type="date" name="target_selesai" id="tglTarget" required
                        value="{{ old('target_selesai') }}"
                        class="w-full border rounded-xl px-4 py-3 outline-none transition
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               @error('target_selesai') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                    @error('target_selesai')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <div id="boxDurasi"
                         class="hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Durasi target: <strong id="txtDurasi">0</strong> hari
                    </div>
                    <div id="boxDurasiSalah"
                         class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        Target selesai harus setelah tanggal order.
                    </div>
                </div>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('prediksi.history') }}"
                   class="text-sm font-semibold text-slate-600 hover:text-slate-800 px-4 py-3">
                    Lihat riwayat
                </a>
                <button id="btnSubmit"
                    class="bg-blue-600 hover:bg-blue-700 transition text-white px-8 py-3 rounded-xl
                           font-semibold shadow-sm">
                    <span id="btnLabel">Prediksi Sekarang</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ============ PANEL INFORMASI ============ --}}
    <div class="space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-1">Cara Kerja</h3>
            <p class="text-xs text-slate-500 mb-4">
                Model memakai empat variabel untuk memprediksi keterlambatan.
            </p>
            <ol class="space-y-3 text-sm text-slate-600">
                <li class="flex gap-3">
                    <span class="flex-none w-6 h-6 rounded-lg bg-blue-100 text-blue-700 font-semibold
                                 flex items-center justify-center text-xs">1</span>
                    <span>Qty produksi diambil langsung dari isian form.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-none w-6 h-6 rounded-lg bg-blue-100 text-blue-700 font-semibold
                                 flex items-center justify-center text-xs">2</span>
                    <span>Jenis barang dikonversi menjadi bobot kesusahan 1 sampai 5.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-none w-6 h-6 rounded-lg bg-blue-100 text-blue-700 font-semibold
                                 flex items-center justify-center text-xs">3</span>
                    <span>Durasi target dihitung otomatis dari selisih target selesai dan tanggal order.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-none w-6 h-6 rounded-lg bg-blue-100 text-blue-700 font-semibold
                                 flex items-center justify-center text-xs">4</span>
                    <span>Jumlah pekerja menentukan kapasitas pengerjaan per hari.</span>
                </li>
            </ol>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Bobot Kesusahan</h3>
            <div class="space-y-2.5 text-sm">
                @php
                    $bobot = [
                        [1, 'Bahan/Kain, Kaos Oblong, Kaos Tangan Pendek, Topi', 'bg-emerald-500'],
                        [2, 'Kaos Tangan Panjang, Tas, Wangky Tangan Pendek',    'bg-lime-500'],
                        [3, 'Celana, Rompi, Seragam & Rok, Wangky Tgn Panjang',  'bg-amber-500'],
                        [4, 'Kemeja Tangan Pendek, Kemeja Tangan Panjang',       'bg-orange-500'],
                        [5, 'Hoodie & Jaket',                                    'bg-red-500'],
                    ];
                @endphp
                @foreach($bobot as $b)
                <div class="flex gap-3">
                    <span class="flex-none w-6 h-6 rounded-lg {{ $b[2] }} text-white font-semibold
                                 flex items-center justify-center text-xs">{{ $b[0] }}</span>
                    <span class="text-slate-600 text-xs leading-relaxed pt-0.5">{{ $b[1] }}</span>
                </div>
                @endforeach
            </div>
            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Semakin tinggi bobot, semakin lama waktu pengerjaan per potong barang.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var order  = document.getElementById('tglOrder');
    var target = document.getElementById('tglTarget');
    var box    = document.getElementById('boxDurasi');
    var boxErr = document.getElementById('boxDurasiSalah');
    var txt    = document.getElementById('txtDurasi');

    function hitung() {
        box.classList.add('hidden');
        boxErr.classList.add('hidden');
        if (!order.value || !target.value) return;

        var selisih = Math.round(
            (new Date(target.value) - new Date(order.value)) / 86400000
        );

        if (selisih <= 0) {
            boxErr.classList.remove('hidden');
        } else {
            txt.textContent = selisih;
            box.classList.remove('hidden');
        }
    }

    order.addEventListener('change', hitung);
    target.addEventListener('change', hitung);
    hitung();

    document.getElementById('formPrediksi').addEventListener('submit', function (e) {
        // Validasi sisi klien: target selesai harus setelah tanggal order
        if (order.value && target.value &&
            (new Date(target.value) - new Date(order.value)) <= 0) {
            e.preventDefault();
            boxErr.classList.remove('hidden');
            target.focus();
            return false;
        }

        var btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.className = btn.className + ' opacity-60 cursor-wait';
        document.getElementById('btnLabel').textContent = 'Memproses...';
    });
});
</script>

@endsection
