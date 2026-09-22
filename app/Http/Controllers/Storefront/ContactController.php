<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ContactMessageStatus;
use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ContactController extends Controller
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function index(): View
    {
        $page = Page::active()->where('template', PageTemplate::Contact->value)->first();

        return view('pages.contact', ['page' => $page]);
    }

    public function store(Request $request): RedirectResponse
    {
        // A hidden field a person never fills in. Bots fill everything.
        if ($request->filled('website')) {
            return back()->with('status', 'Thanks! We will get back to you shortly.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $message = ContactMessage::create([
            ...$data,
            'ip' => $request->ip(),
            'status' => ContactMessageStatus::New,
        ]);

        $this->notifications->toAdmins(
            'New contact message',
            "{$message->name}: {$message->subject}",
            url('/admin/contact-messages'),
            'heroicon-o-envelope',
        );

        return back()->with('status', 'Thanks! We will get back to you shortly.');
    }
}
