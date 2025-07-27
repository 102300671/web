<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint
;
use Illuminate\Support\Facades\Schema;

class CreateNovelsTable extends Migration
{
    public function up(): void
    {
        Schema::create('novels', function (Blueprint $table) {
            $table->id();
            $table->string('title');                  // 小说标题
            $table->unsignedBigInteger('user_id');    // 作者ID
            $table->string('cover')->nullable();      // 封面图URL
            $table->text('description')->nullable();  // 简介
            $table->integer('views')->default(0);     // 阅读量
            $table->timestamps();

            // 外键约束（可选）
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('novels');
    }
}