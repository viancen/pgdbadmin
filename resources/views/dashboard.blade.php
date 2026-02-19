@extends('layout')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-term-text-dim">database</p>
            <h1 class="font-mono text-2xl font-semibold text-term-text"><span class="text-term-accent">{{ $currentDb }}</span></h1>
        </div>
        <a href="{{ route('query.index') }}" class="btn-primary font-mono">run sql</a>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="font-mono font-medium text-term-text">tables</h2>
            <p class="mt-0.5 text-xs text-term-text-dim">Click a table to browse data or view structure</p>
        </div>
        @if(isset($error) && $error)
        <div class="border-b border-term-border bg-term-danger/10 px-6 py-3 font-mono text-sm text-term-danger">{{ $error }}</div>
        @endif
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>schema</th>
                        <th>table</th>
                        <th>columns</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tables ?? [] as $t)
                    <tr>
                        <td><span class="badge-schema">{{ $t->schema }}</span></td>
                        <td>
                            <a href="{{ route('table.browse', ['schema' => $t->schema, 'table' => $t->name]) }}" class="font-mono font-medium text-term-accent hover:underline">{{ $t->name }}</a>
                        </td>
                        <td class="font-mono text-term-text-dim">{{ $t->column_count }}</td>
                        <td class="flex gap-2">
                            <a href="{{ route('table.browse', ['schema' => $t->schema, 'table' => $t->name]) }}" class="btn-ghost text-xs">browse</a>
                            <a href="{{ route('table.structure', ['schema' => $t->schema, 'table' => $t->name]) }}" class="btn-ghost text-xs">structure</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(empty($tables))
        <div class="px-6 py-12 text-center font-mono text-term-text-dim">no tables in this database.</div>
        @endif
    </div>
</div>
@endsection
