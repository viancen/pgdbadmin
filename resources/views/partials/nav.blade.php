<nav class="fixed top-0 left-0 right-0 z-50 border-b border-term-border bg-term-panel/95 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-5 font-mono text-sm">
        <a href="{{ route('home') }}" class="font-mono font-medium text-term-text hover:text-term-accent">
            <span class="text-term-accent">pg</span>dbadmin
        </a>
        <div class="flex items-center gap-6">
            @if(isset($currentDb) && $currentDb)
            <div class="flex items-center gap-2">
                <span class="text-term-text-dim">db</span>
                <form action="{{ route('switch-db') }}" method="post" class="flex items-center gap-2">
                    @csrf
                    <select name="database" onchange="this.form.submit()" class="input max-w-[160px] border-term-border py-1.5 text-xs">
                        @foreach($databases ?? [] as $db)
                        <option value="{{ $db }}" {{ $db === $currentDb ? 'selected' : '' }}>{{ $db }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <a href="{{ route('query.index') }}" class="text-term-text-dim hover:text-term-accent">sql</a>
            @endif
            <span class="text-term-muted">{{ $user ?? '' }}@{{ $host ?? '' }}</span>
            <form action="{{ route('logout') }}" method="post" class="inline">
                @csrf
                <button type="submit" class="text-term-text-dim hover:text-term-danger">logout</button>
            </form>
        </div>
    </div>
</nav>
