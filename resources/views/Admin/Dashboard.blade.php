<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

    <h1>Admin Dashboard</h1>

    <p>Welcome, {{ Auth::user()->name }}</p>
    <p>Role: {{ Auth::user()->role->role }}</p>

    <hr>

    <h3>Admin Actions</h3>

    <ul>
    <li><a href="{{ route('admin.municipalities') }}">Manage municipalities</a></li>
    <li><a href="{{ route('admin.offices') }}">Manage government offices</a></li>
    <li><a href="{{ route('admin.municipality.users') }}">Manage municipality users</a></li>
</ul>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>