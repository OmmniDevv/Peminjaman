<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('diajukan', 'dipinjam', 'dikembalikan', 'telat', 'pengajuan_kembali') NOT NULL DEFAULT 'diajukan'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('diajukan', 'dipinjam', 'dikembalikan', 'telat') NOT NULL DEFAULT 'diajukan'");
        }
    }
};
