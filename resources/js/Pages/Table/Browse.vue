<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Table2, Columns2, Code, Play, Trash2, X, Save, ExternalLink, Link2, ArrowUp, ArrowDown } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    schema: String,
    tableName: String,
    rows: Array,
    fields: Array,
    total: Number,
    limit: Number,
    offset: Number,
    sql: [String, null],
    defaultSql: String,
    columns: Array,
    primaryKey: Array,
    sort: [String, null],
    dir: String,
});

const columnMeta = computed(() => {
    const meta = {};
    for (const c of props.columns || []) {
        meta[c.name] = {
            type: c.type ?? '',
            notnull: !!c.notnull,
            is_pk: (props.primaryKey || []).includes(c.name),
        };
    }
    return meta;
});

const columnLinkedTables = computed(() => {
    const out = {};
    for (const c of props.columns || []) {
        if (c.name && c.name.endsWith('_id')) {
            out[c.name] = c.name.slice(0, -3) + 's'; // plural
        }
    }
    return out;
});

const canEditRows = computed(() => {
    const hasPk = (props.primaryKey || []).length > 0;
    const defaultSqlTrim = (props.defaultSql || '').trim();
    const sqlTrim = (props.sql || '').trim();
    return hasPk && (sqlTrim === '' || sqlTrim === defaultSqlTrim);
});

const baseParams = computed(() => {
    const p = { limit: props.limit };
    if (props.sql) p.sql = props.sql;
    return p;
});

const baseUrl = computed(() => {
    const params = { ...baseParams.value };
    if (props.sort) params.sort = props.sort;
    if (props.dir) params.dir = props.dir;
    const q = new URLSearchParams(params).toString();
    return route('table.browse', { schema: props.schema, table: props.tableName }) + (q ? '?' + q : '');
});

const queryForm = useForm({
    sql: props.sql ?? props.defaultSql ?? '',
    limit: props.limit,
});

function runQuery() {
    queryForm.get(route('table.browse', { schema: props.schema, table: props.tableName }), {
        preserveState: false,
    });
}

function sortUrl(fieldName) {
    const isSortCol = props.sort === fieldName;
    const nextDir = isSortCol && props.dir === 'DESC' ? 'ASC' : 'DESC';
    const params = { ...baseParams.value, sort: fieldName, dir: nextDir, offset: 0 };
    return route('table.browse', { schema: props.schema, table: props.tableName }) + '?' + new URLSearchParams(params).toString();
}

// Row edit modal
const editModalOpen = ref(false);
const editingRow = ref(null);
const editForm = ref({});
const editErrors = ref({});
const saveError = ref('');

function linkedRecordUrl(col, value) {
    const linked = columnLinkedTables.value[col];
    if (!linked || value == null) return null;
    return route('table.browse', { schema: props.schema, table: linked }) + '?id=' + encodeURIComponent(String(value));
}

function openEditRow(row) {
    if (!canEditRows.value) return;
    editingRow.value = { ...row };
    editForm.value = {};
    for (const f of props.fields || []) {
        const meta = columnMeta.value[f.name];
        if (meta && meta.is_pk) continue;
        const v = row[f.name];
        if (meta && getInputType(meta) === 'checkbox') {
            editForm.value[f.name] = [true, 't', '1', 'yes'].includes(v);
        } else {
            editForm.value[f.name] = v === null || v === undefined ? '' : v;
        }
    }
    editErrors.value = {};
    saveError.value = '';
    editModalOpen.value = true;
}

function numericType(type) {
    if (!type) return false;
    return /int|serial|numeric|decimal|real|double|float|smallint|bigint/i.test(String(type));
}

function validateEditForm() {
    const errs = {};
    for (const col of Object.keys(editForm.value)) {
        const meta = columnMeta.value[col];
        if (!meta) continue;
        const val = editForm.value[col];
        if (meta.notnull && (val === '' || (typeof val === 'string' && val.trim() === ''))) errs[col] = 'Required';
        else if (numericType(meta.type) && val !== '' && isNaN(Number(val))) errs[col] = 'Must be a number';
    }
    editErrors.value = errs;
    return Object.keys(errs).length === 0;
}

function getInputType(meta) {
    if (!meta) return 'text';
    if (numericType(meta.type)) return 'number';
    if (String(meta.type).toLowerCase().includes('bool')) return 'checkbox';
    return 'text';
}

