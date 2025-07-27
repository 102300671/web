<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Http\Request;

class NovelController extends Controller
{
  public function show($id) {
    $novel = Novel::with('author', 'chapters')->findOrFail($id);

    return view('novel.show', compact('novel'));
  }
  public function read($novel_id, $chapter_id) {
    $novel = Novel::findOrFail($novel_id);
    $chapter = $novel->chapters()->where('id', $chapter_id)->firstOrFail();

    // 查找上一章、下一章（按 chapter_number）
    $prev = $novel->chapters()
    ->where('chapter_number', '<', $chapter->chapter_number)
    ->orderByDesc('chapter_number')
    ->first();

    $next = $novel->chapters()
    ->where('chapter_number', '>', $chapter->chapter_number)
    ->orderBy('chapter_number')
    ->first();

    // 增加浏览量
    $novel->increment('views');

    return view('novel.read', compact('novel', 'chapter', 'prev', 'next'));
  }
}