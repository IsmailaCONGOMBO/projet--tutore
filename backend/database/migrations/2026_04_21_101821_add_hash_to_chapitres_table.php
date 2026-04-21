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
        Schema::table('chapitres', function (Blueprint $table) {
            $table->string('hash', 64)->nullable()->index()->after('rapport_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapitres', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
};
