<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use App\Models\Prediksi;

class DashboardController extends Controller
{
    public function index()
    {
        // ================= RINGKASAN DATA =================
        $dataset   = Produksi::count();
        $prediksi  = Prediksi::count();
        $terlambat = Produksi::where('terlambat', 1)->count();
        $tepat     = Produksi::where('terlambat', 0)->count();

        $persenTerlambat = $dataset > 0 ? round($terlambat / $dataset * 100, 2) : 0;

        // ================= STATISTIK RATA-RATA =================
        $statistik = [
            'qty'            => round(Produksi::avg('qty') ?? 0),
            'pekerja'        => round(Produksi::avg('jumlah_pekerja') ?? 0, 1),
            'durasi_target'  => round(Produksi::avg('durasi_target') ?? 0, 1),
            'qty_max'        => (int) (Produksi::max('qty') ?? 0),
        ];

        // ================= TREN 12 BULAN TERAKHIR =================
        $bulanan = Produksi::selectRaw("DATE_FORMAT(tanggal_order, '%Y-%m') as bulan,
                                        COUNT(*) as total,
                                        SUM(terlambat) as telat")
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $namaBulan = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                      '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Ags',
                      '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'];

        $tren = [
            'label' => [],
            'total' => [],
            'telat' => [],
            'rate'  => [],
        ];

        foreach ($bulanan as $b) {
            [$tahun, $bulan] = explode('-', $b->bulan);
            $tren['label'][] = ($namaBulan[$bulan] ?? $bulan) . ' ' . substr($tahun, 2);
            $tren['total'][] = (int) $b->total;
            $tren['telat'][] = (int) $b->telat;
            $tren['rate'][]  = $b->total > 0 ? round($b->telat / $b->total * 100, 1) : 0;
        }

        // ================= KETERLAMBATAN PER JENIS BARANG =================
        $perJenis = Produksi::selectRaw('jenis_barang,
                                         COUNT(*) as total,
                                         SUM(terlambat) as telat')
            ->groupBy('jenis_barang')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                return [
                    'jenis' => $r->jenis_barang,
                    'total' => (int) $r->total,
                    'telat' => (int) $r->telat,
                    'rate'  => $r->total > 0 ? round($r->telat / $r->total * 100, 1) : 0,
                ];
            });

        // ================= HASIL PREDIKSI =================
        $prediksiTerlambat = Prediksi::where('hasil_prediksi', 'TERLAMBAT')->count();
        $prediksiTepat     = $prediksi - $prediksiTerlambat;

        $prediksiTerbaru = Prediksi::latest()->limit(6)->get();

        // ================= STATUS MODEL =================
        $model = null;
        $path  = base_path('python/models/evaluation.json');

        if (file_exists($path)) {
            $model = json_decode(file_get_contents($path), true);
        }

        return view('dashboard.index', compact(
            'dataset',
            'prediksi',
            'terlambat',
            'tepat',
            'persenTerlambat',
            'statistik',
            'tren',
            'perJenis',
            'prediksiTerlambat',
            'prediksiTepat',
            'prediksiTerbaru',
            'model'
        ));
    }
}
