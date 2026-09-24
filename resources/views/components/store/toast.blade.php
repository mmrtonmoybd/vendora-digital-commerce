{{-- Confirmation of something the visitor just did. It says what happened, in the same
     words the button used, and takes itself away. It is cut from the same dark ground as the
     masthead and the footer, so the shop answers in its own ink. --}}
<div x-data x-show="$store.toast.on" x-cloak x-transition.duration.200ms
  class="slab fixed bottom-5 right-5 z-[80] flex items-center gap-2.5 pl-3 pr-4 py-3 text-sm border border-white/10 shadow-2xl">
  <span class="guilloche absolute inset-0"></span>
  <span class="relative grid place-items-center w-5 h-5 rounded-full bg-grant-600 shrink-0"><x-store.ui name="check" class="w-3 h-3" stroke-width="3" /></span>
  <span class="relative" x-text="$store.toast.msg"></span>
</div>
