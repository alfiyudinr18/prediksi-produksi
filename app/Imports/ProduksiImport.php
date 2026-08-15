<?php

namespace App\Imports;

use App\Models\Produksi;

use Carbon\Carbon;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProduksiImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /** Jumlah data baru yang ditambahkan */
    public int $baru = 0;

    /** Jumlah data lama yang diperbarui berdasarkan no_po */
    public int $diperbarui = 0;

    /** Jumlah baris yang dilewati karena tidak dapat diproses */
    public int $dilewati = 0;

    /** Catatan baris yang dilewati beserta alasannya */
    public array $catatan = [];

    /**
     * Kolom yang wajib ada pada berkas Excel.
     */
    public const KOLOM_WAJIB = [
        'no_po',
        'tanggal_order',
        'keterangan_barang',
        'qty',
        'pekerja',
        'target_selesai',
        'terlambat',
    ];

    public function collection(Collection $rows)
    {
        // ---------- Periksa kelengkapan kolom pada berkas ----------
        if ($rows->isNotEmpty()) {

            $kolomBerkas = array_keys($rows->first()->toArray());

            $kurang = array_diff(self::KOLOM_WAJIB, $kolomBerkas);

            if (count($kurang)) {
                throw new \Exception(
                    'Kolom berikut tidak ditemukan pada berkas: ' . implode(', ', $kurang) . '.'
                );
            }
        }

        foreach ($rows as $i => $row) {

            $baris = $i + 2; // baris 1 adalah judul kolom

            // ---------- Lewati baris kosong ----------
            if (blank($row['no_po'] ?? null)) {
                continue;
            }

            $noPo = trim((string) $row['no_po']);

            // ---------- Konversi tanggal ----------
            $tanggalOrder  = $this->tanggal($row['tanggal_order'] ?? null);
            $targetSelesai = $this->tanggal($row['target_selesai'] ?? null);

            if (!$tanggalOrder || !$targetSelesai) {
                $this->lewati($baris, $noPo, 'Tanggal order atau target selesai tidak dapat dibaca.');
                continue;
            }

            if ($targetSelesai->lessThanOrEqualTo($tanggalOrder)) {
                $this->lewati($baris, $noPo, 'Target selesai lebih awal atau sama dengan tanggal order.');
                continue;
            }

            // ---------- Durasi target ----------
            $durasiTarget = is_numeric($row['durasi_target_hari'] ?? null)
                ? (int) $row['durasi_target_hari']
                : $tanggalOrder->diffInDays($targetSelesai);

            if ($durasiTarget <= 0) {
                $this->lewati($baris, $noPo, 'Durasi target harus lebih besar dari 0 hari.');
                continue;
            }

            // ---------- Status keterlambatan ----------
            $nilaiStatus = $this->status($row['terlambat'] ?? null);

            if (is_null($nilaiStatus)) {
                $this->lewati($baris, $noPo, 'Status keterlambatan harus bernilai 0 atau 1.');
                continue;
            }

            $terlambat = $nilaiStatus;

            $data = [
                'tanggal_order'   => $tanggalOrder,
                'jenis_barang'    => trim((string) $row['keterangan_barang']),
                'warna'           => $row['warna'] ?? null,
                'qty'             => (int) $row['qty'],
                'jumlah_pekerja'  => (int) $row['pekerja'],
                'target_selesai'  => $targetSelesai,
                'durasi_target'   => $durasiTarget,
                'terlambat'       => $terlambat,
            ];

            // ---------- Tambah data baru atau perbarui data lama ----------
            $produksi = Produksi::where('no_po', $noPo)->first();

            if ($produksi) {
                $produksi->update($data);
                $this->diperbarui++;
            } else {
                Produksi::create(array_merge(['no_po' => $noPo], $data));
                $this->baru++;
            }
        }
    }

    /**
     * Aturan validasi setiap baris berkas.
     */
    public function rules(): array
    {
        return [
            'no_po'             => ['required'],
            'keterangan_barang' => ['required'],
            'qty'               => ['required', 'numeric', 'min:1'],
            'pekerja'           => ['required', 'numeric', 'min:1'],
            'tanggal_order'     => ['required'],
            'target_selesai'    => ['required'],
            'terlambat'         => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'no_po.required'             => 'Kolom no_po wajib diisi.',
            'keterangan_barang.required' => 'Kolom keterangan_barang wajib diisi.',
            'qty.required'               => 'Kolom qty wajib diisi.',
            'qty.numeric'                => 'Kolom qty harus berupa angka.',
            'qty.min'                    => 'Kolom qty minimal bernilai 1.',
            'pekerja.required'           => 'Kolom pekerja wajib diisi.',
            'pekerja.numeric'            => 'Kolom pekerja harus berupa angka.',
            'pekerja.min'                => 'Kolom pekerja minimal bernilai 1.',
            'tanggal_order.required'     => 'Kolom tanggal_order wajib diisi.',
            'target_selesai.required'    => 'Kolom target_selesai wajib diisi.',
            'terlambat.required'         => 'Kolom terlambat wajib diisi dengan nilai 0 atau 1.',
        ];
    }

    /**
     * Baris yang gagal validasi dicatat, bukan menghentikan seluruh proses import.
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {

            $this->lewati(
                $failure->row(),
                '-',
                implode(' ', $failure->errors())
            );
        }
    }

    /**
     * Ubah nilai tanggal dari berkas Excel menjadi objek Carbon.
     * Mendukung format serial Excel maupun teks.
     */
    private function tanggal($nilai): ?Carbon
    {
        if (blank($nilai)) {
            return null;
        }

        try {

            if (is_numeric($nilai)) {
                return Carbon::instance(Date::excelToDateTimeObject($nilai))->startOfDay();
            }

            $nilai = trim((string) $nilai);

            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd M Y'] as $format) {
                $tanggal = Carbon::createFromFormat($format, $nilai);
                if ($tanggal && $tanggal->format($format) === $nilai) {
                    return $tanggal->startOfDay();
                }
            }

            return Carbon::parse($nilai)->startOfDay();

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ubah nilai kolom terlambat menjadi 0 atau 1.
     * Menerima angka 0/1 maupun teks seperti TRUE, FALSE, Terlambat, dan Tepat Waktu.
     */
    private function status($nilai): ?int
    {
        if (is_null($nilai) || $nilai === '') {
            return null;
        }

        $teks = strtoupper(trim((string) $nilai));

        if (in_array($teks, ['1', 'TRUE', 'YA', 'TERLAMBAT'], true)) {
            return 1;
        }

        if (in_array($teks, ['0', 'FALSE', 'TIDAK', 'TEPAT WAKTU'], true)) {
            return 0;
        }

        return null;
    }

    private function lewati(int $baris, string $noPo, string $alasan): void
    {
        $this->dilewati++;

        if (count($this->catatan) < 15) {
            $this->catatan[] = [
                'baris'  => $baris,
                'no_po'  => $noPo,
                'alasan' => $alasan,
            ];
        }
    }
}
