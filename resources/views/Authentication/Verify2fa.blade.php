<!DOCTYPE html>
<html>
<head>
    <title>Verify 2FA</title>
</head>
<body>

    <h1>Two-Factor Verification</h1>

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

    <form method="POST" action="{{ route('twofactor.verify') }}">
        @csrf

        <div>
            <label>Verification Code</label>
            <input type="text" name="code" maxlength="6">
        </div>

        <br>

        <button type="submit">Verify</button>
    </form>

    <br>

    <form method="POST" action="{{ route('twofactor.resend') }}">
        @csrf
        <button type="submit">Resend Code</button>
    </form>

</body>
</html>