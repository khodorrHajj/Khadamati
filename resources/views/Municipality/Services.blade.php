<!DOCTYPE html>
<html>
<head>
    <title>Services</title>
</head>
<body>

    <h1>Manage Services</h1>

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

    <h2>Create Service</h2>

    <form method="POST" action="{{ route('municipality.services.store') }}">
        @csrf

        <div>
            <label>Category</label>
            <select name="service_category_id">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Service Name</label>
            <input type="text" name="name">
        </div>

        <br>

        <div>
            <label>Description</label>
            <textarea name="description"></textarea>
        </div>

        <br>

        <div>
            <label>Price</label>
            <input type="number" step="0.01" name="price">
        </div>

        <br>

        <div>
            <label>Duration in Days</label>
            <input type="number" name="duration_days">
        </div>

        <br>

        <div>
            <label>Required Documents</label>
            <textarea name="required_documents"></textarea>
        </div>

        <br>

        <button type="submit">Create Service</button>
    </form>

    <hr>

    <h2>Existing Services</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Required Documents</th>
        </tr>

        @foreach ($services as $service)
            <tr>
                <td>{{ $service->id }}</td>
                <td>{{ $service->name }}</td>
                <td>{{ $service->serviceCategory->name }}</td>
                <td>{{ $service->price }}</td>
                <td>{{ $service->duration_days }} days</td>
                <td>{{ $service->required_documents }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>