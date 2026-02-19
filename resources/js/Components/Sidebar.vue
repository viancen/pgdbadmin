<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Table2,
    Play,
    LogOut,
    Columns2,
} from 'lucide-vue-next';

const page = usePage();
const auth = page.props.auth;

function switchDb(e) {
    const database = e.target.value;
    if (!database) return;
    router.post(route('switch-db'), { database }, { preserveState: true });
}

function logout() {
    router.post(route('logout'));
}

const tablesBySchema = () => {
    const tables = auth?.sidebarTables ?? [];
    const bySchema = {};
    for (const t of tables) {
        if (!bySchema[t.schema]) bySchema[t.schema] = [];
        bySchema[t.schema].push(t);
    }
    return bySchema;
};
</script>

<template>
    <aside class="app-sidebar flex h-screen w-[260px] shrink-0 flex-col border-r border-term-border bg-term-panel">
        <div class="border-b border-term-border px-4 py-4">
            <Link :href="route('home')" class="font-mono text-sm font-medium text-term-text hover:text-term-accent inline-flex items-center gap-2">
                <img src="/pgdb.png" alt="pgdbadmin" class="h-6 w-6 shrink-0 object-contain" />
                <span class="text-term-accent">pg</span>dbadmin
            </Link>
        </div>
        <div class="flex-1 overflow-y-auto">
            <div class="border-b border-term-border px-4 py-3">
                <p class="font-mono text-[11px] uppercase tracking-wider text-term-text-dim inline-flex items-center gap-1.5">
                    <LayoutGrid class="w-3.5 h-3.5" />
                    Database Explorer
                </p>
                <p class="mt-1 truncate font-mono text-xs text-term-muted" :title="`${auth?.sidebarUser ?? ''}@${auth?.sidebarHost ?? ''}`">
                    {{ auth?.sidebarUser ?? '' }}@{{ auth?.sidebarHost ?? '' }}
                </p>
            </div>
            <div class="border-b border-term-border px-4 py-3">
                <span class="font-mono text-[11px] text-term-text-dim">database</span>
                <div class="mt-1">
                    <select
                        name="database"
                        class="input max-w-full border-term-border py-1.5 text-xs"
                        :value="auth?.sidebarCurrentDb ?? ''"
                        @change="switchDb"
                    >
                        <option v-for="db in (auth?.sidebarDatabases ?? [])" :key="db" :value="db">{{ db }}</option>
                    </select>
                </div>
            </div>
            <div class="px-2 py-2">
                <div class="flex items-center justify-between px-1 py-1">
                    <span class="font-mono text-[11px] uppercase tracking-wider text-term-text-dim inline-flex items-center gap-1.5">
                        <Table2 class="w-3.5 h-3.5" />
                        tables
                    </span>
                    <span class="font-mono text-[10px] text-term-muted">{{ (auth?.sidebarTables ?? []).length }}</span>
                </div>
                <nav class="mt-1 space-y-0.5 font-mono text-xs">
                    <template v-for="(items, schema) in tablesBySchema()" :key="schema">
                        <div class="py-0.5">
                            <p class="truncate px-2 py-0.5 text-term-amber">{{ schema }}</p>
                            <div
                                v-for="t in items"
                                :key="`${t.schema}.${t.name}`"
                                class="flex items-center gap-1 rounded px-2 py-0.5 hover:bg-term-bg/80"
                            >
                                <Link
                                    :href="route('table.browse', { schema: t.schema, table: t.name })"
                                    class="min-w-0 flex-1 truncate text-term-text-dim hover:text-term-accent hover:underline"
                                    :title="`${t.schema}.${t.name}`"
                                >
                                    {{ t.name }}
                                </Link>
                                <Link
                                    :href="route('table.structure', { schema: t.schema, table: t.name })"
                                    class="shrink-0 text-term-muted hover:text-term-amber inline-flex"
                                    title="structure"
                                >
                                    <Columns2 class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </template>
                    <p v-if="!(auth?.sidebarTables ?? []).length" class="px-2 py-2 text-term-muted">no tables</p>
                </nav>
            </div>
        </div>
        <div class="border-t border-term-border p-3">
            <Link
                :href="route('query.index')"
                class="btn-primary mb-2 w-full py-1.5 text-center text-xs font-mono inline-flex items-center justify-center gap-1.5"
            >
                <Play class="w-3.5 h-3.5 shrink-0" />
                Run SQL
            </Link>
            <button
                type="button"
                class="w-full text-left font-mono text-[11px] text-term-text-dim hover:text-term-danger inline-flex items-center gap-1.5"
                @click="logout"
            >
                <LogOut class="w-3.5 h-3.5 shrink-0" />
                logout
            </button>
        </div>
    </aside>
</template>
