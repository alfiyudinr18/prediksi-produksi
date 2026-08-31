<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Prediksi') &mdash; PT APUC</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-slate-100 text-slate-800 antialiased">

@php
    // Daftar menu: label, nama route, pola route aktif, ikon (path svg)
    $menu = [
        'Utama' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'aktif' => 'dashboard',
                'icon'  => 'M3 12l2-2m0 0l7-7 7 7m-9 2v8m0-8H5m7 0h7',
            ],
        ],
        'Data & Model' => [
            [
                'label' => 'Data Produksi',
                'route' => 'produksi.index',
                'aktif' => 'produksi.*',
                'icon'  => 'M3 10h18M3 6h18M3 14h18M3 18h18',
            ],
            [
                'label' => 'Training Model',
                'route' => 'training.index',
                'aktif' => 'training.*',
                'icon'  => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
        ],
        'Prediksi' => [
            [
                'label' => 'Prediksi Baru',
                'route' => 'prediksi.index',
                'aktif' => 'prediksi.index',
                'icon'  => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            ],
            [
                'label' => 'Riwayat Prediksi',
                'route' => 'prediksi.history',
                'aktif' => 'prediksi.history',
                'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ],
    ];
@endphp

<a href="#konten"
   class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50
          focus:bg-white focus:text-slate-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow">
    Lompat ke konten
</a>

{{-- ==================== SIDEBAR ==================== --}}
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 flex flex-col
              -translate-x-full lg:translate-x-0 transition-all duration-200 ease-out
              overflow-hidden">

    <div id="brandBar" class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 flex-none">
        <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center
                    font-bold text-sm flex-none">
            AP
        </div>
        <div class="leading-tight min-w-0" data-label>
            <p class="text-white font-semibold text-sm truncate">PT APUC</p>
            <p class="text-slate-400 truncate" style="font-size:11px">Prediksi Produksi</p>
        </div>
        <button id="tutupSidebar" type="button"
                class="ml-auto lg:hidden text-slate-400 hover:text-white p-1 rounded"
                aria-label="Tutup menu">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4" aria-label="Menu utama">
        @foreach($menu as $grup => $items)
            <p class="px-5 pt-3 pb-2 text-slate-500 font-semibold uppercase tracking-wider"
               style="font-size:10px" data-label>{{ $grup }}</p>

            @foreach($items as $m)
                @php $aktif = request()->routeIs($m['aktif']); @endphp
                <a href="{{ route($m['route']) }}"
                   title="{{ $m['label'] }}"
                   data-nav
                   @if($aktif) aria-current="page" @endif
                   class="relative flex items-center gap-3 px-5 py-2.5 text-sm transition
                          {{ $aktif
                                ? 'bg-slate-800 text-white font-semibold'
                                : 'hover:bg-slate-800/60 hover:text-white' }}">
                    @if($aktif)
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-r"></span>
                    @endif
                    <svg class="w-5 h-5 flex-none {{ $aktif ? 'text-blue-400' : 'text-slate-400' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="{{ $m['icon'] }}"/>
                    </svg>
                    <span data-label>{{ $m['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="flex-none p-4 border-t border-slate-800">
        <div id="userBar" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-slate-700 text-white flex items-center
                        justify-center font-semibold text-sm flex-none">A</div>
            <div class="min-w-0" data-label>
                <p class="text-white text-sm font-semibold truncate">Admin</p>
                <p class="text-slate-400 truncate" style="font-size:11px">Bagian Produksi</p>
            </div>
        </div>
    </div>
</aside>

{{-- Lapisan gelap saat sidebar terbuka di layar kecil --}}
<div id="overlay"
     class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden"
     aria-hidden="true"></div>

{{-- ==================== KONTEN ==================== --}}
<div id="wrapper" class="lg:pl-64 min-h-screen flex flex-col transition-all duration-200">

    <header class="sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-slate-200">
        <div class="h-16 flex items-center gap-3 px-4 sm:px-6 lg:px-8">

            {{-- Tombol buka menu untuk layar kecil --}}
            <button id="bukaSidebar" type="button"
                    class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100"
                    aria-label="Buka menu">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Tombol tutup buka sidebar untuk layar besar --}}
            <button id="toggleSidebar" type="button"
                    class="hidden lg:inline-flex p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100"
                    aria-label="Tutup atau buka menu" aria-expanded="true" title="Tutup menu">
                <svg id="ikonToggle" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-semibold text-slate-800 truncate">
                    @yield('title', 'Dashboard')
                </h1>
                <p class="text-slate-500 hidden sm:block" style="font-size:11px">
                    Sistem Prediksi Keterlambatan Produksi Garmen
                </p>
            </div>

            {{-- <div class="ml-auto flex items-center gap-4">
                <span class="hidden md:inline text-sm text-slate-500">
                    {{ now()->translatedFormat('l, d F Y') }}
                </span>
                <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center
                            justify-center font-semibold text-sm">A</div>
            </div> --}}
        </div>
    </header>

    <main id="konten" class="flex-1 p-4 sm:p-6 lg:p-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="px-4 sm:px-6 lg:px-8 py-4 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Sistem Prediksi Keterlambatan Produksi Garmen &mdash;
            PT Agung Perkasa Utama Cemerlang
        </div>
    </footer>
</div>

<script>
(function () {
    var sidebar = document.getElementById('sidebar');
    var wrapper = document.getElementById('wrapper');
    var overlay = document.getElementById('overlay');
    var buka    = document.getElementById('bukaSidebar');
    var tutup   = document.getElementById('tutupSidebar');
    var toggle  = document.getElementById('toggleSidebar');

    var labels  = document.querySelectorAll('[data-label]');
    var navs    = document.querySelectorAll('[data-nav]');

    var KUNCI   = 'sidebarMenyempit';

    /* ---------- Buka tutup pada layar kecil ---------- */
    function bukaMenu() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    }

    function tutupMenu() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }

    if (buka)    buka.addEventListener('click', bukaMenu);
    if (tutup)   tutup.addEventListener('click', tutupMenu);
    if (overlay) overlay.addEventListener('click', tutupMenu);

    /* ---------- Menyempitkan sidebar pada layar besar ---------- */
    function terapkan(menyempit) {

        if (menyempit) {
            sidebar.classList.add('lg:w-20');
            wrapper.classList.remove('lg:pl-64');
            wrapper.classList.add('lg:pl-20');
            labels.forEach(function (el) { el.classList.add('lg:hidden'); });
            navs.forEach(function (el) {
                el.classList.add('lg:justify-center', 'lg:px-0');
            });
        } else {
            sidebar.classList.remove('lg:w-20');
            wrapper.classList.remove('lg:pl-20');
            wrapper.classList.add('lg:pl-64');
            labels.forEach(function (el) { el.classList.remove('lg:hidden'); });
            navs.forEach(function (el) {
                el.classList.remove('lg:justify-center', 'lg:px-0');
            });
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', menyempit ? 'false' : 'true');
            toggle.setAttribute('title', menyempit ? 'Buka menu' : 'Tutup menu');
        }

        try { localStorage.setItem(KUNCI, menyempit ? '1' : '0'); } catch (e) {}
    }

    var tersimpan = '0';
    try { tersimpan = localStorage.getItem(KUNCI) || '0'; } catch (e) {}

    terapkan(tersimpan === '1');

    if (toggle) {
        toggle.addEventListener('click', function () {
            terapkan(!sidebar.classList.contains('lg:w-20'));
        });
    }

    /* ---------- Tombol Escape menutup menu layar kecil ---------- */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupMenu();
    });
})();
</script>

@stack('scripts')

</body>
</html>
