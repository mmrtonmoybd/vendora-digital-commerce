        <a href="{{ route('cart') }}" class="relative inline-flex items-center gap-2 h-10 px-4 rounded-full bg-amber-300 text-ink font-semibold text-sm hover:bg-amber-200">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg><span class="hidden sm:inline">My Cart</span>
          @if ($count)<span class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 grid place-items-center rounded-full bg-ink text-white text-[11px] font-bold">{{ $count }}</span>@endif
        </a>
