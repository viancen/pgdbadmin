<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Table2, List, Columns2 } from 'lucide-vue-next';

defineProps({
    currentDb: { type: String, default: '' },
    tables: { type: Array, default: () => [] },
    error: { type: String, default: null },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between border-b border-term-border pb-5">
                <div>
                    <h1 class="font-mono text-lg font-semibold text-term-text inline-flex items-center gap-2">
                        <img src="/pgdb.png" alt="" class="h-5 w-5 shrink-0 object-contain" aria-hidden="true" />
                        <Table2 class="w-5 h-5 text-term-accent shrink-0" />
                        Tables
                    </h1>
                    <p class="mt-0.5 font-mono text-xs text-term-text-dim">database: <span class="text-term-accent">{{ currentDb }}</span></p>
                </div>
            </div>
            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="font-mono text-sm font-medium text-term-text inline-flex items-center gap-1.5">
                        <List class="w-4 h-4 shrink-0" />
                        All tables
                    </h2>
                    <p class="mt-0.5 text-xs text-term-text-dim">Click a table to browse data or view structure</p>
                </div>
                <div v-if="error" class="border-b border-term-border bg-term-danger/10 px-6 py-4 font-mono text-sm text-term-danger">{{ error }}</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>schema</th>
                                <th>table</th>
                                <th>columns</th>
                                <th class="w-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tables" :key="`${t.schema}.${t.name}`">
                                <td><span class="badge-schema">{{ t.schema }}</span></td>
                                <td>
                                    <Link :href="route('table.browse', { schema: t.schema, table: t.name })" class="font-mono font-medium text-term-accent hover:underline">{{ t.name }}</Link>
                                </td>
                                <td class="font-mono text-term-text-dim">{{ t.column_count }}</td>
                                <td class="flex gap-2">
                                    <Link :href="route('table.browse', { schema: t.schema, table: t.name })" class="btn-ghost text-xs inline-flex items-center gap-1">
                                        <Table2 class="w-3.5 h-3.5 shrink-0" />
                                        browse
                                    </Link>
                                    <Link :href="route('table.structure', { schema: t.schema, table: t.name })" class="btn-ghost text-xs inline-flex items-center gap-1">
                                        <Columns2 class="w-3.5 h-3.5 shrink-0" />
                                        structure
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!tables.length" class="px-6 py-12 text-center font-mono text-term-text-dim">no tables in this database.</div>
            </div>
        </div>
    </AppLayout>
</template>
