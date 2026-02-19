<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Code, Play, X, Layers, ExternalLink, ArrowUp, ArrowDown } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    sql: { type: String, default: '' },
    currentDb: { type: String, default: '' },
    result: { type: Object, default: null },
    error: { type: String, default: null },
    message: { type: String, default: null },
    sort: { type: String, default: null },
    dir: { type: String, default: 'ASC' },
    schemas: { type: Array, default: () => ['public'] },
});

const form = useForm({
    sql: props.sql,
    sort: props.sort ?? '',
    dir: props.dir ?? 'ASC',
    limit: props.result?.limit ?? '',
    offset: props.result?.offset ?? 0,
});

const confirmDestructiveOpen = ref(false);
const cellModalOpen = ref(false);
const cellModalColumn = ref('');
const cellModalValue = ref('');
const cellModalLink = ref(null);
const createViewOpen = ref(false);
const createViewSchema = ref('public');
const createViewName = ref('');
const createViewSql = ref('');
const createViewError = ref('');

const hasResult = computed(() => props.result && (props.result.fields || []).length > 0);

function isDestructive(sql) {
    return /^\s*(DELETE|TRUNCATE|UPDATE)\s+/i.test((sql || '').trim());
}

function submitQuery() {
    if (isDestructive(form.sql) && !form.data.confirmed) {
        confirmDestructiveOpen.value = true;
        return;
    }
    form.transform((data) => ({ ...data, confirmed: undefined })).post(route('query.execute'), {
        preserveScroll: true,
        onSuccess: () => {},
    });
}

function confirmDestructive() {
    confirmDestructiveOpen.value = false;
    form.data.confirmed = true;
    form.post(route('query.execute'), { preserveScroll: true });
}

function sortColumn(col) {
    form.sort = col;
    form.dir = (props.sort === col && props.dir === 'DESC') ? 'ASC' : 'DESC';
    form.post(route('query.execute'), { preserveScroll: true });
}

const focusedCell = ref(null);
function setFocusedCell(key) {
    focusedCell.value = key;
}

function onResultTableKeydown(e) {
    if (e.key !== 'Enter' || !focusedCell.value || !props.result?.rows || !props.result?.fields) return;
    const dash = focusedCell.value.indexOf('-');
    const rowIndex = parseInt(focusedCell.value.slice(0, dash), 10);
    const colName = focusedCell.value.slice(dash + 1);
    const row = props.result.rows[rowIndex];
    if (row && colName) openCellModal(colName, row[colName]);
}

function openCellModal(col, value) {
    cellModalColumn.value = col;
    cellModalValue.value = value === '' || value === null || value === undefined ? 'NULL' : value;
    cellModalLink.value = null;
    if (col && col.endsWith('_id') && value !== '' && value != null) {
        const linkedTable = col.slice(0, -3) + 's';
        cellModalLink.value = route('table.browse', { schema: 'public', table: linkedTable }) + '?id=' + encodeURIComponent(String(value));
    }
    cellModalOpen.value = true;
}

function openCreateView() {
    createViewSql.value = (form.sql || '').trim();
    createViewName.value = '';
    createViewError.value = '';
    createViewSchema.value = 'public';
    createViewOpen.value = true;
}

function submitCreateView() {
    const name = createViewName.value.trim();
    const sql = createViewSql.value.trim();
    createViewError.value = '';
    if (!name) {
        createViewError.value = 'View name is required.';
        return;
    }
    axios.post(route('query.create-view'), { schema: createViewSchema.value, name, sql })
        .then((res) => {
            if (res.data.success) {
                createViewOpen.value = false;
                alert(res.data.message || 'View created.');
            } else {
                createViewError.value = res.data.error || 'Failed to create view.';
            }
        })
        .catch(() => {
            createViewError.value = 'Network error.';
        });
}

onMounted(() => {
    const textarea = document.getElementById('sql');
    if (textarea) {
        textarea.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                submitQuery();
            }
        });
    }
});
</script>

