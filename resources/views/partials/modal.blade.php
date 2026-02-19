{{--
  Reusable confirmation/content modal.
  Usage: @include('partials.modal', ['id' => 'my-modal', 'title' => 'Confirm', 'slot' => '...', 'confirmLabel' => 'Confirm', 'cancelLabel' => 'Cancel'])
  Pass $body for HTML content, or $slot for plain text.
--}}
<div id="{{ $id ?? 'confirm-modal' }}" class="modal-backdrop fixed inset-0 z-50 hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="{{ ($id ?? 'confirm-modal') }}-title">
    <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-close></div>
    <div class="modal-panel relative z-10 w-full max-w-lg rounded-lg border border-term-border bg-term-panel shadow-xl">
        <div class="border-b border-term-border px-5 py-4">
            <h2 id="{{ ($id ?? 'confirm-modal') }}-title" class="font-mono text-lg font-semibold text-term-text">{{ $title ?? 'Confirm' }}</h2>
        </div>
        <div class="modal-body px-5 py-4 font-mono text-sm text-term-text-dim">
            {!! $body ?? $slot ?? '' !!}
        </div>
        <div class="modal-footer flex justify-end gap-2 border-t border-term-border px-5 py-4">
            <button type="button" class="modal-cancel btn-secondary font-mono text-sm inline-flex items-center gap-1.5" data-modal-close><i data-lucide="x" class="w-4 h-4 shrink-0"></i>{{ $cancelLabel ?? 'Cancel' }}</button>
            <button type="button" class="modal-confirm btn-primary font-mono text-sm inline-flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 shrink-0"></i>{{ $confirmLabel ?? 'Confirm' }}</button>
        </div>
    </div>
</div>
