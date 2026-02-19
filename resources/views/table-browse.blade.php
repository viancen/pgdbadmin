@php
    use Illuminate\Support\Str;
    $baseParams = array_filter(['sql' => $sql ?? null, 'limit' => $limit]);
    $baseUrl = route('table.browse', ['schema' => $schema, 'table' => $tableName]) . '?' . http_build_query(array_merge($baseParams, array_filter(['sort' => $sort ?? null, 'dir' => $dir ?? null])));
    $columnMeta = [];
    $columnLinkedTables = [];
    foreach ($columns ?? [] as $c) {
        $columnMeta[$c->name] = ['type' => $c->type ?? '', 'notnull' => !empty($c->notnull), 'is_pk' => in_array($c->name, $primaryKey ?? [], true)];
        if (Str::endsWith($c->name, '_id')) {
            $columnLinkedTables[$c->name] = Str::plural(Str::beforeLast($c->name, '_id'));
        }
    }
    $canEditRows = !empty($primaryKey) && ($sql === null || $sql === '' || trim($sql ?? '') === trim($defaultSql));
@endphp
@extends('layout')

@section('content')
<div class="p-6">
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <a href="{{ route('home') }}" class="btn-ghost text-sm font-mono inline-flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4 shrink-0"></i> database</a>
        <div>
            <p class="font-mono text-xs text-term-text-dim">table</p>
            <h1 class="font-mono text-xl font-semibold text-term-text inline-flex items-center gap-2">
                <i data-lucide="table-2" class="w-5 h-5 text-term-accent shrink-0"></i>
                <span class="badge-schema">{{ $schema }}</span>.<span class="text-term-accent">{{ $tableName }}</span>
            </h1>
        </div>
        <a href="{{ route('table.structure', ['schema' => $schema, 'table' => $tableName]) }}" class="btn-secondary text-sm font-mono inline-flex items-center gap-1.5"><i data-lucide="columns-2" class="w-4 h-4 shrink-0"></i> structure</a>
        <a href="{{ route('query.index', ['table' => $schema . '.' . $tableName]) }}" class="btn-primary text-sm font-mono inline-flex items-center gap-1.5"><i data-lucide="code" class="w-4 h-4 shrink-0"></i> sql</a>
    </div>

    {{-- Query box: run custom SQL on this table with pagination --}}
    <form action="{{ route('table.browse', ['schema' => $schema, 'table' => $tableName]) }}" method="get" class="card overflow-hidden mb-4">
        <div class="border-b border-term-border p-5">
            <label for="table-query-sql" class="font-mono text-xs text-term-text-dim block mb-2">Query (results paginated below)</label>
            <textarea name="sql" id="table-query-sql" rows="3" class="input font-mono text-sm" placeholder="{{ $defaultSql }}">{{ old('sql', $sql ?? $defaultSql) }}</textarea>
        </div>
        <div class="flex items-center justify-between border-term-border bg-term-panel/80 px-5 py-4 font-mono text-sm gap-4">
            <span class="text-term-text-dim text-xs">Default: SELECT * FROM table LIMIT 100 — change and run to filter/sort.</span>
            <div class="flex items-center gap-3">
                <input type="hidden" name="limit" value="{{ $limit }}" />
                <button type="submit" class="btn-primary font-mono text-sm inline-flex items-center gap-1.5"><i data-lucide="play" class="w-4 h-4 shrink-0"></i> Run query</button>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span class="font-mono text-term-text-dim">{{ $total }} row{{ $total !== 1 ? 's' : '' }}</span>
            @include('partials.pagination', ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'baseUrl' => $baseUrl])
        </div>
        <div class="table-container max-h-[70vh] overflow-auto">
            <table class="data-table" id="table-browse-data" tabindex="0"
                data-schema="{{ $schema }}"
                data-table="{{ $tableName }}"
                data-browse-base-url="{{ route('table.browse', ['schema' => $schema, 'table' => $tableName]) }}"
                data-update-url="{{ route('table.row.update', ['schema' => $schema, 'table' => $tableName]) }}"
                data-delete-url="{{ route('table.row.delete', ['schema' => $schema, 'table' => $tableName]) }}"
                data-can-edit="{{ $canEditRows ? '1' : '0' }}"
                data-csrf="{{ csrf_token() }}"
                data-column-meta="{{ json_encode($columnMeta) }}"
                data-column-linked-tables="{{ json_encode($columnLinkedTables) }}">
                <thead>
                    <tr>
                        @foreach($fields ?? [] as $f)
                        @php
                            $isSortCol = isset($sort) && $sort === $f->name;
                            $nextDir = $isSortCol && ($dir ?? 'ASC') === 'DESC' ? 'ASC' : 'DESC';
                            $sortUrl = route('table.browse', ['schema' => $schema, 'table' => $tableName]) . '?' . http_build_query(array_merge($baseParams, ['sort' => $f->name, 'dir' => $nextDir, 'offset' => 0]));
                        @endphp
                        <th class="whitespace-nowrap">
                            <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1 hover:text-term-accent focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded {{ $isSortCol ? 'text-term-accent' : 'text-term-amber' }}">
                                {{ $f->name }}
                                @if($isSortCol)
                                <i data-lucide="{{ ($dir ?? 'ASC') === 'DESC' ? 'arrow-down' : 'arrow-up' }}" class="w-3.5 h-3.5 text-term-text-dim shrink-0" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows ?? [] as $row)
                    <tr class="table-browse-row {{ $canEditRows ? 'cursor-pointer' : '' }}"
                        data-row="{{ json_encode($row) }}"
                        @if($canEditRows) title="Double-click to edit" @endif>
                        @foreach($fields ?? [] as $f)
                        @php $meta = $columnMeta[$f->name] ?? null; $isPk = $meta['is_pk'] ?? false; @endphp
                        <td class="max-w-xs font-mono text-xs table-browse-cell align-middle table-browse-td"
                            data-column="{{ $f->name }}"
                            data-value="{{ e($row[$f->name] ?? '') }}"
                            @if($meta) data-type="{{ e($meta['type']) }}" data-notnull="{{ $meta['notnull'] ? '1' : '0' }}" data-is-pk="{{ $isPk ? '1' : '0' }}" @endif
                            title="{{ is_string($row[$f->name] ?? null) ? e($row[$f->name]) : ($row[$f->name] ?? '') }}">
                            @if(($row[$f->name] ?? null) === null)
                            <span class="text-term-muted">NULL</span>
                            @else
                            <span class="inline-flex items-center gap-1 truncate max-w-full">
                                <span class="truncate">{{ $row[$f->name] }}</span>
                                @if(Str::endsWith($f->name, '_id'))
                                @php
                                    $linkedTable = Str::plural(Str::beforeLast($f->name, '_id'));
                                    $rawVal = $row[$f->name];
                                    $linkUrl = route('table.browse', ['schema' => $schema, 'table' => $linkedTable]) . '?id=' . rawurlencode((string) $rawVal);
                                @endphp
                                <a href="{{ $linkUrl }}" target="_blank" rel="noopener noreferrer" class="shrink-0 inline-flex items-center text-term-accent hover:text-term-amber focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded" title="View linked record in {{ $linkedTable }} (new tab)"><i data-lucide="link" class="w-3.5 h-3.5"></i></a>
                                @endif
                            </span>
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

