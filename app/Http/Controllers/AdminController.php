<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // List all pages
    public function index()
    {
        $pages = Page::orderBy('created_at','DESC')->get();
        return view('pages')->with([
            'pages' => $pages
        ]);
        return response()->json($pages);
    }
    public function create()
    {
        return view('pages-create');
    }
    // Show a single page
    public function show($id)
    {
        $page = Page::findOrFail($id);
        return view('pages-show', compact('page'));
    }

    // Create a new page
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|string|max:100|unique:pages,page_id',
            'openai_token' => 'required|string|max:255',
            'ai_context' => 'required|string',
            'price_per_unit' => 'required|numeric',
            'price_per_combo' => 'required|numeric',
        ]);

        $page = Page::create($validated);
        return redirect()->route('admin.pages.index')->with('success', 'Page created successfully!');
    }
    public function edit($id)
    {
        $page = Page::findOrFail($id);
        return view('pages-edit', compact('page'));
    }
    // Update an existing page
    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'page_id' => 'sometimes|string|max:100',
            'openai_token' => 'sometimes|string|max:255',
            'ai_context' => 'sometimes|string',
            'price_per_unit' => 'sometimes|numeric',
            'price_per_combo' => 'sometimes|numeric',
        ]);

        $page->update($validated);
        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully!');
    }

    // Delete a page
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Page deleted successfully!');
    }
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.pages.index'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
