<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

    <h1>Login</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <div style="color: red;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('dologin') }}">
        @csrf

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

        <button type="submit">Login</button>
    </form>

    <p>
        Don't have an account?
        <a href="{{ route('signup') }}">Register</a>
    </p>

</body>
</html>