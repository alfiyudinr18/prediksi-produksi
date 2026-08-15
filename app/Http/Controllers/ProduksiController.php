<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produksi;
use App\Imports\ProduksiImport;
use Maatwebsite\Excel\Facades\Excel;

class ProduksiController extends Controller
{
    public function index(Request $request)
    {
        $query = Produksi::query();

        // Pencarian nomor PO, jenis barang, atau warna
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('no_po', 'like', "%{$cari}%")
                  ->orWhere('jenis_barang', 'like', "%{$cari}%")
                  ->orWhere('warna', 'like', "%{$cari}%");
            });
        }

        // Filter status keterlambatan
        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('terlambat', (int) $request->status);
        }

        // Filter jenis barang
        if ($request->filled('jenis')) {
            $query->where('jenis_barang', $request->jenis);
        }

        $perPage = in_array($request->per_page, [15, 25, 50, 100]) ? (int) $request->per_page : 15;

        $produksi = $query->orderBy('tanggal_order', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Ringkasan seluruh dataset (tidak terpengaruh filter)
        $ringkasan = [
            'total'         => Produksi::count(),
            'terlambat'     => Produksi::where('terlambat', 1)->count(),
            'durasi_target' => round(Produksi::avg('durasi_target') ?? 0, 1),
            'qty'           => round(Produksi::avg('qty') ?? 0),
            'awal'          => Produksi::min('tanggal_order'),
            'akhir'         => Produksi::max('tanggal_order'),
        ];

        $ringkasan['tepat'] = $ringkasan['total'] - $ringkasan['terlambat'];

        $jenisList = Produksi::select('jenis_barang')
            ->distinct()
            ->orderBy('jenis_barang')
            ->pluck('jenis_barang');

        return view('produksi.index', compact('produksi', 'ringkasan', 'jenisList'));
    }

    public function import()
    {
        return view('produksi.import');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'dataset' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'dataset.required' => 'Berkas dataset wajib dipilih.',
            'dataset.mimes'    => 'Berkas harus berformat xlsx, xls, atau csv.',
            'dataset.max'      => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $import = new ProduksiImport;

        try {

            Excel::import($import, $request->file('dataset'));

        } catch (\Throwable $e) {

            return redirect()
                ->route('produksi.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }

        // Ringkasan hasil import untuk ditampilkan pada halaman data produksi
        $ringkas = [
            'baru'       => $import->baru,
            'diperbarui' => $import->diperbarui,
            'dilewati'   => $import->dilewati,
            'catatan'    => $import->catatan,
        ];

        if ($ringkas['baru'] === 0 && $ringkas['diperbarui'] === 0) {

            return redirect()
                ->route('produksi.index')
                ->with('error', 'Tidak ada data yang berhasil diimport. Periksa kembali isi berkas.')
                ->with('import', $ringkas);
        }

        return redirect()
            ->route('produksi.index')
            ->with('success', 'Import dataset selesai diproses.')
            ->with('import', $ringkas);
    }
}
