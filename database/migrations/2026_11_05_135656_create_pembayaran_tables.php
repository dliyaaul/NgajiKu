<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekening tujuan, tarif, tagihan beserta rinciannya, dan konfirmasi pembayaran.
     * Catatan transaksi tidak ikut terhapus bila data santri/lembaga dihapus.
     */
    public function up(): void
    {
        Schema::create('pembayaran_rekening', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('nama_pemilik');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pembayaran_tarif', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_tarif');
            $table->foreignUuid('kelas_type_id')->constrained('kelas_types');
            $table->foreignUuid('program_id')->nullable()->constrained('programs');
            $table->foreignUuid('santri_program_id')->nullable()->constrained('santri_programs');
            $table->foreignUuid('lembaga_id')->nullable()->constrained('lembaga');
            $table->decimal('nominal', 14, 2);
            $table->string('satuan', 30);
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tagihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_tagihan', 50)->unique();
            $table->string('tipe_pembayar', 20)->default('santri');
            $table->foreignUuid('santri_program_id')->nullable()->constrained('santri_programs');
            $table->foreignUuid('lembaga_id')->nullable()->constrained('lembaga');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->date('tanggal_tagihan');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('diskon', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 30)->default('belum_bayar')->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tagihan_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('deskripsi');
            $table->string('jenis', 30)->default('pertemuan');
            $table->uuid('referensi_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->decimal('jumlah', 10, 2)->default(1);
            $table->string('satuan', 30)->default('pertemuan');
            $table->decimal('harga_satuan', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_pembayaran', 50)->unique();
            $table->foreignUuid('tagihan_id')->constrained('tagihan');
            $table->foreignUuid('rekening_tujuan_id')->constrained('pembayaran_rekening');
            $table->date('tanggal_transfer');
            $table->decimal('nominal_transfer', 14, 2);
            $table->string('nama_pengirim')->nullable();
            $table->string('rekening_pengirim', 50)->nullable();
            $table->string('bukti_transfer', 1024);
            $table->text('catatan')->nullable();
            $table->string('status', 30)->default('menunggu_verifikasi')->index();
            $table->foreignUuid('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Jejak perubahan status pembayaran (pengajuan, diterima, ditolak).
        Schema::create('pembayaran_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->string('status_lama', 30)->nullable();
            $table->string('status_baru', 30);
            $table->text('catatan')->nullable();
            $table->foreignUuid('dilakukan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_log');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('tagihan_item');
        Schema::dropIfExists('tagihan');
        Schema::dropIfExists('pembayaran_tarif');
        Schema::dropIfExists('pembayaran_rekening');
    }
};
