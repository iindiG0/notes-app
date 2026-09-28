@extends('layouts.app')

@section('title', 'Edit note')

@section('content')
    <div class="card">
        <h1>Edit note</h1>

        <form method="POST" action="{{ route('notes.update', $note) }}">
            @csrf
            @method('PUT')
            @include('notes._form', ['note' => $note])

            <div class="actions">
                <button type="submit" class="btn">Save changes</button>
                <a href="{{ route('notes.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
