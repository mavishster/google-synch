<!DOCTYPE html>
<html>
<head>
    <title>Record Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
    <h1>Records</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('records.store') }}" class="row g-3 mb-4">
        @csrf
        <div class="col-md-3">
            <input type="text" name="title" class="form-control" placeholder="Title" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="description" class="form-control" placeholder="Description">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select" required>
                <option value="Allowed">Allowed</option>
                <option value="Prohibited">Prohibited</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Add</button>
        </div>
    </form>

    <form method="POST" action="{{ route('records.generate') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-success mb-3">Generate 1000</button>
    </form>

    <form method="POST" action="{{ route('records.truncate') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-danger mb-3">Truncate Table</button>
    </form>

    <form method="POST" action="{{ route('records.setUrl') }}" class="mb-3">
        @csrf
        <input type="url" name="sheet_url" class="form-control" placeholder="Enter Google Sheet URL" value="{{ $sheetUrl ?? '' }}">
        <button type="submit" class="btn btn-info mt-2">Save Sheet URL</button>
    </form>

    <table class="table table-bordered">
        <thead class="table-dark">
        <tr>
            <th>ID</th><th>Title</th><th>Description</th><th>Status</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($records as $record)
            <tr>
                <td>{{ $record->id }}</td>
                <td>{{ $record->title }}</td>
                <td>{{ $record->description }}</td>
                <td>{{ $record->status }}</td>
                <td>
                    <form method="POST" action="{{ route('records.destroy', $record->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Del</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

</body>
</html>