@if($canEditRows)
{{-- Context menu: right-click row → Verwijder record --}}
<div id="table-browse-context-menu" class="fixed z-40 hidden min-w-[10rem] rounded-lg border border-term-border bg-term-panel shadow-xl py-1 font-mono text-sm">
    <button type="button" class="table-context-delete w-full text-left px-4 py-2 text-term-danger hover:bg-term-danger/10 focus:outline-none focus:ring-0 flex items-center gap-2" data-action="delete"><i data-lucide="trash-2" class="w-4 h-4 shrink-0"></i> Verwijder record</button>
</div>
@include('partials.modal', [
    'id' => 'row-delete-confirm-modal',
    'title' => 'Verwijder record',
    'body' => '<p>Weet je het zeker? Dit kan niet ongedaan worden gemaakt.</p>',
    'confirmLabel' => 'Verwijderen',
    'cancelLabel' => 'Annuleren',
])
@endif

<script>
(function() {
    var table = document.getElementById('table-browse-data');
    if (!table) return;
    var allCells = function() { return table.querySelectorAll('tbody .table-browse-td'); };
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

    table.addEventListener('click', function(e) {
        var td = e.target.closest('.table-browse-td');
        if (td) setFocus(td);
    });

    table.addEventListener('keydown', function(e) {
        if (!focusedCell) return;
        var key = e.key;
        var tr = focusedCell.closest('tr');
        var cells = Array.from(tr.querySelectorAll('.table-browse-td'));
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
                var prevRow = rows[rowIndex - 1];
                var prevCells = prevRow.querySelectorAll('.table-browse-td');
                if (prevCells[colIndex]) setFocus(prevCells[colIndex]);
            }
        } else if (key === 'ArrowDown') {
            e.preventDefault();
            if (rowIndex < rows.length - 1) {
                var nextRow = rows[rowIndex + 1];
                var nextCells = nextRow.querySelectorAll('.table-browse-td');
                if (nextCells[colIndex]) setFocus(nextCells[colIndex]);
            }
        } else if (key === 'Enter') {
            e.preventDefault();
            if (table.dataset.canEdit === '1') {
                tr.dispatchEvent(new MouseEvent('dblclick', { bubbles: true, cancelable: true, view: window }));
            }
        }
    });
})();
</script>

