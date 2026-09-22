<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ProblemReason;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\AccessFulfillmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The two buttons in the "access granted" email.
 *
 * Both arrive on a signed URL so they work for a guest with no session, and the signature
 * is the authorisation: it names one specific order item and cannot be edited to point at
 * somebody else's.
 */
final class OrderAccessController extends Controller
{
    public function __construct(private readonly AccessFulfillmentService $access) {}

    public function confirm(Request $request, OrderItem $item): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->access->confirm($item, $user?->getKey() === $item->order?->user_id ? $user : null);
        } catch (DomainException $e) {
            return $this->backToOrder($item)->with('error', $e->getMessage());
        }

        return $this->backToOrder($item)->with('status', 'Thanks for confirming. Enjoy your access!');
    }

    public function problemForm(Request $request, OrderItem $item): View
    {
        return view('pages.access-problem', [
            'item' => $item,
            'order' => $item->order,
            'reasons' => ProblemReason::cases(),
        ]);
    }

    public function submitProblem(Request $request, OrderItem $item): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', array_column(ProblemReason::cases(), 'value'))],
            'message' => ['nullable', 'string', 'max:2000'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->hasFile('screenshot')
            ? $request->file('screenshot')->store('access-problems', 'local')
            : null;

        try {
            $this->access->reportProblem(
                $item,
                ProblemReason::from($data['reason']),
                $data['message'] ?? null,
                $path,
                $request->user(),
                $item->order?->guest_email,
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return $this->backToOrder($item)
            ->with('status', 'Thanks — our team has been alerted and will sort this out.');
    }

    private function backToOrder(OrderItem $item): RedirectResponse
    {
        return $item->order
            ? redirect()->route('order.pay', $item->order)
            : redirect()->route('home');
    }
}
