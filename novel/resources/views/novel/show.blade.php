@extends('layouts.app')

@section('title', $novel->title)

@section('content')
<div class="container mx-auto py-8">
    <div class="bg-white shadow p-6 rounded">
        <div class="flex flex-col md:flex-row gap-4">
            @if ($novel->cover)
                <img src="{{ $novel->cover }}" alt="封面" class="w-48 h-64 object-cover rounded">
            @endif
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $novel->title }}</h1>
                <p class="text-gray-600 mb-2">作者：{{ $novel->author->name ?? '未知' }}</p>
                <p class="text-gray-700">{{ $novel->description }}</p>
                <p class="text-sm text-gray-400 mt-4">总浏览量：{{ $novel->views }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow p-6 rounded mt-6">
        <h2 class="text-xl font-semibold mb-4">📖 章节目录</h2>
        @if ($novel->chapters->count())
            <ul class="list-disc list-inside">
                @foreach ($novel->chapters->sortBy('chapter_number') as $chapter)
                    <li>
                        <a href="{{ route('chapter.read', [$novel->id, $chapter->id]) }}" class="text-blue-600 hover:underline">
                            第 {{ $chapter->chapter_number }} 章：{{ $chapter->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-gray-500">暂无章节</p>
        @endif
    </div>
</div>
@endsection