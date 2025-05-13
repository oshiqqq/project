<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /* 
    Создание таблицы roles 
    */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Наименование роли');
            $table->string('description')->nullable()->comment('Описание роли');
            $table->string('slug')->unique()->comment('Шифр роли'); 

            $table->timestamp('created_at')->useCurrent()->comment('Время создания записи');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Идентификатор пользователя, создавшего запись');
            $table->softDeletes('deleted_at')->comment('Время мягкого удаления записи');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null')->comment('Идентификатор пользователя, удалившего запись');
        });
    }

    /* 
    Удаление таблицы roles 
    */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};