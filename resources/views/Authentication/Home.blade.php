<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>

    <h1>Home Page</h1>

    <p>Welcome, {{ Auth::user()->name }}</p>
    <p>Email: {{ Auth::user()->email }}</p>
    <p>Role: {{ Auth::user()->role->role }}</p>
    @if (Auth::user()->avatar)
    <img src="{{ Auth::user()->avatar }}" width="80" height="80" style="border-radius: 50%;">
@endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>