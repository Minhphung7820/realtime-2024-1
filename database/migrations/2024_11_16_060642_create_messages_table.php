<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade'); // Cuộc trò chuyện
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade'); // Người gửi
            $table->text('content'); // Nội dung tin nhắn
            $table->enum('type', ['text', 'image', 'file'])->default('text'); // Loại tin nhắn
            $table->timestamps(); // Thời gian gửi
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
