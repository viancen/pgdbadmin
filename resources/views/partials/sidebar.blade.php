{{-- DataGrip-style left sidebar: Database Explorer --}}
<aside class="app-sidebar flex h-screen w-[260px] shrink-0 flex-col border-r border-term-border bg-term-panel">
    <div class="border-b border-term-border px-4 py-4">
        <a href="{{ route('home') }}" class="font-mono text-sm font-medium text-term-text hover:text-term-accent inline-flex items-center gap-2">
            <img src="{{ asset('pgdb.png') }}" alt="pgdbadmin" class="h-6 w-6 shrink-0 object-contain" />
            <span class="text-term-accent">pg</span>dbadmin
        </a>
    </div>
    <div class="flex-1 overflow-y-auto">
        <div class="border-b border-term-border px-4 py-3">
            <p class="font-mono text-[11px] uppercase tracking-wider text-term-text-dim inline-flex items-center gap-1.5"><i data-lucide="layout-grid" class="w-3.5 h-3.5"></i> Database Explorer</p>
            <p class="mt-1 truncate font-mono text-xs text-term-muted" title="{{ $sidebarUser ?? '' }}@{{ $sidebarHost ?? '' }}">
                {{ $sidebarUser ?? '' }}@{{ $sidebarHost ?? '' }}
            </p>
        </div>
        <div class="border-b border-term-border px-4 py-3">
            <span class="font-mono text-[11px] text-term-text-dim">database</span>
            <form action="{{ route('switch-db') }}" method="post" class="mt-1">
                @csrf
                <select name="database" onchange="this.form.submit()" class="input max-w-full border-term-border py-1.5 text-xs">
                    @foreach($sidebarDatabases ?? [] as $db)
                    <option value="{{ $db }}" {{ $db === ($sidebarCurrentDb ?? '') ? 'selected' : '' }}>{{ $db }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="px-2 py-2">
            <div class="flex items-center justify-between px-1 py-1">
                <span class="font-mono text-[11px] uppercase tracking-wider text-term-text-dim inline-flex items-center gap-1.5"><i data-lucide="table-2" class="w-3.5 h-3.5"></i> tables</span>
                @php
                    $sidebarTables = $sidebarTables ?? [];
                    $tablesBySchema = collect($sidebarTables)->groupBy('schema');
                @endphp
                <span class="font-mono text-[10px] text-term-muted">{{ count($sidebarTables) }}</span>
            </div>
            <nav class="mt-1 space-y-0.5 font-mono text-xs">
                @foreach($tablesBySchema as $schema => $items)
                <div class="py-0.5">
                    <p class="truncate px-2 py-0.5 text-term-amber">{{ $schema }}</p>
                    @foreach($items as $t)
                    <div class="flex items-center gap-1 rounded px-2 py-0.5 hover:bg-term-bg/80">
                        <a href="{{ route('table.browse', ['schema' => $t->schema, 'table' => $t->name]) }}" class="min-w-0 flex-1 truncate text-term-text-dim hover:text-term-accent hover:underline" title="{{ $t->schema }}.{{ $t->name }}">{{ $t->name }}</a>
                        <a href="{{ route('table.structure', ['schema' => $t->schema, 'table' => $t->name]) }}" class="shrink-0 text-term-muted hover:text-term-amber inline-flex" title="structure"><i data-lucide="columns-2" class="w-3.5 h-3.5"></i></a>
                    </div>
                    @endforeach
                </div>
                @endforeach
                @if(empty($sidebarTables))
                <p class="px-2 py-2 text-term-muted">no tables</p>
                @endif
            </nav>
        </div>
    </div>
    <div class="border-t border-term-border p-3">
        <a href="{{ route('query.index') }}" class="btn-primary mb-2 w-full py-1.5 text-center text-xs font-mono inline-flex items-center justify-center gap-1.5"><i data-lucide="play" class="w-3.5 h-3.5 shrink-0"></i> Run SQL</a>
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit" class="w-full text-left font-mono text-[11px] text-term-text-dim hover:text-term-danger inline-flex items-center gap-1.5"><i data-lucide="log-out" class="w-3.5 h-3.5 shrink-0"></i> logout</button>
        </form>
    </div>
</aside>
