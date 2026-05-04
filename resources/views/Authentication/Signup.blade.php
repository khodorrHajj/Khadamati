<div>
    <!-- The whole future lies in uncertainty: live immediately. - Seneca -->
</div>
<!DOCTYPE html>
<html>
<head>
    <title>Signup</title>
</head>
<body>

    <h1>Create Account</h1>

    @if ($errors->any())
        <div style="color: red;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name') }}">
        </div>

        <br>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}">
        </div>

        <br>

        <div>
            <label>Password</label>
            <input type="password" name="password">
        </div>

        <br>

        <div>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation">
        </div>

        <br>

        <button type="submit">Register</button>
    </form>

    <p>
        Already have an account?
        <a href="{{ route('login') }}">Login</a>
    </p>

</body>
</html>