<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UsefulPlacesController extends Controller
{
    public function index(): View
    {
        return view('public.useful-places');
    }
}
