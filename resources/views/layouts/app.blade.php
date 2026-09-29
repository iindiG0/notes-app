<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Notes') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <!-- <a href="{{ url('/') }}" class="brand">📝 {{ config('app.name') }}</a> -->
            <a href="{{ url('/') }}" class="brand">📝 {{ config('app.name') }} · v2 (deployed by Fleet)</a>
            <nav>
                @auth
                    <span class="muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Log in</a>
                    <a href="{{ route('register') }}" class="btn">Register</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
