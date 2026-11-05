<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data induk akademik: program, jenis kelas, lembaga, kelas pengajaran,
     * keanggotaan pengajar/santri pada program, dan riwayat pindah kelas.
     * Constraint mengikuti skema Supabase (hasil `supabase:audit`).
     */
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // Jenis layanan: Reguler, Privat, Lembaga (aturan kelas & tarif bergantung pada namanya).
        Schema::create('kelas_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('lembaga', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_lembaga');
            $table->string('pbm_type', 20);
            $table->text('alamat')->nullable();
            $table->foreignUuid('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignUuid('pengajar_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('lembaga_id')->references('id')->on('lembaga')->nullOnDelete();
        });

        Schema::create('pengajar_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pengajar_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('programs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pengajar_id', 'program_id']);
        });

        Schema::create('pengajar_pengganti', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('no_hp', 32)->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kelas_pengajaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('programs')->restrictOnDelete();
            $table->foreignUuid('kelas_type_id')->constrained('kelas_types')->restrictOnDelete();
            $table->string('nama_kelas');
            $table->foreignUuid('pengajar_id')->nullable()->constrained('users')->nullOnDelete();
            // Satu lembaga hanya punya satu kelas (NULL boleh berulang).
            $table->foreignUuid('lembaga_id')->nullable()->unique()->constrained('lembaga')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['program_id', 'aktif', 'nama_kelas']);
            // Target foreign key komposit: pertemuan/santri harus satu program (& jenis) dengan kelasnya.
            $table->unique(['id', 'program_id']);
            $table->unique(['id', 'program_id', 'kelas_type_id']);
        });

        Schema::create('santri_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('santri_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('programs')->restrictOnDelete();
            $table->foreignUuid('kelas_type_id')->constrained('kelas_types')->restrictOnDelete();
            $table->uuid('kelas_pengajaran_id')->nullable();
            $table->foreignUuid('pengajar_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('lembaga_id')->nullable()->constrained('lembaga')->nullOnDelete();
            $table->string('default_pbm', 20)->nullable();
            $table->timestamps();

            $table->unique(['santri_id', 'program_id']);
            $table->foreign(['kelas_pengajaran_id', 'program_id', 'kelas_type_id'], 'santri_programs_kelas_pengajaran_fk')
                ->references(['id', 'program_id', 'kelas_type_id'])
                ->on('kelas_pengajaran')
                ->restrictOnDelete();
        });

        Schema::create('riwayat_kelas_santri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('santri_program_id')->constrained('santri_programs')->restrictOnDelete();
            $table->foreignUuid('kelas_asal_id')->nullable()->constrained('kelas_pengajaran')->restrictOnDelete();
            $table->foreignUuid('kelas_tujuan_id')->nullable()->constrained('kelas_pengajaran')->restrictOnDelete();
            $table->date('berlaku_mulai');
            $table->foreignUuid('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_santri');
        Schema::dropIfExists('santri_programs');
        Schema::dropIfExists('kelas_pengajaran');
        Schema::dropIfExists('pengajar_pengganti');
        Schema::dropIfExists('pengajar_programs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['lembaga_id']);
        });

        Schema::dropIfExists('lembaga');
        Schema::dropIfExists('kelas_types');
        Schema::dropIfExists('programs');
    }
};
