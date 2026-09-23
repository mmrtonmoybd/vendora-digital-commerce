<div x-data x-show="$store.toast.on" x-cloak x-transition
  class="fixed bottom-5 right-5 z-[80] flex items-center gap-2 px-4 py-3 rounded-xl bg-ink text-white text-sm shadow-xl">
  <span class="grid place-items-center w-5 h-5 rounded-full bg-emerald-500"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M20 6 9 17l-5-5" />
    </svg></span><span x-text="$store.toast.msg"></span>
</div>
