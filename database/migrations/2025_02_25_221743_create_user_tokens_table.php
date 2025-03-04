<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /* 
    Создание таблицы user_tokens 
    */
    public function up(): void
    {
        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->string('refresh_token')->unique()->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
        });
    }

    /* 
    Удаление таблицы user_tokens 
    */
    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
    }
};