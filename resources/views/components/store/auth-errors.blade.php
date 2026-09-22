{{-- The template's error block, shown only when there is something to say. --}}
@if ($errors->any())
<div class="mt-6 p-3 rounded-xl bg-red-50 text-red-700 text-sm font-semibold ring-1 ring-red-200">{{ $errors->first() }}</div>
@endif
@if (session('status'))
<div class="mt-6 p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold ring-1 ring-emerald-200">{{ session('status') }}</div>
@endif
