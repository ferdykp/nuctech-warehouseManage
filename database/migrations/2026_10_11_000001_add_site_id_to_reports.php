<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
        });
        DB::table('reports')->orderBy('id')->chunkById(200, function ($reports) {
            foreach ($reports as $report) {
                $site = DB::table('sites')->where('slug', $report->site_machine)->first();
                if (! $site) {
                    $matches = DB::table('sites')->where('machine_name', $report->site_machine)->get();
                    $site = $matches->count() === 1 ? $matches->first() : null;
                }
                // Keep ambiguous legacy records unassigned for superadmin review.
                if ($site) {
                    DB::table('reports')->where('id', $report->id)->update(['site_id' => $site->id]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', fn(Blueprint $table) => $table->dropConstrainedForeignId('site_id'));
    }
};
