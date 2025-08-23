@extends('layouts.app')

@section('title', 'Manage Pages')

@section('content')
    <h2 class="mb-4">Pages Manager</h2>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-success mb-3">Add New Page</a>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Page ID</th>
                <th>OpenAI Token</th>
                <th>AI Context</th>
                <th>Price/Unit</th>
                <th>Price/Combo</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $page)
            <tr>
                <td>{{ $page->id }}</td>
                <td>{{ $page->page_id }}</td>
                <td>{{ substr($page->openai_token, 0, 3) }}...{{ substr($page->openai_token, -3) }}</td>
                <td>{{ \Illuminate\Support\Str::limit(strip_tags($page->ai_context), 80) }}</td>
                <td>{{ $page->price_per_unit }}</td>
                <td>{{ $page->price_per_combo }}</td>
                <td>
                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('admin.pages.destroy', $page->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this page?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection