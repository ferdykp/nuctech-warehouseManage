<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            // Menambahkan kolom digital_signature yang bertipe nullable setelah receipt_attachment
            $table->string('digital_signature')->nullable()->after('receipt_attachment');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn('digital_signature');
        });
    }
};
