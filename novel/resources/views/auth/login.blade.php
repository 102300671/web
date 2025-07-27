@vite('resources/css/auth/login.css')
<div class="auth-container">
    <!-- Session Status -->
    @if(session('status'))
        <div class="session-status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label">邮箱</label></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" 
                   class="form-input" required autofocus autocomplete="username">
            @error('email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">密码</label>
            <input id="password" type="password" name="password" 
                   class="form-input" required autocomplete="current-password">
            @error('password')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="remember-me">
            <input id="remember_me" type="checkbox" name="remember" class="remember-checkbox">
            <label for="remember_me" class="remember-label">记住我</label>
        </div>

        <div class="form-footer">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-password">
                    忘记你的密码？
                </a>
            @endif

            <button type="submit" class="submit-button">
                登录
            </button>
        </div>
    </form>
</div>