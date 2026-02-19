@extends('layout')

@section('content')
<div class="p-6">
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

    {{-- Columns --}}
    <div class="card overflow-hidden mb-6">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-mono font-medium text-term-text">columns</h2>
            <button type="button" class="btn-primary text-sm font-mono" data-modal="structure-add-modal">+ add column</button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>column</th>
                        <th>type</th>
                        <th>null</th>
                        <th>default</th>
                        <th class="text-term-text-dim">actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($columns ?? [] as $col)
                    <tr>
                        <td class="font-mono font-medium text-term-text">{{ $col->name }}</td>
                        <td><span class="badge-type">{{ $col->type }}</span></td>
                        <td class="font-mono text-term-text-dim">{{ $col->notnull ? 'NO' : 'YES' }}</td>
                        <td class="font-mono text-term-text-dim">{{ $col->default ?? '—' }}</td>
                        <td class="font-mono text-xs">
                            <button type="button" class="structure-edit-col btn-ghost text-term-accent py-0.5 px-1 text-xs" data-column="{{ $col->name }}" data-type="{{ e($col->type) }}" data-notnull="{{ $col->notnull ? '1' : '0' }}" data-default="{{ e($col->default ?? '') }}">edit</button>
                            <button type="button" class="structure-drop-col btn-ghost text-term-danger py-0.5 px-1 text-xs" data-column="{{ $col->name }}">drop</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Constraints --}}
    @if(!empty($constraints))
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="font-mono font-medium text-term-text">constraints</h2>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>name</th>
                        <th>type</th>
                        <th>definition</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($constraints as $c)
                    <tr>
                        <td class="font-mono font-medium text-term-text">{{ $c->name }}</td>
                        <td><span class="badge-type">{{ $c->type }}</span></td>
                        <td class="font-mono text-term-text-dim text-xs">{{ $c->definition ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- Confirm DDL modal: shows SQL and has hidden form to execute --}}
<form id="form-execute-ddl" action="{{ route('table.column.ddl', ['schema' => $schema, 'table' => $tableName]) }}" method="post" class="hidden">
    @csrf
    <input type="hidden" name="action" value="execute" />
    <input type="hidden" name="ddl" id="ddl-value" value="" />