@if($canEditRows)
{{-- Context menu + delete confirm script --}}
<script>
(function() {
    var table = document.getElementById('table-browse-data');
    if (!table || table.dataset.canEdit !== '1') return;
    var deleteUrl = table.dataset.deleteUrl;
    var csrf = table.dataset.csrf;
    var columnMeta = JSON.parse(table.dataset.columnMeta || '{}');
    var menu = document.getElementById('table-browse-context-menu');
    var deleteModal = document.getElementById('row-delete-confirm-modal');
    var rowToDelete = null;

    function getPkFromRow(row) {
        var pk = {};
        Object.keys(columnMeta).forEach(function(col) {
            if (columnMeta[col] && columnMeta[col].is_pk) pk[col] = row[col];
        });
        return pk;
    }

    table.querySelectorAll('.table-browse-row').forEach(function(tr) {
        tr.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            if (!tr.dataset.row) return;
            rowToDelete = tr;
            if (menu) {
                menu.style.left = e.clientX + 'px';
                menu.style.top = e.clientY + 'px';
                menu.classList.remove('hidden');
                if (window.refreshLucideIcons) window.refreshLucideIcons();
            }
        });
    });

    function hideContextMenu() {
        if (menu) menu.classList.add('hidden');
    }

    document.addEventListener('click', function() { hideContextMenu(); });
    document.addEventListener('scroll', function() { hideContextMenu(); }, true);

    var pendingDeleteTr = null;
    if (menu) {
        menu.querySelector('.table-context-delete').addEventListener('click', function(e) {
            e.stopPropagation();
            hideContextMenu();
            if (!rowToDelete || !deleteModal) return;
            pendingDeleteTr = rowToDelete;
            rowToDelete = null;
            deleteModal.classList.remove('hidden');
        });
    }
    var confirmBtn = deleteModal ? deleteModal.querySelector('.modal-confirm') : null;
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!pendingDeleteTr) return;
            var tr = pendingDeleteTr;
            pendingDeleteTr = null;
            var row = JSON.parse(tr.dataset.row || '{}');
            var pk = getPkFromRow(row);
            confirmBtn.disabled = true;
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ pk: pk })
            })
            .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
            .then(function(res) {
                confirmBtn.disabled = false;
                deleteModal.classList.add('hidden');
                if (res.ok && res.json.success) {
                    tr.remove();
                } else {
                    alert(res.json.error || 'Verwijderen mislukt.');
                }
            })
            .catch(function() {
                confirmBtn.disabled = false;
                alert('Netwerkfout.');
            });
        });
    }
})();
</script>
{{-- Edit row modal: double-click a row to open --}}
<div id="row-edit-modal" class="modal-backdrop fixed inset-0 z-50 hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="row-edit-modal-title">
    <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-close></div>
    <div class="modal-panel relative z-10 w-full max-w-lg max-h-[90vh] overflow-hidden rounded-lg border border-term-border bg-term-panel shadow-xl flex flex-col">
        <div class="border-b border-term-border px-6 py-5 shrink-0">
            <h2 id="row-edit-modal-title" class="font-mono text-lg font-semibold text-term-text">Edit record</h2>
        </div>
        <div class="modal-body px-6 py-5 overflow-y-auto font-mono text-sm text-term-text-dim space-y-3" id="row-edit-form-container"></div>
        <div class="modal-footer flex justify-end gap-3 border-t border-term-border px-6 py-5 shrink-0">
            <button type="button" class="modal-cancel btn-secondary font-mono text-sm inline-flex items-center gap-1.5" data-modal-close><i data-lucide="x" class="w-4 h-4 shrink-0"></i> Cancel</button>
            <button type="button" class="row-edit-save btn-primary font-mono text-sm inline-flex items-center gap-1.5"><i data-lucide="save" class="w-4 h-4 shrink-0"></i> Save</button>
        </div>
        <div id="row-edit-error" class="hidden px-6 pb-5 text-term-danger text-xs font-mono"></div>
    </div>
