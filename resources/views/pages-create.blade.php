@extends('layouts.app')

@section('title', 'Edit Page')

@section('content')
    <h2 class="mb-4">Create New Page</h2>
    <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        <div class="mb-3">
            <label for="page_id" class="form-label">Page ID</label>
            <input type="text" class="form-control" id="page_id" name="page_id" required maxlength="100">
        </div>
        <div class="mb-3">
            <label for="openai_token" class="form-label">OpenAI Token</label>
            <input type="text" class="form-control" id="openai_token" name="openai_token" required maxlength="255">
        </div>
        <div class="mb-3">
            <label for="ai_context" class="form-label">AI Context</label>
            <textarea class="form-control" id="ai_context" name="ai_context" rows="10"></textarea>
        </div>
        <div class="mb-3">
            <label for="price_per_unit" class="form-label">Price Per Unit</label>
            <input type="number" step="0.01" class="form-control" id="price_per_unit" name="price_per_unit" required>
        </div>
        <div class="mb-3">
            <label for="price_per_combo" class="form-label">Price Per Combo</label>
            <input type="number" step="0.01" class="form-control" id="price_per_combo" name="price_per_combo" required>
        </div>
        <button type="submit" class="btn btn-success">Create Page</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary ms-2">Back</a>
    </form>
@endsection