function saveEditRow() {
    if (!editingRow.value || !validateEditForm()) return;
    const pk = {};
    for (const col of (props.primaryKey || [])) {
        pk[col] = editingRow.value[col];
    }
    const updates = {};
    for (const col of Object.keys(editForm.value)) {
        if ((columnMeta.value[col] || {}).is_pk) continue;
        const val = editForm.value[col];
        const meta = columnMeta.value[col];
        const isCheckbox = meta && getInputType(meta) === 'checkbox';
        updates[col] = isCheckbox ? (val ? 't' : 'f') : (val === '' ? null : val);
    }
    if (Object.keys(updates).length === 0) {
        editModalOpen.value = false;
        return;
    }
    axios.post(route('table.row.update', { schema: props.schema, table: props.tableName }), { pk, updates })
        .then((res) => {
            if (res.data.success) {
                Object.assign(editingRow.value, updates);
                editModalOpen.value = false;
                router.reload();
            } else {
                saveError.value = res.data.error || 'Update failed';
            }
        })
        .catch((err) => {
            saveError.value = err.response?.data?.error || 'Network error';
        });
}

// Context menu & delete
const contextMenu = ref({ show: false, x: 0, y: 0, row: null });
const deleteModalOpen = ref(false);
const rowToDelete = ref(null);

function onContextMenu(e, row) {
    if (!canEditRows.value) return;
    e.preventDefault();
    contextMenu.value = { show: true, x: e.clientX, y: e.clientY, row };
}

function closeContextMenu() {
    contextMenu.value.show = false;
}

function openDeleteModal() {
    rowToDelete.value = contextMenu.value.row;
    contextMenu.value.show = false;
    deleteModalOpen.value = true;
}

function confirmDelete() {
    if (!rowToDelete.value) return;
    const pk = {};
    for (const col of (props.primaryKey || [])) {
        pk[col] = rowToDelete.value[col];
    }
    axios.post(route('table.row.delete', { schema: props.schema, table: props.tableName }), { pk })
        .then((res) => {
            if (res.data.success) {
                deleteModalOpen.value = false;
                rowToDelete.value = null;
                router.reload();
            } else {
                alert(res.data.error || 'Verwijderen mislukt.');
            }
        })
        .catch(() => alert('Netwerkfout.'));
}

// Cell focus (keyboard nav)
const focusedCell = ref(null);
function setFocusedCell(td) {
    focusedCell.value = td;
}
</script>

<template>
    <AppLayout :title="`${schema}.${tableName}`">
        <div class="p-6">
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <Link :href="route('home')" class="btn-ghost text-sm font-mono inline-flex items-center gap-1.5">
                    <ArrowLeft class="w-4 h-4 shrink-0" />
                    database
                </Link>
                <div>
                    <p class="font-mono text-xs text-term-text-dim">table</p>
                    <h1 class="font-mono text-xl font-semibold text-term-text inline-flex items-center gap-2">
                        <Table2 class="w-5 h-5 text-term-accent shrink-0" />
                        <span class="badge-schema">{{ schema }}</span>.<span class="text-term-accent">{{ tableName }}</span>
                    </h1>
                </div>
                <Link :href="route('table.structure', { schema, table: tableName })" class="btn-secondary text-sm font-mono inline-flex items-center gap-1.5">
                    <Columns2 class="w-4 h-4 shrink-0" />
                    structure
                </Link>
                <Link :href="route('query.index', { table: schema + '.' + tableName })" class="btn-primary text-sm font-mono inline-flex items-center gap-1.5">
                    <Code class="w-4 h-4 shrink-0" />
                    sql
                </Link>
            </div>

            <form @submit.prevent="runQuery" class="card overflow-hidden mb-4">
                <div class="border-b border-term-border p-5">
                    <label for="table-query-sql" class="font-mono text-xs text-term-text-dim block mb-2">Query (results paginated below)</label>
                    <textarea v-model="queryForm.sql" id="table-query-sql" name="sql" rows="3" class="input font-mono text-sm" :placeholder="defaultSql" />
                </div>
                <div class="flex items-center justify-between border-term-border bg-term-panel/80 px-5 py-4 font-mono text-sm gap-4">
                    <span class="text-term-text-dim text-xs">Default: SELECT * FROM table LIMIT 100 — change and run to filter/sort.</span>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary font-mono text-sm inline-flex items-center gap-1.5">
                            <Play class="w-4 h-4 shrink-0" />
                            Run query
                        </button>
                    </div>
                </div>
            </form>

            <div class="card overflow-hidden">
                <div class="card-header flex flex-wrap items-center justify-between gap-2">
                    <span class="font-mono text-term-text-dim">{{ total }} row{{ total !== 1 ? 's' : '' }}</span>
                    <Pagination :total="total" :limit="limit" :offset="offset" :base-url="baseUrl" />
                </div>
                <div class="table-container max-h-[70vh] overflow-auto">
                    <table class="data-table" tabindex="0">
                        <thead>
                            <tr>
                                <th v-for="f in fields" :key="f.name" class="whitespace-nowrap">
                                    <a :href="sortUrl(f.name)" class="inline-flex items-center gap-1 hover:text-term-accent focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded" :class="sort === f.name ? 'text-term-accent' : 'text-term-amber'">
                                        {{ f.name }}
                                        <ArrowDown v-if="sort === f.name && dir === 'DESC'" class="w-3.5 h-3.5 text-term-text-dim shrink-0" />
                                        <ArrowUp v-else-if="sort === f.name" class="w-3.5 h-3.5 text-term-text-dim shrink-0" />
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, ri) in rows"
                                :key="ri"
                                class="table-browse-row"
                                :class="{ 'cursor-pointer': canEditRows }"
                                :title="canEditRows ? 'Double-click to edit' : undefined"
                                @dblclick="canEditRows && openEditRow(row)"
                                @contextmenu="onContextMenu($event, row)"
                            >
                                <td
                                    v-for="f in fields"
                                    :key="f.name"
                                    class="max-w-xs font-mono text-xs table-browse-cell align-middle table-browse-td"
                                    :class="{ 'cell-focused': focusedCell === `${ri}-${f.name}` }"
                                    :data-column="f.name"
                                    @click="setFocusedCell(`${ri}-${f.name}`)"
                                >
                                    <template v-if="(row[f.name] ?? null) === null">
                                        <span class="text-term-muted">NULL</span>
                                    </template>
                                    <template v-else>
                                        <span class="inline-flex items-center gap-1 truncate max-w-full">
                                            <span class="truncate">{{ row[f.name] }}</span>
                                            <a
                                                v-if="columnLinkedTables[f.name]"
                                                :href="linkedRecordUrl(f.name, row[f.name])"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="shrink-0 inline-flex text-term-accent hover:text-term-amber"
                                                title="View linked record"
                                            >
                                                <Link2 class="w-3.5 h-3.5" />
                                            </a>
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!rows.length" class="px-6 py-12 text-center font-mono text-term-text-dim">no rows.</div>
            </div>
        </div>

        <!-- Context menu -->
        <div
            v-show="contextMenu.show"
            class="fixed z-40 min-w-[10rem] rounded-lg border border-term-border bg-term-panel shadow-xl py-1 font-mono text-sm"
            :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
            @click.stop
        >
            <button type="button" class="w-full text-left px-4 py-2 text-term-danger hover:bg-term-danger/10 flex items-center gap-2" @click="openDeleteModal">
                <Trash2 class="w-4 h-4 shrink-0" />
                Verwijder record
            </button>
        </div>
        <div v-if="contextMenu.show" class="fixed inset-0 z-30" @click="closeContextMenu" @scroll="closeContextMenu" />

        <Modal
            id="row-delete-confirm-modal"
            title="Verwijder record"
            :show="deleteModalOpen"
            confirm-label="Verwijderen"
            cancel-label="Annuleren"
            @close="deleteModalOpen = false; rowToDelete = null"
            @confirm="confirmDelete"
        >
            <p>Weet je het zeker? Dit kan niet ongedaan worden gemaakt.</p>
        </Modal>

        <!-- Edit row modal -->
        <Modal
            id="row-edit-modal"
            title="Edit record"
            :show="editModalOpen"
            confirm-label="Save"
            cancel-label="Cancel"
            @close="editModalOpen = false"
            @confirm="saveEditRow"
        >
            <template v-if="editingRow">
                <div class="space-y-3">
                    <div v-for="f in (fields || [])" :key="f.name" class="space-y-1">
                        <label class="block text-term-text-dim text-xs">{{ f.name }}{{ (columnMeta[f.name] || {}).is_pk ? ' (primary key)' : '' }}</label>
                        <template v-if="(columnMeta[f.name] || {}).is_pk">
                            <div class="rounded border border-term-border bg-term-bg/50 px-3 py-2 text-term-text font-mono text-xs">
                                {{ editingRow[f.name] ?? 'NULL' }}
                            </div>
                            <a v-if="linkedRecordUrl(f.name, editingRow[f.name])" :href="linkedRecordUrl(f.name, editingRow[f.name])" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs">
                                <ExternalLink class="w-3.5 h-3.5 shrink-0" />
                                View linked record
                            </a>
                        </template>
                        <template v-else>
                            <input
                                v-if="getInputType(columnMeta[f.name]) === 'checkbox'"
                                v-model="editForm[f.name]"
                                type="checkbox"
                                class="rounded border-term-border bg-term-bg text-term-accent focus:ring-term-accent"
                            />
                            <input
                                v-else
                                v-model="editForm[f.name]"
                                :type="getInputType(columnMeta[f.name])"
                                class="input w-full py-1.5 text-xs font-mono"
                            />
                            <div v-if="editErrors[f.name]" class="text-term-danger text-xs">{{ editErrors[f.name] }}</div>
                            <a v-if="linkedRecordUrl(f.name, editingRow[f.name])" :href="linkedRecordUrl(f.name, editingRow[f.name])" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs">
                                <ExternalLink class="w-3.5 h-3.5 shrink-0" />
                                View linked record
                            </a>
                        </template>
                    </div>
                </div>
                <p v-if="saveError" class="mt-3 text-term-danger text-xs">{{ saveError }}</p>
            </template>
        </Modal>
    </AppLayout>
</template>
