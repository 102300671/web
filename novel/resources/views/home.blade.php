<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>依依家的猫窝</title>
    @vite('resources/css/home.css')
</head>
<body>
    <div class="top-bar">
        @if (Route::has('login'))
            <div class="links">
                @auth
                    <a href="{{ url('/dashboard') }}">控制面板</a>
                @else
                    <a href="{{ route('login') }}">登录</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">注册</a>
                    @endif
                @endauth
            </div>
        @endif
    </div>

    <div class="wrapper">
        <h1>依依家的猫窝</h1>
        <p>这里是依依家的猫窝，欢迎</p>
        <div class="circle"></div>
        <p>你可以看看这个圈，但它不会变，也不说话。</p>
        <footer>© 2025 依依家的猫窝 v0.0.0-alpha</footer>
    </div>
</body>
</html>