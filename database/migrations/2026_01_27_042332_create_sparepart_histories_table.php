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
        Schema::create('sparepart_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('sparepart_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('from_site_id')
                ->nullable()
                ->constrained('sites')
                ->nullOnDelete();

            $table->foreignId('to_site_id')
                ->nullable()
                ->constrained('sites')
                ->nullOnDelete();

            $table->enum('action', [
                'CREATE',
                'MOVE',
                'RETURN',
                'CONDITION_CHANGE'
            ]);

            $table->enum('condition', ['new', 'used-good', 'damaged', 'repair'])->nullable();
            $table->integer('qty')->default(0);
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sparepart_histories');
    }
};
