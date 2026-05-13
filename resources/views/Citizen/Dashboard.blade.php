<!DOCTYPE html>
<html>
<head>
    <title>Citizen Dashboard</title>
</head>
<body>

    <h1>Citizen Dashboard</h1>

    <p>Welcome, {{ Auth::user()->name }}</p>
    <p>Role: {{ Auth::user()->role->role }}</p>

    <hr>

    <h3>Citizen Actions</h3>

    <ul>
        <li>Browse government offices</li>
        <li>Browse services</li>
        <li>Submit service request</li>
        <li>Track request status</li>
    </ul>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>