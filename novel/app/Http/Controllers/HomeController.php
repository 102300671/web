<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Novel;

class HomeController extends Controller
{
    public function index()
    {
        // 最新小说
        $latestNovels = Novel::orderBy('created_at', 'desc')->take(8)->get();

        // 热门小说（基于阅读量）
        $popularNovels = Novel::orderBy('views', 'desc')->take(8)->get();

        return view('home.index', compact('latestNovels', 'popularNovels'));
    }
}