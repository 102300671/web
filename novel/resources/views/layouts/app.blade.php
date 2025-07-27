<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - 小说站</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow px-6 py-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-xl font-bold">小说之家</a>
            <div>
                <a href="{{ route('login') }}" class="text-sm px-3">登录</a>
                <a href="{{ route('register') }}" class="text-sm px-3">注册</a>
            </div>
        </div>
    </nav>

    <main class="py-6">
        @yield('content')
    </main>

    <footer class="bg-white text-center text-sm text-gray-500 py-4 border-t">
        &copy; {{ date('Y') }} 小说之家. 保留所有权利。
    </footer>
</body>
</html>