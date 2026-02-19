@extends('layout')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <a href="{{ route('home') }}" class="btn-ghost text-sm font-mono">← database</a>
        <div>
            <p class="font-mono text-xs text-term-text-dim">structure</p>
            <h1 class="font-mono text-xl font-semibold text-term-text">
                <span class="badge-schema">{{ $schema }}</span>.<span class="text-term-accent">{{ $tableName }}</span>
            </h1>
        </div>
        <a href="{{ route('table.browse', ['schema' => $schema, 'table' => $tableName]) }}" class="btn-secondary text-sm font-mono">browse</a>
        <a href="{{ route('query.index', ['table' => $schema . '.' . $tableName]) }}" class="btn-primary text-sm font-mono">sql</a>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="font-mono font-medium text-term-text">columns</h2>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>column</th>
                        <th>type</th>
                        <th>null</th>
                        <th>default</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($columns ?? [] as $col)
                    <tr>
                        <td class="font-mono font-medium text-term-text">{{ $col->name }}</td>
                        <td><span class="badge-type">{{ $col->type }}</span></td>
                        <td class="font-mono text-term-text-dim">{{ $col->notnull ? 'NO' : 'YES' }}</td>
                        <td class="font-mono text-term-text-dim">{{ $col->default ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
