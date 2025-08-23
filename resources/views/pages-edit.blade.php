@extends('layouts.app')

@section('title', 'Edit Page')

@section('content')

    <h2 class="mb-4">Edit Page</h2>
    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="page_id" class="form-label">Page ID</label>
            <input type="text" class="form-control" id="page_id" name="page_id" value="{{ old('page_id', $page->page_id) }}" maxlength="100" required>
        </div>
        <div class="mb-3">
            <label for="openai_token" class="form-label">OpenAI Token</label>
            <input type="text" class="form-control" id="openai_token" name="openai_token" value="{{ old('openai_token', $page->openai_token) }}" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label for="ai_context" class="form-label">AI Context</label>
            <textarea class="form-control" id="ai_context" name="ai_context" rows="10">{{ old('ai_context', $page->ai_context) }}</textarea>
        </div>
        <div class="mb-3">
            <label for="price_per_unit" class="form-label">Price Per Unit</label>
            <input type="number" step="0.01" class="form-control" id="price_per_unit" name="price_per_unit" value="{{ old('price_per_unit', $page->price_per_unit) }}" required>
        </div>
        <div class="mb-3">
            <label for="price_per_combo" class="form-label">Price Per Combo</label>
            <input type="number" step="0.01" class="form-control" id="price_per_combo" name="price_per_combo" value="{{ old('price_per_combo', $page->price_per_combo) }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Page</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary ms-2">Back</a>
    </form>
@endsection