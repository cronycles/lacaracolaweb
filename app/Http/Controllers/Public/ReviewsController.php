<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\View\View;

class ReviewsController extends Controller
{
    public function index(): View
    {
        $reviews = Review::with(['translations', 'booking'])
            ->where('is_active', true)
            ->join('bookings', 'reviews.booking_id', '=', 'bookings.id')
            ->orderByDesc('bookings.checkout')
            ->select('reviews.*')
            ->get();

        return view('public.reviews', compact('reviews'));
    }
}
