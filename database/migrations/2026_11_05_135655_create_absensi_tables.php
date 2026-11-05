<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pertemuan & absensi per program. Nama tabel dan kolom sama dengan Supabase
     * (termasuk perbedaan `pengganti_pengajar_id` vs `pengajar_pengganti_id`)
     * agar data dapat dipindahkan apa adanya.
     *
     * Status "Alpha" disimpan apa adanya di `absensi` (enum Supabase), tetapi
     * sebagai "Alpa" di `absensi_tahfidz` & `absensi_tarjamah` (check constraint Supabase).
     */
    public function up(): void
    {
        foreach (['bbaq_pertemuan' => 'pengganti_pengajar_id', 'tahfidz_pertemuan' => 'pengganti_pengajar_id', 'tarjamah_pertemuan' => 'pengajar_pengganti_id'] as $tableName => $penggantiColumn) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName, $penggantiColumn) {
                $table->uuid('id')->primary();
                $table->foreignUuid('program_id')->constrained('programs')->restrictOnDelete();
                $table->uuid('kelas_pengajaran_id')->nullable();
                $table->date('tanggal');
                $table->foreignUuid('pengajar_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid($penggantiColumn)->nullable()->constrained('pengajar_pengganti')->nullOnDelete();
                $table->text('materi_pembelajaran')->nullable();
                $table->string('metode', 20)->default('Offline');
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['kelas_pengajaran_id', 'tanggal']);
                $table->index(['program_id', 'tanggal']);
                // Pertemuan harus berada pada program yang sama dengan kelasnya.
                $table->foreign(['kelas_pengajaran_id', 'program_id'], "{$tableName}_kelas_pengajaran_fk")
                    ->references(['id', 'program_id'])
                    ->on('kelas_pengajaran')
                    ->restrictOnDelete();
            });
        }

        // BBAQ (dengan pertemuan; user_id = penginput) dan absensi kegiatan PTL
        // (tanpa pertemuan; user_id = santri).
        Schema::create('absensi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bbaq_pertemuan_id')->nullable()->constrained('bbaq_pertemuan')->cascadeOnDelete();
            $table->foreignUuid('santri_program_id')->nullable()->constrained('santri_programs')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('program', 10);
            $table->string('kegiatan', 100);
            $table->string('pbm_aktual', 20)->nullable();
            $table->string('status', 20)->default('Hadir');
            $table->text('catatan')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // NULL boleh berulang, jadi hanya berlaku untuk absensi BBAQ berpertemuan.
            $table->unique(['bbaq_pertemuan_id', 'santri_program_id']);
            $table->index(['user_id', 'tanggal']);
        });

        Schema::create('absensi_tahfidz', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tahfidz_pertemuan_id')->nullable()->constrained('tahfidz_pertemuan')->cascadeOnDelete();
            $table->foreignUuid('santri_program_id')->constrained('santri_programs')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 20);
            $table->string('jenis_tahfidz', 20)->nullable();
            $table->string('surat', 50)->nullable();
            $table->unsignedSmallInteger('ayat_mulai')->nullable();
            $table->unsignedSmallInteger('ayat_selesai')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tahfidz_pertemuan_id', 'santri_program_id']);
            // Satu santri hanya satu setoran Tahfidz per tanggal.
            $table->unique(['santri_program_id', 'tanggal']);
        });

        Schema::create('absensi_tarjamah', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tarjamah_pertemuan_id')->constrained('tarjamah_pertemuan')->cascadeOnDelete();
            $table->foreignUuid('santri_program_id')->constrained('santri_programs')->cascadeOnDelete();
            $table->string('status', 20);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tarjamah_pertemuan_id', 'santri_program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_tarjamah');
        Schema::dropIfExists('absensi_tahfidz');
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('tarjamah_pertemuan');
        Schema::dropIfExists('tahfidz_pertemuan');
        Schema::dropIfExists('bbaq_pertemuan');
    }
};
