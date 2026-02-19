<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Columns2, Table2, Code, PlusCircle, Pencil, Trash2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    schema: String,
    tableName: String,
    columns: Array,
    constraints: Array,
});

const columnDdlUrl = () => route('table.column.ddl', { schema: props.schema, table: props.tableName });

const confirmModalOpen = ref(false);
const confirmSql = ref('');
const confirmExecute = ref(null); // () => Promise

const editModalOpen = ref(false);
const addModalOpen = ref(false);
const dropModalOpen = ref(false);

const editForm = ref({ column: '', type: '', notnull: false, default: '' });
const addForm = ref({ name: '', type: '', notnull: false, default: '' });
const dropColumn = ref('');
const dropSql = ref('');

function openConfirmModal(sql, executeFn) {
    confirmSql.value = sql;
    confirmExecute.value = executeFn;
    confirmModalOpen.value = true;
}

function runConfirmExecute() {
    if (!confirmExecute.value) return;
    confirmExecute.value()
        .then(() => {
            confirmModalOpen.value = false;
            window.location.reload();
        })
        .catch((err) => {
            alert(err.response?.data?.error || err.message || 'Failed');
        });
}

function openEdit(col) {
    editForm.value = {
        column: col.name,
        type: col.type || '',
        notnull: !!col.notnull,
        default: col.default ?? '',
    };
    editModalOpen.value = true;
}

function submitEditPreview() {
    const payload = {
        action: 'preview',
        op: 'alter',
        column: editForm.value.column,
        type: editForm.value.type.trim() || null,
        notnull: editForm.value.notnull ? '1' : '0',
        default: editForm.value.default.trim() || null,
    };
    axios.post(columnDdlUrl(), payload)
        .then((res) => {
            if (res.data.error) {
                alert(res.data.error);
                return;
            }
            editModalOpen.value = false;
            openConfirmModal(res.data.sql, () => axios.post(columnDdlUrl(), { action: 'execute', ddl: confirmSql.value }));
        })
        .catch((e) => alert(e.response?.data?.error || e.message || 'Request failed'));
}

function openAdd() {
    addForm.value = { name: '', type: '', notnull: false, default: '' };
    addModalOpen.value = true;
}

function submitAddPreview() {
    const name = addForm.value.name.trim();
    const type = addForm.value.type.trim();
    if (!name || !type) {
        alert('Name and type are required');
        return;
    }
    const payload = {
        action: 'preview',
        op: 'add',
        name,
        type,
        notnull: addForm.value.notnull ? '1' : '0',
        default: addForm.value.default.trim() || null,
    };
    axios.post(columnDdlUrl(), payload)
        .then((res) => {
            if (res.data.error) {
                alert(res.data.error);
                return;
            }
            addModalOpen.value = false;
            openConfirmModal(res.data.sql, () => axios.post(columnDdlUrl(), { action: 'execute', ddl: confirmSql.value }));
        })
        .catch((e) => alert(e.response?.data?.error || e.message || 'Request failed'));
}

