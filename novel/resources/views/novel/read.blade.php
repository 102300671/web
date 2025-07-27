@extends('layouts.app')

@section('title', $novel->title . ' - ' . $chapter->title)

@section('content')
<div class="container mx-auto py-8">
    <div class="bg-white p-6 shadow rounded">
        <h1 class="text-2xl font-bold mb-2">{{ $novel->title }}</h1>
        <h2 class="text-xl text-gray-700 mb-4">第 {{ $chapter->chapter_number }} 章：{{ $chapter->title }}</h2>

        <div class="prose prose-lg max-w-full leading-relaxed text-gray-800">
            {!! nl2br(e($chapter->content)) !!}
        </div>

        <div class="mt-8 flex justify-between items-center">
            @if ($prev)
                <a href="{{ route('chapter.read', [$novel->id, $prev->id]) }}"
                   class="text-blue-600 hover:underline">← 上一章</a>
            @else
                <span class="text-gray-400">← 已是第一章</span>
            @endif

            <a href="{{ route('novel.show', $novel->id) }}" class="text-sm text-gray-500 underline">
                返回目录
            </a>

            @if ($next)
                <a href="{{ route('chapter.read', [$novel->id, $next->id]) }}"
                   class="text-blue-600 hover:underline">下一章 →</a>
            @else
                <span class="text-gray-400">已是最后一章 →</span>
            @endif
        </div>
    </div>
</div>
@endsection