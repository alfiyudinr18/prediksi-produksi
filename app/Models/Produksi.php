<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    protected $table = 'produksis';

    protected $fillable = [
        'no_po',
        'tanggal_order',
        'jenis_barang',
        'warna',
        'qty',
        'jumlah_pekerja',
        'target_selesai',
        'durasi_target',
        'terlambat',
    ];

    protected $casts = [
        'tanggal_order'  => 'date',
        'target_selesai' => 'date',
        'qty'            => 'integer',
        'jumlah_pekerja' => 'integer',
        'durasi_target'  => 'integer',
        'terlambat'      => 'integer',
    ];
}