function openDrop(col) {
    const q = (s) => '"' + String(s).replace(/"/g, '""') + '"';
    dropSql.value = `ALTER TABLE ${q(props.schema)}.${q(props.tableName)} DROP COLUMN ${q(col)}`;
    dropColumn.value = col;
    dropModalOpen.value = true;
}

function submitDrop() {
    openConfirmModal(dropSql.value, () => axios.post(columnDdlUrl(), { action: 'execute', ddl: confirmSql.value }));
    dropModalOpen.value = false;
}
</script>

<template>
    <AppLayout :title="`Structure ${schema}.${tableName}`">
        <div class="p-6">
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <Link :href="route('home')" class="btn-ghost text-sm font-mono inline-flex items-center gap-1.5">
                    <ArrowLeft class="w-4 h-4 shrink-0" />
                    database
                </Link>
                <div>
                    <p class="font-mono text-xs text-term-text-dim">structure</p>
                    <h1 class="font-mono text-xl font-semibold text-term-text inline-flex items-center gap-2">
                        <Columns2 class="w-5 h-5 text-term-accent shrink-0" />
                        <span class="badge-schema">{{ schema }}</span>.<span class="text-term-accent">{{ tableName }}</span>
                    </h1>
                </div>
                <Link :href="route('table.browse', { schema, table: tableName })" class="btn-secondary text-sm font-mono inline-flex items-center gap-1.5">
                    <Table2 class="w-4 h-4 shrink-0" />
                    browse
                </Link>
                <Link :href="route('query.index', { table: schema + '.' + tableName })" class="btn-primary text-sm font-mono inline-flex items-center gap-1.5">
                    <Code class="w-4 h-4 shrink-0" />
                    sql
                </Link>
            </div>

            <div class="card overflow-hidden mb-6">
                <div class="card-header flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-mono font-medium text-term-text">columns</h2>
                    <button type="button" class="btn-primary text-sm font-mono inline-flex items-center gap-1.5" @click="openAdd">
                        <PlusCircle class="w-4 h-4 shrink-0" />
                        add column
                    </button>
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
                            <tr v-for="col in columns" :key="col.name">
                                <td class="font-mono font-medium text-term-text">{{ col.name }}</td>
                                <td><span class="badge-type">{{ col.type }}</span></td>
                                <td class="font-mono text-term-text-dim">{{ col.notnull ? 'NO' : 'YES' }}</td>
                                <td class="font-mono text-term-text-dim">{{ col.default ?? '—' }}</td>
                                <td class="font-mono text-xs">
                                    <button type="button" class="structure-edit-col btn-ghost text-term-accent py-0.5 px-1 text-xs inline-flex items-center gap-1" @click="openEdit(col)">
                                        <Pencil class="w-3.5 h-3.5 shrink-0" />
                                        edit
                                    </button>
                                    <button type="button" class="structure-drop-col btn-ghost text-term-danger py-0.5 px-1 text-xs inline-flex items-center gap-1" @click="openDrop(col.name)">
                                        <Trash2 class="w-3.5 h-3.5 shrink-0" />
                                        drop
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="constraints && constraints.length" class="card overflow-hidden">
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
                            <tr v-for="c in constraints" :key="c.name">
                                <td class="font-mono font-medium text-term-text">{{ c.name }}</td>
                                <td><span class="badge-type">{{ c.type }}</span></td>
                                <td class="font-mono text-term-text-dim text-xs">{{ c.definition ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal id="structure-confirm-modal" title="Confirm change" :show="confirmModalOpen" confirm-label="Execute" cancel-label="Cancel" @close="confirmModalOpen = false" @confirm="runConfirmExecute">
            <p class="mb-2">The following SQL will be executed:</p>
            <pre class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap break-all">{{ confirmSql }}</pre>
        </Modal>

        <Modal id="structure-edit-modal" title="Edit column" :show="editModalOpen" confirm-label="Preview SQL" cancel-label="Cancel" @close="editModalOpen = false" @confirm="submitEditPreview">
            <div class="space-y-3">
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">column</label>
                    <input v-model="editForm.column" type="text" class="input font-mono w-full" readonly />
                </div>
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">type</label>
                    <input v-model="editForm.type" type="text" class="input font-mono w-full" placeholder="e.g. integer, varchar(255)" />
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input v-model="editForm.notnull" type="checkbox" value="1" />
                        NOT NULL
                    </label>
                </div>
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">default</label>
                    <input v-model="editForm.default" type="text" class="input font-mono w-full" placeholder="e.g. 0 or 'value'" />
                </div>
            </div>
        </Modal>

        <Modal id="structure-add-modal" title="Add column" :show="addModalOpen" confirm-label="Preview SQL" cancel-label="Cancel" @close="addModalOpen = false" @confirm="submitAddPreview">
            <div class="space-y-3">
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">name</label>
                    <input v-model="addForm.name" type="text" class="input font-mono w-full" required placeholder="column_name" />
                </div>
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">type</label>
                    <input v-model="addForm.type" type="text" class="input font-mono w-full" required placeholder="e.g. integer, varchar(255)" />
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input v-model="addForm.notnull" type="checkbox" value="1" />
                        NOT NULL
                    </label>
                </div>
                <div>
                    <label class="block text-term-text-dim text-xs mb-1">default</label>
                    <input v-model="addForm.default" type="text" class="input font-mono w-full" placeholder="e.g. 0 or 'value'" />
                </div>
            </div>
        </Modal>

        <Modal id="structure-drop-modal" title="Drop column" :show="dropModalOpen" confirm-label="Drop column" cancel-label="Cancel" @close="dropModalOpen = false" @confirm="submitDrop">
            <p class="mb-2">This will permanently remove the column and its data.</p>
            <pre class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap text-term-danger">{{ dropSql }}</pre>
        </Modal>
    </AppLayout>
</template>
