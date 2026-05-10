<!DOCTYPE html>
<html>
<head>
    <title>Municipality Users</title>
</head>
<body>

    <h1>Manage Municipality Users</h1>

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

    <h2>Create Municipality User</h2>

    <form method="POST" action="{{ route('admin.municipality.users.store') }}">
        @csrf

        <div>
            <label>Government Office</label>
            <select name="government_office_id">
                @foreach ($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Name</label>
            <input type="text" name="name">
        </div>

        <br>

        <div>
            <label>Email</label>
            <input type="email" name="email">
        </div>

        <br>

        <div>
            <label>Password</label>
            <input type="password" name="password">
        </div>

        <br>

        <button type="submit">Create User</button>
    </form>

    <hr>

    <h2>Existing Municipality Users</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Office</th>
        </tr>

        @foreach ($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @if ($user->governmentOffice)
                        {{ $user->governmentOffice->name }}
                    @else
                        No Office
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

</body>
</html>