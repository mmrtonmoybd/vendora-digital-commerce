<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class CartController extends Controller
{
    public function index(): View
    {
        return view('pages.cart');
    }
}
