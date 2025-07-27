
@vite(['resources/css/auth/register.css'])
<form method="POST" action="{{ route('register') }}" class="register-form">
    @csrf

    <!-- Name -->
    <div class="form-group">
        <label for="name">姓名</label></label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        @error('name')
            <p class="error-message">{{ $message }}</p>
        @enderror
    </div>

    <!-- Email -->
    <div class="form-group">
        <label for="email">邮箱</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        @error('email')
            <p class="error-message">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <label for="password">密码</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password')
            <p class="error-message">{{ $message }}</p>
        @enderror
    </div>

    <!-- Confirm Password -->
    <div class="form-group">
        <label for="password_confirmation">确认密码</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
    </div>

    <div class="form-footer">
        <a href="{{ route('login') }}" class="login-link">
            已经注册?
        </a>
        <button type="submit" class="submit-button">
            注册
        </button>
    </div>
</form>