<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Prediksi;

class PrediksiController extends Controller
{
    /**
     * Peta jenis barang beserta bobot kesusahan pengerjaannya.
     * Harus sama persis dengan mapping BOBOT_BARANG pada train_model.py dan predict.py.
     */
    public const BOBOT_BARANG = [
        'Bahan / Kain'          => 1,
        'Kaos Oblong'           => 1,
        'Kaos Tangan Pendek'    => 1,
        'Topi'                  => 1,
        'Kaos Tangan Panjang'   => 2,
        'Tas'                   => 2,
        'Wangky Tangan Pendek'  => 2,
        'Celana'                => 3,
        'Rompi'                 => 3,
        'Seragam & Rok'         => 3,
        'Wangky Tangan Panjang' => 3,
        'Kemeja Tangan Pendek'  => 4,
        'Kemeja Tangan Panjang' => 4,
        'Hoodie & Jaket'        => 5,
    ];

    /** Kapasitas satu pekerja per hari dalam pcs ekuivalen barang berbobot 1 */
    public const KAPASITAS_PEKERJA = 10;

    public function index()
    {
        return view('prediksi.index', [
            'jenisBarang' => array_keys(self::BOBOT_BARANG),
        ]);
    }

    public function proses(Request $request)
    {
        // =====================================================
        // NODE 2 : DATA VALID ?
        // Seluruh aturan validasi masukan diperiksa di sini.
        // Apabila salah satu aturan tidak terpenuhi, Laravel
        // mengembalikan pengguna ke form beserta pesan error
        // (NODE 3 : Tampilkan pesan error).
        // =====================================================
        $validated = $request->validate(
            [
                'qty' => 'required|integer|min:1|max:100000',

                'jenis_barang' => ['required', Rule::in(array_keys(self::BOBOT_BARANG))],

                'jumlah_pekerja' => 'required|integer|min:1|max:100',

                'tanggal_order' => 'required|date|before_or_equal:target_selesai',

                'target_selesai' => 'required|date|after:tanggal_order',
            ],
            [
                'qty.required'            => 'Qty produksi wajib diisi.',
                'qty.integer'             => 'Qty produksi harus berupa angka bulat.',
                'qty.min'                 => 'Qty produksi minimal 1 pcs.',
                'qty.max'                 => 'Qty produksi maksimal 100.000 pcs.',

                'jenis_barang.required'   => 'Jenis barang wajib dipilih.',
                'jenis_barang.in'         => 'Jenis barang tidak dikenali oleh model prediksi.',

                'jumlah_pekerja.required' => 'Jumlah pekerja wajib diisi.',
                'jumlah_pekerja.integer'  => 'Jumlah pekerja harus berupa angka bulat.',
                'jumlah_pekerja.min'      => 'Jumlah pekerja minimal 1 orang.',
                'jumlah_pekerja.max'      => 'Jumlah pekerja maksimal 100 orang.',

                'tanggal_order.required'  => 'Tanggal order wajib diisi.',
                'tanggal_order.date'      => 'Format tanggal order tidak valid.',

                'target_selesai.required' => 'Target selesai wajib diisi.',
                'target_selesai.date'     => 'Format target selesai tidak valid.',
                'target_selesai.after'    => 'Target selesai harus setelah tanggal order.',
            ]
        );

        // =====================================================
        // NODE 4 - 7 : HITUNG DURASI TARGET, ENCODE JENIS BARANG,
        // MEMUAT MODEL RANDOM FOREST, DAN MELAKUKAN PREDIKSI.
        // Keempat proses tersebut dijalankan oleh predict.py.
        // =====================================================
        $python = "python";
        $script = base_path('python/scripts/predict.py');

        $command =
            $python . " " . $script . " " .
            escapeshellarg($validated['qty']) . " " .
            escapeshellarg($validated['jenis_barang']) . " " .
            escapeshellarg($validated['jumlah_pekerja']) . " " .
            escapeshellarg($validated['tanggal_order']) . " " .
            escapeshellarg($validated['target_selesai']);

        $output = shell_exec($command);

        $hasil = json_decode($output, true);

        // Validasi keluaran proses prediksi (kembali ke NODE 3 bila gagal)
        if (!is_array($hasil) || isset($hasil['error'])) {

            $pesan = $hasil['error']
                ?? 'Proses prediksi gagal dijalankan. Pastikan model sudah dilatih pada menu Training Model.';

            return back()->withErrors(['prediksi' => $pesan])->withInput();
        }

        if (!isset($hasil['hasil'], $hasil['durasi_target'], $hasil['probabilitas'])) {

            return back()
                ->withErrors(['prediksi' => 'Keluaran proses prediksi tidak lengkap.'])
                ->withInput();
        }

        // =====================================================
        // NODE 8 - 10 : HASIL = 1 ?
        // Bernilai benar apabila model mengklasifikasikan pesanan
        // sebagai TERLAMBAT, dan salah apabila TEPAT WAKTU.
        // =====================================================
        $terlambat = strtoupper($hasil['hasil']) === 'TERLAMBAT';

        $hasil['hasil'] = $terlambat ? 'TERLAMBAT' : 'TEPAT WAKTU';

        // =====================================================
        // NODE 11 - 12 : HITUNG PROBABILITAS DAN SIMPAN HASIL
        // =====================================================
        $probabilitas = round((float) $hasil['probabilitas'], 2);

        Prediksi::create([
            'qty'            => $validated['qty'],
            'jenis_barang'   => $validated['jenis_barang'],
            'jumlah_pekerja' => $validated['jumlah_pekerja'],
            'tanggal_order'  => $validated['tanggal_order'],
            'target_selesai' => $validated['target_selesai'],
            'durasi_target'  => (int) $hasil['durasi_target'],
            'hasil_prediksi' => $hasil['hasil'],
            'probabilitas'   => $probabilitas,
        ]);

        // =====================================================
        // NODE 13 : TAMPILKAN HASIL PREDIKSI
        // =====================================================
        return view('prediksi.hasil', [
            'hasil' => $hasil,
            'input' => $validated,
        ]);
    }

