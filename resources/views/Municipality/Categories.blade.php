<!DOCTYPE html>
<html>
<head>
    <title>Service Categories</title>
</head>
<body>

    <h1>Manage Service Categories</h1>

    <p>Office: {{ $office->name }}</p>

    <a href="{{ route('municipality.dashboard') }}">Back to Dashboard</a>

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

    <h2>Create Category</h2>

    <form method="POST" action="{{ route('municipality.categories.store') }}">
        @csrf

        <div>
            <label>Name</label>
            <input type="text" name="name">
        </div>

        <br>

        <div>
            <label>Description</label>
            <textarea name="description"></textarea>
        </div>

        <br>

        <button type="submit">Create</button>
    </form>

    <hr>

    <h2>Existing Categories</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
        </tr>

        @foreach ($categories as $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>