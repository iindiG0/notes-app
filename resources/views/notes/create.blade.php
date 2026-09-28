@extends('layouts.app')

@section('title', 'New note')

@section('content')
    <div class="card">
        <h1>New note</h1>

        <form method="POST" action="{{ route('notes.store') }}">
            @csrf
            @include('notes._form')

            <div class="actions">
                <button type="submit" class="btn">Save</button>
                <a href="{{ route('notes.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
