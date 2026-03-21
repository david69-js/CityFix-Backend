<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('department')->nullable();
            $table->integer('workload')->default(0);
            $table->timestamps();

            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};