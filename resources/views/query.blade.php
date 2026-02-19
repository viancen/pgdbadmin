@extends('layout')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <p class="font-mono text-xs text-term-text-dim">sql console</p>
        <h1 class="font-mono text-2xl font-semibold text-term-text">query</h1>
        <p class="mt-1 font-mono text-sm text-term-text-dim">SELECT, INSERT, UPDATE, DELETE — results limited to 500 rows.</p>
    </div>
    <form action="{{ route('query.execute') }}" method="post" class="card overflow-hidden" id="query-form">
        @csrf
        <input type="hidden" name="sort" id="query-sort" value="{{ $sort ?? '' }}" />
        <input type="hidden" name="dir" id="query-dir" value="{{ $dir ?? 'ASC' }}" />
        <input type="hidden" name="offset" id="query-offset" value="{{ $result['offset'] ?? 0 }}" />
        <div class="border-b border-term-border p-4">
            <textarea name="sql" id="sql" rows="10" class="input font-mono text-sm" placeholder="SELECT * FROM my_table LIMIT 10;">{{ $sql ?? '' }}</textarea>
        </div>
        <div class="flex items-center justify-between border-b border-term-border bg-term-panel/80 px-4 py-3 font-mono text-sm">
            <span class="text-term-text-dim">db: <span class="text-term-accent">{{ $currentDb }}</span></span>
            <button type="submit" class="btn-primary font-mono" id="query-submit-btn">execute</button>
        </div>
    </form>

    {{-- Confirmation modal for DELETE / TRUNCATE / UPDATE --}}
    @include('partials.modal', [
        'id' => 'query-confirm-modal',
        'title' => 'Confirm destructive query',
        'body' => '<p class="mb-2">This query will modify or remove data. Please confirm:</p><pre id="query-confirm-sql" class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap break-all text-term-danger"></pre>',
        'confirmLabel' => 'Execute',
        'cancelLabel' => 'Cancel',
    ])
    @if(isset($error) && $error)
    <div class="mt-4 rounded border border-term-danger/50 bg-term-danger/10 px-4 py-3 font-mono text-sm text-term-danger">
        {{ $error }}
    </div>
    @endif
    @if(isset($result) && $result && !empty($result['fields'] ?? []))
    <div class="card mt-6 overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span class="font-mono text-term-text-dim">{{ $result['rowCount'] ?? 0 }} row{{ ($result['rowCount'] ?? 0) !== 1 ? 's' : '' }} returned</span>
            @if(isset($result['total']) && $result['total'] > count($result['rows'] ?? []))
            <span class="font-mono text-xs text-term-muted">(showing first {{ count($result['rows'] ?? []) }})</span>
            @endif
            @include('partials.pagination', [
                'total' => $result['total'] ?? count($result['rows'] ?? []),
                'limit' => $result['limit'] ?? 100,
                'offset' => $result['offset'] ?? 0,
                'baseUrl' => null,
            ])
        </div>
        <div class="table-container max-h-[60vh] overflow-auto">
            <table class="data-table" data-sort="{{ $sort ?? '' }}" data-dir="{{ $dir ?? 'ASC' }}">
                <thead>
                    <tr>
                        @foreach($result['fields'] ?? [] as $f)
                        @php $isSortCol = isset($sort) && $sort === $f->name; @endphp
                        <th class="whitespace-nowrap">
                            <button type="button" class="query-sort-header inline-flex items-center gap-1 hover:text-term-accent focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded font-inherit {{ $isSortCol ? 'text-term-accent' : 'text-term-amber' }}" data-column="{{ $f->name }}">
                                {{ $f->name }}
                                @if($isSortCol)
                                <span class="text-term-text-dim" aria-hidden="true">{{ ($dir ?? 'ASC') === 'DESC' ? '↓' : '↑' }}</span>
                                @endif
                            </button>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($result['rows'] ?? [] as $row)
                    <tr>
                        @foreach($result['fields'] ?? [] as $f)
                        <td class="max-w-xs truncate font-mono text-xs">
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
        @if(empty($result['rows'] ?? []))
        <div class="px-6 py-8 text-center font-mono text-term-text-dim">no rows.</div>
        @endif
    </div>
    @endif
    @if(isset($message) && $message && empty($result['fields'] ?? []))
    <div class="mt-4 rounded border border-term-success/50 bg-term-success/10 px-4 py-3 font-mono text-sm text-term-success">
        {{ $message }}
    </div>
    @endif

    <script>
    (function() {
        var form = document.getElementById('query-form');
        var sqlInput = document.getElementById('sql');
        var confirmModal = document.getElementById('query-confirm-modal');
        var confirmSqlEl = document.getElementById('query-confirm-sql');
        if (!form || !sqlInput) return;

        function isDestructive(sql) {
            var t = (sql || '').trim();
            return /^\s*(DELETE|TRUNCATE|UPDATE)\s+/i.test(t);
        }

        form.addEventListener('submit', function(e) {
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }
            if (!isDestructive(sqlInput.value)) return;
            e.preventDefault();
            if (confirmSqlEl) confirmSqlEl.textContent = sqlInput.value;
            if (confirmModal) confirmModal.classList.remove('hidden');
        });

        if (confirmModal) {
            confirmModal.querySelector('.modal-confirm').addEventListener('click', function() {
                confirmModal.classList.add('hidden');
                form.dataset.confirmed = '1';
                form.submit();
            });
        }

        var table = document.querySelector('.data-table[data-sort]');
        if (table) {
            var sortInput = document.getElementById('query-sort');
            var dirInput = document.getElementById('query-dir');
            var currentSort = table.dataset.sort || '';
            var currentDir = (table.dataset.dir || 'ASC').toUpperCase();
            table.querySelectorAll('.query-sort-header').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var col = this.dataset.column;
                    var nextDir = (currentSort === col && currentDir === 'DESC') ? 'ASC' : 'DESC';
                    sortInput.value = col;
                    dirInput.value = nextDir;
                    form.submit();
                });
            });
        }
    })();
    </script>
</div>
@endsection
