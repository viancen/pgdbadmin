@extends('layout')

@section('content')
<div class="relative flex min-h-screen items-center justify-center px-4">
    <div class="absolute inset-0 bg-grid-pattern bg-grid opacity-60"></div>
    <div class="absolute inset-0 bg-landing-gradient"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="card border-term-accent/20 p-8 shadow-term-glow">
            <div class="mb-8">
                <p class="font-mono text-term-prompt text-sm">$ connect</p>
                <h1 class="mt-2 font-mono text-2xl font-semibold text-term-text">
                    <span class="text-term-accent">pg</span>dbadmin
                </h1>
                <p class="mt-1 font-mono text-xs text-term-text-dim">PostgreSQL 18+ — host, port, user, password</p>
                <a href="{{ route('docs.connecting') }}" class="mt-2 inline-block font-mono text-xs text-term-cyan hover:underline">Direct & SSH tunnel →</a>
            </div>
            @if(isset($error) && $error)
            <div class="mb-4 rounded border border-term-danger/50 bg-term-danger/10 px-4 py-3 font-mono text-sm text-term-danger">
                ERROR: {{ $error }}
            </div>
            @endif
            <form action="{{ route('login') }}" method="post" class="space-y-4 font-mono">
                @csrf
                <div>
                    <label for="host" class="mb-1 block text-xs text-term-text-dim">host</label>
                    <input type="text" id="host" name="host" value="{{ $host ?? 'localhost' }}" placeholder="localhost" class="input" required />
                </div>
                <div>
                    <label for="port" class="mb-1 block text-xs text-term-text-dim">port</label>
                    <input type="number" id="port" name="port" value="{{ $port ?? '5432' }}" placeholder="5432" class="input" min="1" max="65535" />
                </div>
                <div>
                    <label for="user" class="mb-1 block text-xs text-term-text-dim">user</label>
                    <input type="text" id="user" name="user" value="{{ $user ?? '' }}" placeholder="postgres" class="input" required />
                </div>
                <div>
                    <label for="password" class="mb-1 block text-xs text-term-text-dim">password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" class="input" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="ssl" name="ssl" value="1" {{ (isset($ssl) && $ssl) ? 'checked' : '' }} class="rounded border-term-border bg-term-bg text-term-accent focus:ring-term-accent" />
                    <label for="ssl" class="font-mono text-xs text-term-text-dim">ssl</label>
                </div>
                <button type="submit" class="btn-primary w-full font-mono">connect</button>
            </form>
        </div>
    </div>
</div>
@endsection
