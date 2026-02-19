<script setup>
import { X, Check } from 'lucide-vue-next';

defineProps({
    id: { type: String, default: 'confirm-modal' },
    title: { type: String, default: 'Confirm' },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    show: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <div
        v-show="show"
        :id="id"
        class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`${id}-title`"
    >
        <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />
        <div class="modal-panel relative z-10 w-full max-w-lg rounded-lg border border-term-border bg-term-panel shadow-xl">
            <div class="border-b border-term-border px-6 py-5">
                <h2 :id="`${id}-title`" class="font-mono text-lg font-semibold text-term-text">{{ title }}</h2>
            </div>
            <div class="modal-body px-6 py-5 font-mono text-sm text-term-text-dim">
                <slot />
            </div>
            <div class="modal-footer flex justify-end gap-3 border-t border-term-border px-6 py-5">
                <button
                    type="button"
                    class="modal-cancel btn-secondary font-mono text-sm inline-flex items-center gap-1.5"
                    @click="emit('close')"
                >
                    <X class="w-4 h-4 shrink-0" />
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    class="modal-confirm btn-primary font-mono text-sm inline-flex items-center gap-1.5"
                    @click="emit('confirm')"
                >
                    <Check class="w-4 h-4 shrink-0" />
                    {{ confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
