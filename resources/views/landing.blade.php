@extends('layout')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    <div class="absolute inset-0 bg-term-bg bg-grid-pattern bg-grid opacity-80"></div>
    <div class="absolute inset-0 bg-landing-gradient"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_120%_80%_at_50%_120%,rgba(0,255,65,0.04),transparent)]"></div>
    <div class="absolute inset-0 opacity-[0.02]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');"></div>

    <div class="relative z-10 mx-auto max-w-5xl px-6 pt-20 pb-24">
        <header class="mb-24 animate-fade-in">
            <div class="flex items-center justify-between">
                <span class="font-mono text-term-text-dim">pgdbadmin@<span class="text-term-accent">v1.0</span></span>
                <a href="{{ route('login') }}" class="btn-ghost text-term-text-dim hover:text-term-accent">login →</a>
            </div>
        </header>

        <section class="mb-32 animate-fade-in" style="animation-delay: 0.05s">
            <p class="font-mono text-term-prompt text-sm tracking-wide">$ connect postgres://</p>
            <h1 class="mt-4 font-mono text-4xl font-semibold tracking-tight text-term-text sm:text-5xl md:text-6xl">
                PostgreSQL admin,<br />
                <span class="text-term-accent">terminal-style.</span>
            </h1>
            <p class="mt-6 max-w-xl font-mono text-base text-term-text-dim leading-relaxed">
                Browse schemas, run SQL, inspect structure. No GUI bloat — just a clean, technical interface for PostgreSQL 18+.
            </p>
            <div class="mt-10 flex flex-wrap items-center gap-4">
                <a href="{{ route('login') }}" class="btn-primary px-6 py-3 font-mono">
                    Connect to database
                </a>
                <a href="{{ route('docs.connecting') }}" class="btn-ghost font-mono text-term-text-dim hover:text-term-cyan">
                    Direct & SSH tunnel docs →
                </a>
            </div>
        </section>

        <section class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="card animate-fade-in border-term-border/80 p-5" style="animation-delay: 0.1s">
                <p class="font-mono text-xs uppercase tracking-wider text-term-amber">Schemas & tables</p>
                <p class="mt-2 font-mono text-sm text-term-text-dim">List tables, browse rows, paginate. One click to structure view.</p>
            </div>
            <div class="card animate-fade-in border-term-border/80 p-5" style="animation-delay: 0.15s">
                <p class="font-mono text-xs uppercase tracking-wider text-term-amber">SQL console</p>
                <p class="mt-2 font-mono text-sm text-term-text-dim">Run any query. SELECT limited to 500 rows; full result metadata.</p>
            </div>
            <div class="card animate-fade-in border-term-border/80 p-5" style="animation-delay: 0.2s">
                <p class="font-mono text-xs uppercase tracking-wider text-term-amber">Structure</p>
                <p class="mt-2 font-mono text-sm text-term-text-dim">Column names, types, nullability, defaults — no fluff.</p>
            </div>
        </section>

        <section class="mt-24 animate-fade-in" style="animation-delay: 0.25s">
            <div class="card overflow-hidden border-term-accent/20">
                <div class="border-b border-term-border bg-term-panel/80 px-4 py-2 font-mono text-xs text-term-text-dim">
                    <span class="text-term-amber">●</span> postgres@localhost — psql compatible
                </div>
                <div class="space-y-1 p-4 font-mono text-sm">
                    <p><span class="text-term-prompt">$</span> <span class="text-term-text">SELECT schema_name FROM information_schema.schemata;</span></p>
                    <p class="text-term-text-dim"> schema_name</p>
                    <p class="text-term-text-dim"> ---------------</p>
                    <p><span class="text-term-accent"> public</span></p>
                    <p><span class="text-term-accent"> pg_catalog</span></p>
                    <p><span class="text-term-prompt">$</span> <span class="text-term-muted">_</span></p>
                </div>
            </div>
        </section>

        <footer class="mt-32 border-t border-term-border pt-8 font-mono text-xs text-term-text-dim">
            <a href="{{ route('login') }}" class="hover:text-term-accent">Login</a>
            <span class="mx-2">·</span>
            <a href="{{ route('docs.connecting') }}" class="hover:text-term-accent">Connecting (NL)</a>
        </footer>
    </div>
</div>
@endsection
