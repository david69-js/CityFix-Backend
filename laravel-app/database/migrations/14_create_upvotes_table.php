<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upvotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('issue_id')->constrained('issues')->onDelete('cascade');
            
            $table->unique(['user_id', 'issue_id']);
            $table->index('issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upvotes');
    }
};