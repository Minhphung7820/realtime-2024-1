<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupMembersTable extends Migration
{
    public function up()
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade'); // Nhóm
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Thành viên
            $table->enum('role', ['admin', 'member'])->default('member'); // Vai trò trong nhóm
            $table->timestamp('joined_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_members');
    }
}
