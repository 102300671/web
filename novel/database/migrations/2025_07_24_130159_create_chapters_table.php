<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChaptersTable extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('novel_id');   // 所属小说
            $table->integer('chapter_number');        // 章节编号
            $table->string('title');                  // 章节标题
            $table->longText('content');              // 章节内容
            $table->timestamps();

            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
}