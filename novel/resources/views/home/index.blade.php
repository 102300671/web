@extends('layouts.app')

@section('title', '小说首页')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">📕 最新小说</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($latestNovels as $novel)
            <div class="bg-white p-4 shadow rounded">
                <h2 class="font-semibold text-lg">
                    <a href="{{ route('novel.show', $novel->id) }}">{{ $novel->title }}</a>
                </h2>
                <p class="text-sm text-gray-600">作者：{{ $novel->author->name ?? '未知' }}</p>
                <p class="text-gray-700 mt-2">{{ Str::limit($novel->description, 80) }}</p>
            </div>
        @endforeach
    </div>

    <h1 class="text-2xl font-bold mt-10 mb-4">🔥 热门小说</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($popularNovels as $novel)
            <div class="bg-white p-4 shadow rounded">
                <h2 class="font-semibold text-lg">
                    <a href="{{ route('novel.show', $novel->id) }}">{{ $novel->title }}</a>
                </h2>
                <p class="text-sm text-gray-600">作者：{{ $novel->author->name ?? '未知' }}</p>
                <p class="text-gray-700 mt-2">{{ Str::limit($novel->description, 80) }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection