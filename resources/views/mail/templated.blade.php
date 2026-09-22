{{-- Every transactional email. The body comes from an email_templates row, so support can
     reword it without a deploy; the frame is Laravel's own mail theme, so nothing here
     competes with the storefront design. --}}
<x-mail::message>
{!! \Illuminate\Support\Str::markdown($bodyMarkdown) !!}

@if ($actionUrl && $actionText)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endif

Thanks,<br>
{{ $siteName }}
</x-mail::message>
