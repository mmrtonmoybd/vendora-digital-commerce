{{-- The exception message is deliberately not shown: it is written for developers, and on
     a public page it leaks internals. Laravel's debug page still appears when APP_DEBUG
     is on. --}}
@include('errors.layout', [
    'code' => 500,
    'title' => __('errors.server_error.title'),
    'message' => __('errors.server_error.message'),
])
