<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prediksis', function (Blueprint $table) {
            $table->id();
            $table->integer('qty');

            $table->string('jenis_barang');

            $table->integer('jumlah_pekerja');

            $table->date('tanggal_order');

            $table->date('target_selesai');

            $table->integer('durasi_target');

            $table->string('hasil_prediksi');

            $table->double('probabilitas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediksis');
    }
};
