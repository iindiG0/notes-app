@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="card narrow">
        <h1>Create an account</h1>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <p class="error">{{ $message }}</p> @enderror

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <p class="error">{{ $message }}</p> @enderror

            <label for="password">Password <span class="muted">(at least 8 characters)</span></label>
            <input id="password" type="password" name="password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <button type="submit" class="btn btn-block">Register</button>
        </form>

        <p class="muted center">Already registered? <a href="{{ route('login') }}">Log in</a></p>
    </div>
@endsection
