{{-- Confirmation of something the visitor just did. It says what happened, in the same
     words the button used, and takes itself away. --}}
<div x-data x-show="$store.toast.on" x-cloak x-transition.duration.200ms
  class="fixed bottom-5 right-5 z-[80] flex items-center gap-2.5 pl-3 pr-4 py-3 rounded-[var(--radius-panel)] bg-navy-900 text-white text-sm shadow-2xl border border-white/10">
  <span class="grid place-items-center w-5 h-5 rounded-full bg-seat-600 shrink-0"><x-store.ui name="check" class="w-3 h-3" stroke-width="3" /></span>
  <span x-text="$store.toast.msg"></span>
</div>
