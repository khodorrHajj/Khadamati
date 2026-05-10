<!DOCTYPE html>
<html>
<head>
    <title>Municipality Dashboard</title>
</head>
<body>

    <h1>Municipality Dashboard</h1>

    <p>Welcome, {{ Auth::user()->name }}</p>
    <p>Role: {{ Auth::user()->role->role }}</p>

    <hr>

    <h3>Municipality Actions</h3>

    <ul>
    <li><a href="{{ route('municipality.categories') }}">Manage service categories</a></li>
    <li><a href="{{ route('municipality.services') }}">Manage services</a></li>
</ul>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>