<template>
    <AppLayout title="SQL Query">
        <div class="p-6">
            <div class="mb-6">
                <p class="font-mono text-xs text-term-text-dim">sql console</p>
                <h1 class="font-mono text-2xl font-semibold text-term-text inline-flex items-center gap-2">
                    <Code class="w-7 h-7 text-term-accent shrink-0" />
                    query
                </h1>
                <p class="mt-1 font-mono text-sm text-term-text-dim">SELECT, INSERT, UPDATE, DELETE — results limited to 500 rows.</p>
            </div>

            <form @submit.prevent="submitQuery" class="card overflow-hidden">
                <input v-model="form.sort" type="hidden" name="sort" />
                <input v-model="form.dir" type="hidden" name="dir" />
                <input v-model="form.limit" type="hidden" name="limit" />
                <input v-model="form.offset" type="hidden" name="offset" />
                <div class="border-b border-term-border p-5">
                    <textarea id="sql" v-model="form.sql" name="sql" rows="10" class="input font-mono text-sm" placeholder="SELECT * FROM my_table LIMIT 10;" title="Cmd+Enter to execute" />
                    <p class="mt-2 font-mono text-[11px] text-term-muted">Cmd+Enter to execute</p>
                </div>
                <div class="flex items-center justify-between border-b border-term-border bg-term-panel/80 px-5 py-4 font-mono text-sm gap-4">
                    <span class="text-term-text-dim">db: <span class="text-term-accent">{{ currentDb }}</span></span>
                    <button type="submit" class="btn-primary font-mono inline-flex items-center gap-1.5" :disabled="form.processing">
                        <Play class="w-4 h-4 shrink-0" />
                        execute
                    </button>
                </div>
            </form>

            <Modal id="query-confirm-modal" title="Confirm destructive query" :show="confirmDestructiveOpen" confirm-label="Execute" cancel-label="Cancel" @close="confirmDestructiveOpen = false" @confirm="confirmDestructive">
                <p class="mb-2">This query will modify or remove data. Please confirm:</p>
                <pre class="rounded border border-term-border bg-term-bg p-3 text-xs overflow-x-auto whitespace-pre-wrap break-all text-term-danger">{{ form.sql }}</pre>
            </Modal>

            <div id="query-cell-modal" v-show="cellModalOpen" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" @click="cellModalOpen = false" />
                <div class="modal-panel relative z-10 w-full max-w-md rounded-lg border border-term-border bg-term-panel shadow-xl">
                    <div class="border-b border-term-border px-6 py-5">
                        <h2 class="font-mono text-lg font-semibold text-term-text">Cell</h2>
                    </div>
                    <div class="px-6 py-5 font-mono text-sm text-term-text-dim space-y-2">
                        <p class="text-term-text-dim text-xs">{{ cellModalColumn }}</p>
                        <div class="rounded-lg border border-term-border bg-term-bg/50 px-4 py-3 text-term-text break-all">{{ cellModalValue }}</div>
                        <a v-if="cellModalLink" :href="cellModalLink" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-term-accent hover:text-term-amber text-xs">
                            <ExternalLink class="w-3.5 h-3.5 shrink-0" />
                            View linked record (opens in new tab)
                        </a>
                    </div>
                    <div class="border-t border-term-border px-6 py-5">
                        <button type="button" class="btn-secondary font-mono text-sm inline-flex items-center gap-1.5" @click="cellModalOpen = false">
                            <X class="w-4 h-4 shrink-0" />
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="error" class="mt-4 rounded border border-term-danger/50 bg-term-danger/10 px-4 py-3 font-mono text-sm text-term-danger">
                {{ error }}
            </div>

            <div v-if="hasResult" class="card mt-6 overflow-hidden">
                <div class="card-header flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-mono text-term-text-dim">{{ result.rowCount ?? 0 }} row{{ (result.rowCount ?? 0) !== 1 ? 's' : '' }} returned</span>
                        <span v-if="result.total > (result.rows || []).length" class="font-mono text-xs text-term-muted">(showing first {{ (result.rows || []).length }})</span>
                        <button type="button" class="btn-secondary text-sm font-mono inline-flex items-center gap-1.5" @click="openCreateView">
                            <Layers class="w-4 h-4 shrink-0" />
                            Create view
                        </button>
                    </div>
                    <Pagination
                        v-if="result.baseUrl"
                        :total="result.total ?? (result.rows || []).length"
                        :limit="result.limit ?? 100"
                        :offset="result.offset ?? 0"
                        :base-url="result.baseUrl"
                    />
                </div>
                <div class="table-container max-h-[60vh] overflow-auto">
                    <table class="data-table" tabindex="0" @keydown="onResultTableKeydown">
                        <thead>
                            <tr>
                                <th v-for="f in result.fields" :key="f.name" class="whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="query-sort-header inline-flex items-center gap-1 hover:text-term-accent focus:outline-none focus:ring-2 focus:ring-term-accent/50 rounded font-inherit"
                                        :class="sort === f.name ? 'text-term-accent' : 'text-term-amber'"
                                        @click="sortColumn(f.name)"
                                    >
                                        {{ f.name }}
                                        <ArrowDown v-if="sort === f.name && dir === 'DESC'" class="w-3.5 h-3.5 text-term-text-dim shrink-0" />
                                        <ArrowUp v-else-if="sort === f.name" class="w-3.5 h-3.5 text-term-text-dim shrink-0" />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, ri) in (result.rows || [])" :key="ri">
                                <td
                                    v-for="f in result.fields"
                                    :key="f.name"
                                    class="max-w-xs truncate font-mono text-xs query-result-td"
                                    :class="{ 'cell-focused': focusedCell === `${ri}-${f.name}` }"
                                    @click="setFocusedCell(`${ri}-${f.name}`)"
                                >
                                    <template v-if="(row[f.name] ?? null) === null">
                                        <span class="text-term-muted">NULL</span>
                                    </template>
                                    <template v-else>
                                        {{ row[f.name] }}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!(result.rows || []).length" class="px-6 py-8 text-center font-mono text-term-text-dim">no rows.</div>
            </div>

            <div v-show="createViewOpen" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" @click="createViewOpen = false" />
                <div class="modal-panel relative z-10 w-full max-w-lg max-h-[90vh] overflow-hidden rounded-lg border border-term-border bg-term-panel shadow-xl flex flex-col">
                    <div class="border-b border-term-border px-6 py-5 shrink-0">
                        <h2 class="font-mono text-lg font-semibold text-term-text">Create view</h2>
                    </div>
                    <div class="modal-body px-6 py-5 overflow-y-auto font-mono text-sm space-y-4">
                        <div>
                            <label for="create-view-schema" class="block text-term-text-dim text-xs mb-1">Schema</label>
                            <select id="create-view-schema" v-model="createViewSchema" class="input w-full py-2 text-xs">
                                <option v-for="s in schemas" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="create-view-name" class="block text-term-text-dim text-xs mb-1">View name</label>
                            <input id="create-view-name" v-model="createViewName" type="text" class="input w-full py-2 text-xs" placeholder="my_view" />
                        </div>
                        <div>
                            <label for="create-view-sql" class="block text-term-text-dim text-xs mb-1">Query (read-only)</label>
                            <textarea id="create-view-sql" v-model="createViewSql" class="input w-full font-mono text-xs resize-y min-h-[120px]" readonly />
                        </div>
                        <div v-if="createViewError" class="text-term-danger text-xs">{{ createViewError }}</div>
                    </div>
                    <div class="modal-footer flex justify-end gap-3 border-t border-term-border px-6 py-5 shrink-0">
                        <button type="button" class="btn-secondary font-mono text-sm inline-flex items-center gap-1.5" @click="createViewOpen = false">
                            <X class="w-4 h-4 shrink-0" />
                            Cancel
                        </button>
                        <button type="button" class="btn-primary font-mono text-sm inline-flex items-center gap-1.5" @click="submitCreateView">
                            <Layers class="w-4 h-4 shrink-0" />
                            Create view
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="message && !hasResult" class="mt-4 rounded border border-term-success/50 bg-term-success/10 px-4 py-3 font-mono text-sm text-term-success">
                {{ message }}
            </div>
        </div>
    </AppLayout>
</template>
