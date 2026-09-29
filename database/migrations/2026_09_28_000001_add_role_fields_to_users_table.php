<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('karyawan')->after('email');
            $table->string('jabatan')->nullable()->after('role');
            $table->string('divisi')->nullable()->after('jabatan');
            $table->date('tanggal_bergabung')->nullable()->after('divisi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'jabatan', 'divisi', 'tanggal_bergabung']);
        });
    }
};
