<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notes pages for the website. Every query goes through
 * $request->user()->notes() so users can only ever see their own notes.
 */
class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = $request->user()->notes()->latest()->paginate(10);

        return view('notes.index', ['notes' => $notes]);
    }

    public function create(): View
    {
        return view('notes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->notes()->create($this->validated($request));

        return redirect()->route('notes.index')->with('status', 'Note created.');
    }

    public function edit(Request $request, int $note): View
    {
        // findOrFail on the user's own notes: someone else's note gives 404
        $note = $request->user()->notes()->findOrFail($note);

        return view('notes.edit', ['note' => $note]);
    }

    public function update(Request $request, int $note): RedirectResponse
    {
        $note = $request->user()->notes()->findOrFail($note);
        $note->update($this->validated($request));

        return redirect()->route('notes.index')->with('status', 'Note updated.');
    }

    public function destroy(Request $request, int $note): RedirectResponse
    {
        $request->user()->notes()->findOrFail($note)->delete();

        return redirect()->route('notes.index')->with('status', 'Note deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