</div>

<script>
(function() {
    const table = document.getElementById('table-browse-data');
    if (!table || table.dataset.canEdit !== '1') return;
    const updateUrl = table.dataset.updateUrl;
    const csrf = table.dataset.csrf;
    const schema = table.dataset.schema || 'public';
    const columnMeta = JSON.parse(table.dataset.columnMeta || '{}');
    const columnLinkedTables = JSON.parse(table.dataset.columnLinkedTables || '{}');
    const modal = document.getElementById('row-edit-modal');
    const formContainer = document.getElementById('row-edit-form-container');
    const saveBtn = document.querySelector('.row-edit-save');
    const errorEl = document.getElementById('row-edit-error');
    let currentTr = null;

    function linkedRecordUrl(columnName, value) {
        var linkedTable = columnLinkedTables[columnName];
        if (!linkedTable || value === null || value === undefined) return null;
        var idVal = (typeof value === 'number') ? String(value) : String(value);
        return window.location.origin + '/table/' + encodeURIComponent(schema) + '/' + encodeURIComponent(linkedTable) + '?id=' + encodeURIComponent(idVal);
    }

    function numericType(type) {
        if (!type) return false;
        const t = (type + '').toLowerCase();
        return /int|serial|numeric|decimal|real|double|float|smallint|bigint/.test(t);
    }

    function validateCell(value, meta) {
        if (!meta) return null;
        if (meta.notnull && (value === '' || value === null || (typeof value === 'string' && value.trim() === '')))
            return 'Required';
        if (meta.is_pk) return null;
        if (numericType(meta.type) && value !== '' && value !== null) {
            const n = typeof value === 'string' ? value.trim() : value;
            if (n !== '' && isNaN(Number(n))) return 'Must be a number';
        }
        return null;
    }

    function getInputType(meta) {
        if (!meta) return 'text';
        if (numericType(meta.type)) return 'number';
        if ((meta.type + '').toLowerCase().includes('bool')) return 'checkbox';
        return 'text';
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    table.querySelectorAll('.table-browse-row').forEach(function(tr) {
        tr.addEventListener('dblclick', function() {
            if (!tr.dataset.row) return;
            currentTr = tr;
            const row = JSON.parse(tr.dataset.row || '{}');
            formContainer.innerHTML = '';
            errorEl.classList.add('hidden');
            errorEl.textContent = '';

            const columns = tr.querySelectorAll('.table-browse-cell[data-column]');
            columns.forEach(function(td) {
                const col = td.dataset.column;
                const meta = columnMeta[col];
                const isPk = meta && meta.is_pk;
                const currentVal = row[col];
                const displayVal = currentVal === null || currentVal === undefined ? '' : currentVal;

                const rowEl = document.createElement('div');
                rowEl.className = 'space-y-1';
                const label = document.createElement('label');
                label.className = 'block text-term-text-dim text-xs';
                label.textContent = col + (isPk ? ' (primary key)' : '');
                rowEl.appendChild(label);

                if (isPk) {
                    const readOnly = document.createElement('div');
                    readOnly.className = 'rounded border border-term-border bg-term-bg/50 px-3 py-2 text-term-text font-mono text-xs';
                    readOnly.textContent = displayVal === '' ? 'NULL' : displayVal;
                    rowEl.appendChild(readOnly);
                    var pkLinkUrl = linkedRecordUrl(col, currentVal);
                    if (pkLinkUrl) {
                        var pkLink = document.createElement('a');
                        pkLink.href = pkLinkUrl;
                        pkLink.target = '_blank';
                        pkLink.rel = 'noopener noreferrer';
                        pkLink.className = 'mt-1 inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs';
                        pkLink.innerHTML = '<i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0"></i> View linked record';
                        rowEl.appendChild(pkLink);
                    }
                } else {
                    const inputType = getInputType(meta);
                    let input;
                    if (inputType === 'checkbox') {
                        input = document.createElement('input');
                        input.type = 'checkbox';
                        input.checked = currentVal === true || currentVal === 't' || currentVal === '1' || currentVal === 'yes';
                        input.className = 'rounded border-term-border bg-term-bg text-term-accent focus:ring-term-accent';
                    } else {
                        input = document.createElement('input');
                        input.type = inputType;
                        input.value = displayVal;
                        input.className = 'input w-full py-1.5 text-xs font-mono';
                    }
                    input.dataset.column = col;
                    input.name = col;
                    rowEl.appendChild(input);
                    const err = document.createElement('div');
                    err.className = 'row-edit-field-error text-term-danger text-xs hidden';
                    rowEl.appendChild(err);
                    var linkUrl = linkedRecordUrl(col, currentVal);
                    if (linkUrl) {
                        var link = document.createElement('a');
                        link.href = linkUrl;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.className = 'mt-1 inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs';
                        link.innerHTML = '<i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0"></i> View linked record';
                        rowEl.appendChild(link);
                    }
                }
                formContainer.appendChild(rowEl);
            });

            if (window.refreshLucideIcons) window.refreshLucideIcons();
            modal.classList.remove('hidden');
        });
    });

    function getPkFromRow(row) {
        const pk = {};
        Object.keys(columnMeta).forEach(function(col) {
            if (columnMeta[col] && columnMeta[col].is_pk) pk[col] = row[col];
        });
        return pk;
    }

    function getUpdatesFromModal() {
        const updates = {};
        formContainer.querySelectorAll('input[data-column]').forEach(function(input) {
            const col = input.dataset.column;
            const meta = columnMeta[col];
            if (meta && meta.is_pk) return;
            const val = input.type === 'checkbox' ? (input.checked ? 't' : 'f') : input.value;
            updates[col] = val === '' ? null : val;
        });
        return updates;
    }

    function collectValidationErrors() {
        const errors = {};
        formContainer.querySelectorAll('input[data-column]').forEach(function(input) {
            const col = input.dataset.column;
            const meta = columnMeta[col];
            if (meta && meta.is_pk) return;
            const val = input.type === 'checkbox' ? (input.checked ? 't' : 'f') : input.value;
            const msg = validateCell(val, meta);
            if (msg) errors[col] = msg;
        });
        return errors;
    }

    function showFieldErrors(errors) {
        formContainer.querySelectorAll('.row-edit-field-error').forEach(function(el) {
            el.textContent = '';
            el.classList.add('hidden');
        });
        Object.keys(errors).forEach(function(col) {
            const input = formContainer.querySelector('input[data-column="' + col + '"]');
            if (input && input.parentNode) {
                const errEl = input.parentNode.querySelector('.row-edit-field-error');
                if (errEl) {
                    errEl.textContent = errors[col];
                    errEl.classList.remove('hidden');
                }
            }
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            if (!currentTr) return;
            const row = JSON.parse(currentTr.dataset.row || '{}');
            const pk = getPkFromRow(row);
            const errs = collectValidationErrors();
            if (Object.keys(errs).length) {
                showFieldErrors(errs);
                return;
            }
            const updates = getUpdatesFromModal();
            if (Object.keys(updates).length === 0) {
                modal.classList.add('hidden');
                currentTr = null;
                return;
            }
            errorEl.classList.add('hidden');
            saveBtn.disabled = true;
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ pk, updates })
            })
            .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
            .then(function(res) {
                saveBtn.disabled = false;
                if (res.ok && res.json.success) {
                    Object.keys(updates).forEach(function(col) {
                        row[col] = updates[col];
                    });
                    currentTr.dataset.row = JSON.stringify(row);
                    currentTr.querySelectorAll('.table-browse-cell[data-column]').forEach(function(td) {
                        const col = td.dataset.column;
                        if (updates[col] !== undefined) {
                            const val = updates[col];
                            if (val === null || val === undefined) {
                                td.innerHTML = '<span class="text-term-muted">NULL</span>';
                            } else {
                                td.textContent = val;
                            }
                        }
                    });
                    modal.classList.add('hidden');
                    currentTr = null;
                } else {
                    errorEl.textContent = res.json.error || 'Update failed';
                    errorEl.classList.remove('hidden');
                }
            })
            .catch(function() {
                saveBtn.disabled = false;
                errorEl.textContent = 'Network error';
                errorEl.classList.remove('hidden');
            });
        });
    }
})();
</script>
@endif
@endsection
