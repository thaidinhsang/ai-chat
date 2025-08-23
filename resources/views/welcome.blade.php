@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="text-center mt-5">
            <h1 class="display-4 mb-3">Welcome to AI Chat Admin Panel</h1>
            <p class="lead mb-4">
                Manage your AI chat pages, tokens, and pricing easily.<br>
                Use the navigation above to get started.
            </p>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-primary btn-lg">Go to Pages Manager</a>
        </div>
    </div>
</div>
@endsection