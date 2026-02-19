<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    total: { type: Number, required: true },
    limit: { type: Number, required: true },
    offset: { type: Number, required: true },
    baseUrl: { type: String, default: '' },
});

const page = Math.floor(props.offset / props.limit) + 1;
const totalPages = props.total > 0 ? Math.ceil(props.total / props.limit) : 1;
const sep = props.baseUrl && props.baseUrl.includes('?') ? '&' : '?';
</script>

<template>
    <div v-if="totalPages > 1 && baseUrl" class="flex items-center gap-2 font-mono text-xs">
        <Link
            v-if="page > 1"
            :href="`${baseUrl}${sep}limit=${limit}&offset=${Math.max(0, offset - limit)}`"
            class="btn-secondary text-xs inline-flex items-center gap-1"
        >
            <ChevronLeft class="w-3.5 h-3.5 shrink-0" />
            prev
        </Link>
        <span class="text-term-text-dim">page {{ page }} / {{ totalPages }}</span>
        <Link
            v-if="page < totalPages"
            :href="`${baseUrl}${sep}limit=${limit}&offset=${offset + limit}`"
            class="btn-secondary text-xs inline-flex items-center gap-1"
        >
            next
            <ChevronRight class="w-3.5 h-3.5 shrink-0" />
        </Link>
    </div>
</template>
