<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Otomatis dari Employee
            $table->string('position')->nullable(); // Otomatis dari Employee
            $table->string('placement')->nullable(); // Otomatis dari Branch name
            $table->string('bank');
            $table->string('account_no');
            $table->decimal('amount', 15, 2);
            $table->enum('information', ['1st probation', '2nd probation', '3rd probation', 'regular salary'])
                ->default('regular salary');
            $table->string('before_after')->nullable();
            $table->text('more_information')->nullable();
            $table->text('get_information')->nullable(); // Note
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
