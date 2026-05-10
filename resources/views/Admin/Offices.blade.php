<!DOCTYPE html>
<html>
<head>
    <title>Government Offices</title>
</head>
<body>

    <h1>Manage Government Offices</h1>

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

    <h2>Create Government Office</h2>

    <form method="POST" action="{{ route('admin.offices.store') }}">
        @csrf

        <div>
            <label>Municipality</label>
            <select name="municipality_id">
                @foreach ($municipalities as $municipality)
                    <option value="{{ $municipality->id }}">{{ $municipality->name }}</option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Office Name</label>
            <input type="text" name="name">
        </div>

        <br>

        <div>
            <label>Address</label>
            <input type="text" name="address">
        </div>

        <br>

        <div>
            <label>Google Maps Location</label>
            <input type="text" name="google_maps_location">
        </div>

        <br>

        <div>
            <label>Working Hours</label>
            <input type="text" name="working_hours">
        </div>

        <br>

        <div>
            <label>Contact Info</label>
            <input type="text" name="contact_info">
        </div>

        <br>

        <button type="submit">Create</button>
    </form>

    <hr>

    <h2>Existing Offices</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Office</th>
            <th>Municipality</th>
            <th>Address</th>
            <th>Working Hours</th>
            <th>Contact Info</th>
        </tr>

        @foreach ($offices as $office)
            <tr>
                <td>{{ $office->id }}</td>
                <td>{{ $office->name }}</td>
                <td>{{ $office->municipality->name }}</td>
                <td>{{ $office->address }}</td>
                <td>{{ $office->working_hours }}</td>
                <td>{{ $office->contact_info }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>