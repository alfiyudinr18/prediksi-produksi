<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksis', function (Blueprint $table) {

            $table->id();

            $table->string('no_po')->unique();

            $table->date('tanggal_order');

            $table->string('jenis_barang');

            $table->string('warna')->nullable();

            $table->integer('qty');

            $table->integer('jumlah_pekerja');

            $table->date('target_selesai');

            $table->integer('durasi_target');

            $table->boolean('terlambat');

            $table->timestamps();

            $table->index('jenis_barang');
            $table->index('terlambat');
            $table->index('tanggal_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksis');
    }
};
