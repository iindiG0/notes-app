@extends('layouts.app')

@section('title', 'My notes')

@section('content')
    <div class="page-head">
        <h1>My notes</h1>
        <a href="{{ route('notes.create') }}" class="btn">+ New note</a>
    </div>

    @forelse ($notes as $note)
        <article class="card note">
            <div class="note-head">
                <h2>{{ $note->title }}</h2>
                <span class="muted small">{{ $note->updated_at->diffForHumans() }}</span>
            </div>

            @if ($note->body)
                <p class="note-body">{{ $note->body }}</p>
            @endif

            <div class="actions">
                <a href="{{ route('notes.edit', $note) }}" class="btn btn-ghost">Edit</a>
                <form method="POST" action="{{ route('notes.destroy', $note) }}" class="inline"
                      onsubmit="return confirm('Delete this note?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    @empty
        <div class="card empty">
            <p>No notes yet.</p>
            <a href="{{ route('notes.create') }}" class="btn">Write your first note</a>
        </div>
    @endforelse

    <div class="pager">
        @if ($notes->previousPageUrl())
            <a href="{{ $notes->previousPageUrl() }}" class="btn btn-ghost">← Newer</a>
        @endif
        @if ($notes->nextPageUrl())
            <a href="{{ $notes->nextPageUrl() }}" class="btn btn-ghost">Older →</a>
        @endif
    </div>
@endsection