</form>
@include('partials.modal', [
    'id' => 'structure-confirm-modal',
    'title' => 'Confirm change',
    'body' => '<p class="mb-2">The following SQL will be executed:</p><pre id="confirm-ddl-sql" class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap break-all"></pre>',
    'confirmLabel' => 'Execute',
    'cancelLabel' => 'Cancel',
])
{{-- We don't use data-submit on open; we set it when opening this modal and set form values via JS --}}

{{-- Edit column modal --}}
@include('partials.modal', [
    'id' => 'structure-edit-modal',
    'title' => 'Edit column',
    'body' => '
    <form id="form-edit-column" class="space-y-3">
        <div>
            <label class="block text-term-text-dim text-xs mb-1">column</label>
            <input type="text" name="column" id="edit-column" class="input font-mono w-full" readonly />
        </div>
        <div>
            <label class="block text-term-text-dim text-xs mb-1">type</label>
            <input type="text" name="type" id="edit-type" class="input font-mono w-full" placeholder="e.g. integer, varchar(255)" />
        </div>
        <div>
            <label class="flex items-center gap-2"><input type="checkbox" name="notnull" id="edit-notnull" value="1" /> NOT NULL</label>
        </div>
        <div>
            <label class="block text-term-text-dim text-xs mb-1">default</label>
            <input type="text" name="default" id="edit-default" class="input font-mono w-full" placeholder="e.g. 0 or \'value\'" />
        </div>
    </form>',
    'confirmLabel' => 'Preview SQL',
    'cancelLabel' => 'Cancel',
])

{{-- Add column modal --}}
@include('partials.modal', [
    'id' => 'structure-add-modal',
    'title' => 'Add column',
    'body' => '
    <form id="form-add-column" class="space-y-3">
        <div>
            <label class="block text-term-text-dim text-xs mb-1">name</label>
            <input type="text" name="name" id="add-name" class="input font-mono w-full" required placeholder="column_name" />
        </div>
        <div>
            <label class="block text-term-text-dim text-xs mb-1">type</label>
            <input type="text" name="type" id="add-type" class="input font-mono w-full" required placeholder="e.g. integer, varchar(255)" />
        </div>
        <div>
            <label class="flex items-center gap-2"><input type="checkbox" name="notnull" id="add-notnull" value="1" /> NOT NULL</label>
        </div>
        <div>
            <label class="block text-term-text-dim text-xs mb-1">default</label>
            <input type="text" name="default" id="add-default" class="input font-mono w-full" placeholder="e.g. 0 or \'value\'" />
        </div>
    </form>',
    'confirmLabel' => 'Preview SQL',
    'cancelLabel' => 'Cancel',
])

{{-- Drop column modal (body filled by JS) --}}
@include('partials.modal', [
    'id' => 'structure-drop-modal',
    'title' => 'Drop column',
    'body' => '<p class="mb-2">This will permanently remove the column and its data.</p><pre id="drop-ddl-sql" class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap text-term-danger"></pre>',
    'confirmLabel' => 'Drop column',
    'cancelLabel' => 'Cancel',
])

<script>
(function() {
    var columnDdlUrl = @json(route('table.column.ddl', ['schema' => $schema, 'table' => $tableName]));
    var token = @json(csrf_token());

    function openConfirmModal(sql) {
        document.getElementById('confirm-ddl-sql').textContent = sql;
        document.getElementById('ddl-value').value = sql;
        document.getElementById('structure-confirm-modal').classList.remove('hidden');
    }

    document.getElementById('structure-confirm-modal').querySelector('.modal-confirm').addEventListener('click', function() {
        var modal = document.getElementById('structure-confirm-modal');
        var sql = document.getElementById('ddl-value').value;
        if (!sql) return;
        var formData = new FormData();
        formData.append('_token', token);
        formData.append('action', 'execute');
        formData.append('ddl', sql);
        fetch(columnDdlUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
            .then(function(res) {
                modal.classList.add('hidden');
                if (res.ok && res.json.success) location.reload();
                else alert(res.json.error || 'Failed');
            })
            .catch(function() { modal.classList.add('hidden'); alert('Request failed'); });
    });

    document.querySelectorAll('.structure-edit-col').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit-column').value = this.dataset.column;
            document.getElementById('edit-type').value = this.dataset.type || '';
            document.getElementById('edit-notnull').checked = this.dataset.notnull === '1';
            document.getElementById('edit-default').value = this.dataset.default || '';
            document.getElementById('structure-edit-modal').classList.remove('hidden');
        });
    });

    document.getElementById('structure-edit-modal').querySelector('.modal-confirm').addEventListener('click', function() {
        var form = document.getElementById('form-edit-column');
        var payload = {
            _token: token,
            action: 'preview',
            op: 'alter',
            column: document.getElementById('edit-column').value,
            type: document.getElementById('edit-type').value.trim() || null,
            notnull: document.getElementById('edit-notnull').checked ? '1' : '0',
            default: document.getElementById('edit-default').value.trim() || null
        };
        fetch(columnDdlUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(payload) })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                document.getElementById('structure-edit-modal').classList.add('hidden');
                openConfirmModal(data.sql);
            })
            .catch(function(e) { alert(e.message || 'Request failed'); });
    });

    document.getElementById('structure-add-modal').querySelector('.modal-confirm').addEventListener('click', function() {
        var name = document.getElementById('add-name').value.trim();
        var type = document.getElementById('add-type').value.trim();
        if (!name || !type) { alert('Name and type are required'); return; }
        var payload = {
            _token: token,
            action: 'preview',
            op: 'add',
            name: name,
            type: type,
            notnull: document.getElementById('add-notnull').checked ? '1' : '0',
            default: document.getElementById('add-default').value.trim() || null
        };
        fetch(columnDdlUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(payload) })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                document.getElementById('structure-add-modal').classList.add('hidden');
                openConfirmModal(data.sql);
            })
            .catch(function(e) { alert(e.message || 'Request failed'); });
    });

    document.querySelectorAll('.structure-drop-col').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var col = this.dataset.column;
            var sql = 'ALTER TABLE "' + @json($schema).replace(/"/g, '""') + '"."' + @json($tableName).replace(/"/g, '""') + '" DROP COLUMN "' + col.replace(/"/g, '""') + '"';
            document.getElementById('drop-ddl-sql').textContent = sql;
            document.getElementById('ddl-value').value = sql;
            document.getElementById('structure-drop-modal').classList.remove('hidden');
            document.getElementById('structure-drop-modal').dataset.submit = 'form-execute-ddl';
        });
    });

    document.getElementById('structure-drop-modal').querySelector('.modal-confirm').addEventListener('click', function() {
        var sql = document.getElementById('ddl-value').value;
        if (!sql) return;
        var formData = new FormData();
        formData.append('_token', token);
        formData.append('action', 'execute');
        formData.append('ddl', sql);
        fetch(columnDdlUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, json: j }; }); })
            .then(function(res) {
                document.getElementById('structure-drop-modal').classList.add('hidden');
                if (res.ok && res.json.success) location.reload();
                else alert(res.json.error || 'Failed');
            })
            .catch(function() { document.getElementById('structure-drop-modal').classList.add('hidden'); alert('Request failed'); });
    });
})();
</script>
@endsection
