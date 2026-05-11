<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('status_id');
            $table->string('hidden_reason')->nullable()->after('is_hidden');

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn(['is_hidden', 'hidden_reason']);
        });
    }
};
