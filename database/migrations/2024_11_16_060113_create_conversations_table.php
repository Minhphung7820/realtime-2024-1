<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Chỉ có tên nếu là nhóm
            $table->enum('type', ['private', 'group']); // Loại: cá nhân hoặc nhóm
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Người tạo nhóm
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
