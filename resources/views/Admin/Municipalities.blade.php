<!DOCTYPE html>
<html>
<head>
    <title>Municipalities</title>
</head>
<body>

    <h1>Manage Municipalities</h1>

    <a href="{{ route('admin.dashboard') }}">Back to Dashboard</a>

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

    <h2>Create Municipality</h2>

    <form method="POST" action="{{ route('admin.municipalities.store') }}">
        @csrf

        <div>
            <label>Name</label>
            <input type="text" name="name">
        </div>

        <br>

        <div>
            <label>Address</label>
            <input type="text" name="address">
        </div>

        <br>

        <div>
            <label>Phone</label>
            <input type="text" name="phone">
        </div>

        <br>

        <div>
            <label>Email</label>
            <input type="email" name="email">
        </div>

        <br>

        <button type="submit">Create</button>
    </form>

    <hr>

    <h2>Existing Municipalities</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Email</th>
        </tr>

        @foreach ($municipalities as $municipality)
            <tr>
                <td>{{ $municipality->id }}</td>
                <td>{{ $municipality->name }}</td>
                <td>{{ $municipality->address }}</td>
                <td>{{ $municipality->phone }}</td>
                <td>{{ $municipality->email }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>