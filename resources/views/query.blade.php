@extends('layout')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <p class="font-mono text-xs text-term-text-dim">sql console</p>
        <h1 class="font-mono text-2xl font-semibold text-term-text inline-flex items-center gap-2"><i data-lucide="code" class="w-7 h-7 text-term-accent shrink-0"></i> query</h1>
        <p class="mt-1 font-mono text-sm text-term-text-dim">SELECT, INSERT, UPDATE, DELETE — results limited to 500 rows.</p>
    </div>
    <form action="{{ route('query.execute') }}" method="post" class="card overflow-hidden" id="query-form">
        @csrf
        <input type="hidden" name="sort" id="query-sort" value="{{ $sort ?? '' }}" />
        <input type="hidden" name="dir" id="query-dir" value="{{ $dir ?? 'ASC' }}" />
        <input type="hidden" name="offset" id="query-offset" value="{{ $result['offset'] ?? 0 }}" />
        <div class="border-b border-term-border p-4">
            <textarea name="sql" id="sql" rows="10" class="input font-mono text-sm" placeholder="SELECT * FROM my_table LIMIT 10;" title="Cmd+Enter to execute">{{ $sql ?? '' }}</textarea>
            <p class="mt-1 font-mono text-[11px] text-term-muted">Cmd+Enter to execute</p>
        </div>
        <div class="flex items-center justify-between border-b border-term-border bg-term-panel/80 px-4 py-3 font-mono text-sm">
            <span class="text-term-text-dim">db: <span class="text-term-accent">{{ $currentDb }}</span></span>
            <button type="submit" class="btn-primary font-mono inline-flex items-center gap-1.5" id="query-submit-btn"><i data-lucide="play" class="w-4 h-4 shrink-0"></i> execute</button>
        </div>
    </form>

    {{-- Cell view modal (query results: Enter on focused cell) --}}
    <div id="query-cell-modal" class="modal-backdrop fixed inset-0 z-50 hidden flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-close></div>
        <div class="modal-panel relative z-10 w-full max-w-md rounded-lg border border-term-border bg-term-panel shadow-xl">
            <div class="border-b border-term-border px-5 py-4">
                <h2 id="query-cell-modal-title" class="font-mono text-lg font-semibold text-term-text">Cell</h2>
            </div>
            <div class="px-5 py-4 font-mono text-sm text-term-text-dim space-y-2">
                <p class="text-term-text-dim text-xs" id="query-cell-modal-column"></p>
                <div class="rounded border border-term-border bg-term-bg/50 px-3 py-2 text-term-text break-all" id="query-cell-modal-value"></div>
                <div id="query-cell-modal-link" class="hidden mt-2"></div>
            </div>
            <div class="border-t border-term-border px-5 py-4">
                <button type="button" class="btn-secondary font-mono text-sm inline-flex items-center gap-1.5" data-modal-close><i data-lucide="x" class="w-4 h-4 shrink-0"></i> Close</button>
            </div>
        </div>
    </div>

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
            <table class="data-table" id="query-result-table" tabindex="0" data-sort="{{ $sort ?? '' }}" data-dir="{{ $dir ?? 'ASC' }}" data-schema="public">
                <thead>
                    <tr>
                        @foreach($result['fields'] ?? [] as $f)
                        @php $isSortCol = isset($sort) && $sort === $f->name; @endphp
                        <th class="whitespace-nowrap">
                            <button type="button" class="query-sort-header inline-flex items-center gap-1 hover:text-term-accent focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded font-inherit {{ $isSortCol ? 'text-term-accent' : 'text-term-amber' }}" data-column="{{ $f->name }}">
                                {{ $f->name }}
                                @if($isSortCol)
                                <i data-lucide="{{ ($dir ?? 'ASC') === 'DESC' ? 'arrow-down' : 'arrow-up' }}" class="w-3.5 h-3.5 text-term-text-dim shrink-0" aria-hidden="true"></i>
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
                        <td class="max-w-xs truncate font-mono text-xs query-result-td" data-column="{{ $f->name }}" data-value="{{ e($row[$f->name] ?? '') }}">
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

        sqlInput.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });

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

        var table = document.getElementById('query-result-table');
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

            var schema = table.dataset.schema || 'public';
            var focusedCell = null;
            function setFocus(td) {
                if (focusedCell) focusedCell.classList.remove('cell-focused');
                focusedCell = td;
                if (td) {
                    td.classList.add('cell-focused');
                    td.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                    table.focus();
                }
            }
            table.querySelectorAll('.query-result-td').forEach(function(td) {
                td.addEventListener('click', function() { setFocus(td); });
            });
            table.addEventListener('keydown', function(e) {
                if (!focusedCell) return;
                var key = e.key;
                var tr = focusedCell.closest('tr');
                var cells = Array.from(tr.querySelectorAll('.query-result-td'));
                var colIndex = cells.indexOf(focusedCell);
                var rows = Array.from(table.querySelectorAll('tbody tr'));
                var rowIndex = rows.indexOf(tr);
                if (key === 'ArrowLeft') {
                    e.preventDefault();
                    if (colIndex > 0) setFocus(cells[colIndex - 1]);
                } else if (key === 'ArrowRight') {
                    e.preventDefault();
                    if (colIndex < cells.length - 1) setFocus(cells[colIndex + 1]);
                } else if (key === 'ArrowUp') {
                    e.preventDefault();
                    if (rowIndex > 0) {
                        var prevCells = rows[rowIndex - 1].querySelectorAll('.query-result-td');
                        if (prevCells[colIndex]) setFocus(prevCells[colIndex]);
                    }
                } else if (key === 'ArrowDown') {
                    e.preventDefault();
                    if (rowIndex < rows.length - 1) {
                        var nextCells = rows[rowIndex + 1].querySelectorAll('.query-result-td');
                        if (nextCells[colIndex]) setFocus(nextCells[colIndex]);
                    }
                } else if (key === 'Enter') {
                    e.preventDefault();
                    var col = focusedCell.dataset.column;
                    var val = focusedCell.dataset.value;
                    document.getElementById('query-cell-modal-column').textContent = col;
                    document.getElementById('query-cell-modal-value').textContent = val === '' ? 'NULL' : val;
                    var linkContainer = document.getElementById('query-cell-modal-link');
                    linkContainer.classList.add('hidden');
                    linkContainer.innerHTML = '';
                    if (col && col.lastIndexOf('_id') === col.length - 3 && val !== '' && val !== 'null') {
                        var linkedTable = col.slice(0, -3) + 's';
                        var sqlVal = /^\d+$/.test(String(val)) ? val : "'" + String(val).replace(/'/g, "''") + "'";
                        var sql = 'SELECT * FROM "' + schema + '"."' + linkedTable + '" WHERE "id" = ' + sqlVal + ' LIMIT 100';
                        var href = window.location.origin + '/table/' + encodeURIComponent(schema) + '/' + encodeURIComponent(linkedTable) + '?sql=' + encodeURIComponent(sql);
                        var a = document.createElement('a');
                        a.href = href;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.className = 'inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs';
                        a.innerHTML = '<i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0"></i> View linked record (opens in new tab)';
                        linkContainer.appendChild(a);
                        linkContainer.classList.remove('hidden');
                        if (window.refreshLucideIcons) window.refreshLucideIcons();
                    }
                    document.getElementById('query-cell-modal').classList.remove('hidden');
                }
            });
        }
    })();
    </script>
</div>
@endsection
