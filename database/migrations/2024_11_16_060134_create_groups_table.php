<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên nhóm
            $table->text('description')->nullable(); // Mô tả nhóm
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Người tạo nhóm
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
