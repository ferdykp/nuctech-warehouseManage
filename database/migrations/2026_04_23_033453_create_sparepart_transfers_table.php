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
        Schema::create('sparepart_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sparepart_id');
            $table->foreignId('from_site_id');
            $table->foreignId('to_site_id');
            $table->integer('qty');
            $table->string('from_condition'); // Tambahkan ini: Kondisi barang saat di ASAL
            $table->string('condition');
            $table->enum('status', ['pending', 'approved', 'rejected', 'received'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sparepart_transfers');
    }
};
