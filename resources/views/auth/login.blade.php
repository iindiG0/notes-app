@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card narrow">
        <h1>Log in</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <p class="error">{{ $message }}</p> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <label class="checkbox">
                <input type="checkbox" name="remember"> Remember me
            </label>

            <button type="submit" class="btn btn-block">Log in</button>
        </form>

        <p class="muted center">No account yet? <a href="{{ route('register') }}">Register</a></p>
    </div>
@endsection
