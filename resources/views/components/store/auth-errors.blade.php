{{-- What the form has to say back. Errors name what went wrong; a status says what
     happened. Neither appears when there is nothing to report. --}}
@if ($errors->any())
  <div class="note note-bad mb-6 flex items-start gap-2 font-semibold">
    <x-store.ui name="alert" class="w-4 h-4 mt-0.5 shrink-0" />{{ $errors->first() }}
  </div>
@endif
@if (session('status'))
  <div class="note note-ok mb-6 flex items-start gap-2 font-semibold">
    <x-store.ui name="check-circle" class="w-4 h-4 mt-0.5 shrink-0" />{{ session('status') }}
  </div>
@endif
