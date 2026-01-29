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
        Schema::create('sparepart_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sparepart_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('condition', ['new', 'used-good', 'damaged', 'repair'])->default('new');
            $table->integer('qty')->default(0);

            $table->timestamps();

            // 🔥 kunci utama inventory
            $table->unique(['sparepart_id', 'site_id', 'condition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sparepart_stocks');
    }
};
