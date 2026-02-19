@php
    $baseParams = array_filter(['sql' => $sql ?? null, 'limit' => $limit]);
    $baseUrl = route('table.browse', ['schema' => $schema, 'table' => $tableName]) . '?' . http_build_query(array_merge($baseParams, array_filter(['sort' => $sort ?? null, 'dir' => $dir ?? null])));
    $columnMeta = [];
    foreach ($columns ?? [] as $c) {
        $columnMeta[$c->name] = ['type' => $c->type ?? '', 'notnull' => !empty($c->notnull), 'is_pk' => in_array($c->name, $primaryKey ?? [], true)];
    }
    $canEditRows = !empty($primaryKey) && ($sql === null || $sql === '' || trim($sql ?? '') === trim($defaultSql));
@endphp
@extends('layout')

@section('content')
<div class="p-6">
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

    {{-- Query box: run custom SQL on this table with pagination --}}
    <form action="{{ route('table.browse', ['schema' => $schema, 'table' => $tableName]) }}" method="get" class="card overflow-hidden mb-4">
        <div class="border-b border-term-border p-3">
            <label for="table-query-sql" class="font-mono text-xs text-term-text-dim block mb-1">Query (results paginated below)</label>
            <textarea name="sql" id="table-query-sql" rows="3" class="input font-mono text-sm" placeholder="{{ $defaultSql }}">{{ old('sql', $sql ?? $defaultSql) }}</textarea>
        </div>
        <div class="flex items-center justify-between border-term-border bg-term-panel/80 px-4 py-2 font-mono text-sm">
            <span class="text-term-text-dim text-xs">Default: SELECT * FROM table LIMIT 100 — change and run to filter/sort.</span>
            <div class="flex items-center gap-2">
                <input type="hidden" name="limit" value="{{ $limit }}" />
                <button type="submit" class="btn-primary font-mono text-sm">Run query</button>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <span class="font-mono text-term-text-dim">{{ $total }} row{{ $total !== 1 ? 's' : '' }}</span>
            @include('partials.pagination', ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'baseUrl' => $baseUrl])
        </div>
        <div class="table-container max-h-[70vh] overflow-auto">
            <table class="data-table" id="table-browse-data"
                data-schema="{{ $schema }}"
                data-table="{{ $tableName }}"
                data-update-url="{{ route('table.row.update', ['schema' => $schema, 'table' => $tableName]) }}"
                data-can-edit="{{ $canEditRows ? '1' : '0' }}"
                data-csrf="{{ csrf_token() }}"
                data-column-meta="{{ json_encode($columnMeta) }}">
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
                                <span class="text-term-text-dim" aria-hidden="true">{{ ($dir ?? 'ASC') === 'DESC' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        @endforeach
                        @if($canEditRows)
                        <th class="whitespace-nowrap w-0 text-term-text-dim">edit</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows ?? [] as $row)
                    <tr class="table-browse-row {{ $canEditRows ? 'cursor-pointer' : '' }}"
                        data-row="{{ json_encode($row) }}">
                        @foreach($fields ?? [] as $f)
                        @php $meta = $columnMeta[$f->name] ?? null; $isPk = $meta['is_pk'] ?? false; @endphp
                        <td class="max-w-xs truncate font-mono text-xs table-browse-cell"
                            data-column="{{ $f->name }}"
                            data-value="{{ e($row[$f->name] ?? '') }}"
                            @if($meta) data-type="{{ e($meta['type']) }}" data-notnull="{{ $meta['notnull'] ? '1' : '0' }}" data-is-pk="{{ $isPk ? '1' : '0' }}" @endif
                            title="{{ is_string($row[$f->name] ?? null) ? e($row[$f->name]) : ($row[$f->name] ?? '') }}">
                            @if(($row[$f->name] ?? null) === null)
                            <span class="text-term-muted">NULL</span>
                            @else
                            {{ $row[$f->name] }}
                            @endif
                        </td>
                        @endforeach
                        @if($canEditRows)
                        <td class="w-0 p-0"></td>
                        @endif
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
<script>
(function() {
    const table = document.getElementById('table-browse-data');
    if (!table || table.dataset.canEdit !== '1') return;
    const updateUrl = table.dataset.updateUrl;
    const csrf = table.dataset.csrf;
    const columnMeta = JSON.parse(table.dataset.columnMeta || '{}');

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

    table.querySelectorAll('.table-browse-row').forEach(function(tr) {
        tr.addEventListener('click', function(e) {
            if (tr.classList.contains('editing')) return;
            if (e.target.closest('.table-browse-actions')) return;
            enterEditMode(tr);
        });
    });

    function enterEditMode(tr) {
        const row = JSON.parse(tr.dataset.row || '{}');
        tr.classList.add('editing');
        tr.querySelectorAll('.table-browse-cell').forEach(function(td) {
            const col = td.dataset.column;
            const meta = columnMeta[col];
            const isPk = meta && meta.is_pk;
            const currentVal = row[col];
            const displayVal = currentVal === null || currentVal === undefined ? '' : currentVal;

            if (isPk) {
                td.classList.add('bg-term-panel/50');
                td.innerHTML = '<span class="text-term-muted font-mono text-xs">' + (displayVal === '' ? 'NULL' : escapeHtml(String(displayVal))) + '</span>';
                td.setAttribute('data-raw-value', displayVal);
                return;
            }

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
                input.className = 'input w-full min-w-0 py-1 text-xs font-mono';
            }
            input.dataset.column = col;
            td.innerHTML = '';
            td.classList.add('bg-term-panel/50');
            td.appendChild(input);
            const err = document.createElement('div');
            err.className = 'table-browse-cell-error text-term-danger text-xs mt-0.5 hidden';
            td.appendChild(err);

            input.addEventListener('blur', function() {
                const val = inputType === 'checkbox' ? (input.checked ? 't' : 'f') : input.value;
                const msg = validateCell(val, meta);
                err.textContent = msg || '';
                err.classList.toggle('hidden', !msg);
            });
        });

        const actionsCell = tr.querySelector('td:last-child');
        if (actionsCell && !actionsCell.querySelector('.table-browse-actions')) {
            actionsCell.classList.remove('p-0');
            actionsCell.classList.add('bg-term-panel/50', 'align-middle');
            actionsCell.innerHTML = '<div class="table-browse-actions flex items-center gap-2"><button type="button" class="btn-primary table-browse-save text-xs">Save</button><button type="button" class="btn-ghost table-browse-cancel text-xs">Cancel</button></div>';
            actionsCell.querySelector('.table-browse-save').addEventListener('click', function() { saveRow(tr); });
            actionsCell.querySelector('.table-browse-cancel').addEventListener('click', function() { cancelEdit(tr); });
        }
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function getEditedValues(tr) {
        const row = JSON.parse(tr.dataset.row || '{}');
        const pk = {};
        const updates = {};
        const meta = columnMeta;
        tr.querySelectorAll('.table-browse-cell').forEach(function(td) {
            const col = td.dataset.column;
            if (!col) return;
            const m = meta[col];
            if (m && m.is_pk) {
                pk[col] = row[col];
                return;
            }
            const input = td.querySelector('input');
            if (!input) return;
            let val = input.type === 'checkbox' ? (input.checked ? 't' : 'f') : input.value;
            if (val === '' && (m && m.notnull)) return;
            updates[col] = val === '' ? null : val;
        });
        return { pk, updates };
    }

    function collectValidationErrors(tr) {
        const errors = {};
        tr.querySelectorAll('.table-browse-cell').forEach(function(td) {
            const col = td.dataset.column;
            const input = td.querySelector('input');
            if (!input || !columnMeta[col] || columnMeta[col].is_pk) return;
            const val = input.type === 'checkbox' ? (input.checked ? 't' : 'f') : input.value;
            const msg = validateCell(val, columnMeta[col]);
            if (msg) errors[col] = msg;
        });
        return errors;
    }

    function saveRow(tr) {
        const errs = collectValidationErrors(tr);
        tr.querySelectorAll('.table-browse-cell-error').forEach(function(el) {
            el.textContent = '';
            el.classList.add('hidden');
        });
        let hasError = false;
        Object.keys(errs).forEach(function(col) {
            const td = tr.querySelector('[data-column="' + col + '"]');
            if (td) {
                const errEl = td.querySelector('.table-browse-cell-error');
                if (errEl) {
                    errEl.textContent = errs[col];
                    errEl.classList.remove('hidden');
                    hasError = true;
                }
            }
        });
        if (hasError) return;

        const { pk, updates } = getEditedValues(tr);
        if (Object.keys(updates).length === 0) {
            cancelEdit(tr);
            return;
        }

        const body = JSON.stringify({ pk, updates });
        const saveBtn = tr.querySelector('.table-browse-save');
        if (saveBtn) saveBtn.disabled = true;

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        })
        .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
        .then(function({ ok, json }) {
            if (saveBtn) saveBtn.disabled = false;
            if (ok && json.success) {
                Object.keys(updates).forEach(function(col) {
                    const row = JSON.parse(tr.dataset.row || '{}');
                    row[col] = updates[col];
                    tr.dataset.row = JSON.stringify(row);
                });
                cancelEdit(tr);
            } else {
                const msg = json.error || 'Update failed';
                const actionsCell = tr.querySelector('td:last-child');
                if (actionsCell) {
                    let errEl = actionsCell.querySelector('.table-browse-row-error');
                    if (!errEl) {
                        errEl = document.createElement('div');
                        errEl.className = 'table-browse-row-error text-term-danger text-xs mt-1';
                        actionsCell.querySelector('.table-browse-actions').appendChild(errEl);
                    }
                    errEl.textContent = msg;
                    errEl.classList.remove('hidden');
                }
            }
        })
        .catch(function(e) {
            if (saveBtn) saveBtn.disabled = false;
            const actionsCell = tr.querySelector('td:last-child');
            if (actionsCell) {
                let errEl = actionsCell.querySelector('.table-browse-row-error');
                if (!errEl) {
                    errEl = document.createElement('div');
                    errEl.className = 'table-browse-row-error text-term-danger text-xs mt-1';
                    actionsCell.querySelector('.table-browse-actions').appendChild(errEl);
                }
                errEl.textContent = e.message || 'Network error';
                errEl.classList.remove('hidden');
            }
        });
    }

    function cancelEdit(tr) {
        const row = JSON.parse(tr.dataset.row || '{}');
        tr.classList.remove('editing');
        tr.querySelectorAll('.table-browse-cell').forEach(function(td) {
            const col = td.dataset.column;
            const meta = columnMeta[col];
            const isPk = meta && meta.is_pk;
            const val = row[col];
            td.classList.remove('bg-term-panel/50');
            td.innerHTML = '';
            if (val === null || val === undefined) {
                td.innerHTML = '<span class="text-term-muted">NULL</span>';
            } else {
                td.textContent = val;
            }
        });
        const actionsCell = tr.querySelector('td:last-child');
        if (actionsCell) {
            actionsCell.classList.add('p-0');
            actionsCell.classList.remove('bg-term-panel/50', 'align-middle');
            actionsCell.innerHTML = '';
        }
    }
})();
</script>
@endif
@endsection