    /**
     * Menampilkan detail satu riwayat prediksi.
     */
    public function show($id)
    {
        $prediksi = Prediksi::findOrFail($id);

        $bobot = self::BOBOT_BARANG[$prediksi->jenis_barang] ?? null;

        // Beban kerja = pcs ekuivalen yang harus diselesaikan satu pekerja per hari
        $beban = null;
        $pekerjaIdeal = null;

        if ($bobot && $prediksi->durasi_target > 0 && $prediksi->jumlah_pekerja > 0) {

            $beban = round(
                ($prediksi->qty * $bobot) / ($prediksi->jumlah_pekerja * $prediksi->durasi_target),
                2
            );

            $pekerjaIdeal = (int) ceil(
                ($prediksi->qty * $bobot) / (self::KAPASITAS_PEKERJA * $prediksi->durasi_target)
            );
        }

        return view('prediksi.detail', compact('prediksi', 'bobot', 'beban', 'pekerjaIdeal'));
    }

    public function history(Request $request)
    {
        $query = Prediksi::query();

        if ($request->filled('hasil')) {
            $query->where('hasil_prediksi', $request->hasil);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_barang', $request->jenis);
        }

        $data = $query->latest()->paginate(10)->withQueryString();

        $ringkasan = [
            'total'     => Prediksi::count(),
            'terlambat' => Prediksi::where('hasil_prediksi', 'TERLAMBAT')->count(),
        ];

        $ringkasan['tepat'] = $ringkasan['total'] - $ringkasan['terlambat'];

        $jenisList = Prediksi::select('jenis_barang')
            ->distinct()
            ->orderBy('jenis_barang')
            ->pluck('jenis_barang');

        return view('prediksi.history', compact('data', 'ringkasan', 'jenisList'));
    }
}
