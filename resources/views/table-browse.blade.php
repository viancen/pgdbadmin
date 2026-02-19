@extends('layout')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <a href="{{ route('home') }}" class="btn-ghost text-sm font-mono">← database</a>
        <div>
            <p class="font-mono text-xs text-term-text-dim">table</p>
            <h1 class="font-mono text-xl font-semibold text-term-text">
                <span class="badge-schema">{{ $schema }}</span>.<span class="text-term-accent">{{ $tableName }}</span>
            </h1>
        </div>
        <a href="{{ route('table.structure', ['schema' => $schema, 'table' => $tableName]) }}" class="btn-secondary text-sm font-mono">structure</a>
        <a href="{{ route('query.index', ['table' => $schema . '.' . $tableName]) }}" class="btn-primary text-sm font-mono">sql</a>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span class="font-mono text-term-text-dim">{{ $total }} row{{ $total !== 1 ? 's' : '' }}</span>
            @include('partials.pagination', ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'baseUrl' => route('table.browse', ['schema' => $schema, 'table' => $tableName])])
        </div>
        <div class="table-container max-h-[70vh] overflow-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach($fields ?? [] as $f)
                        <th class="whitespace-nowrap">{{ $f->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows ?? [] as $row)
                    <tr>
                        @foreach($fields ?? [] as $f)
                        <td class="max-w-xs truncate font-mono text-xs" title="{{ is_string($row[$f->name] ?? null) ? e($row[$f->name]) : ($row[$f->name] ?? '') }}">
                            @if(($row[$f->name] ?? null) === null)
                            <span class="text-term-muted">NULL</span>
                            @else
                            {{ $row[$f->name] }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(empty($rows))
        <div class="px-6 py-12 text-center font-mono text-term-text-dim">no rows.</div>
        @endif
    </div>
</div>
@endsection
