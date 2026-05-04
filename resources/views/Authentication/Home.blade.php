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

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>