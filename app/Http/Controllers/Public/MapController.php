<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        $address = config('apartment.address');

        return view('public.map', compact('address'));
    }
}
