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
        Schema::table('field_values', function (Blueprint $table) {
            $table->decimal('value_number', 20, 4)->nullable()->after('value');
            $table->boolean('value_boolean')->nullable()->after('value_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_values', function (Blueprint $table) {
            $table->dropColumn(['value_number', 'value_boolean']);
        });
    }
};
