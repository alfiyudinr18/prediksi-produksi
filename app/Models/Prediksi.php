<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediksi extends Model
{
    protected $table = 'prediksis';

    protected $fillable = [

        'qty',

        'jenis_barang',

        'jumlah_pekerja',

        'tanggal_order',

        'target_selesai',

        'durasi_target',

        'hasil_prediksi',

        'probabilitas'

    ];

    protected $casts = [

        'tanggal_order'=>'date',

        'target_selesai'=>'date'

    ];
}
