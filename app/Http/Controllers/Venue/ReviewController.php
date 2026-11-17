<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use App\Models\EventReview;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Show anonymous rating aggregates for this Venue provider.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->venueProfile;
        abort_unless($profile !== null, 404);

        $reviews = EventReview::query()
            ->whereHas('orderItem', fn ($query) => $query
                ->where('provider_type', 'venue_profile')
                ->where('provider_id', $profile->id))
            ->selectRaw('rating, COUNT(*) as review_count')
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->get();

        return view('venue.ulasan', [
            'ratingDistribution' => $reviews,
            'reviewCount' => $reviews->sum('review_count'),
            'ratingAverage' => EventReview::query()
                ->whereHas('orderItem', fn ($query) => $query
                    ->where('provider_type', 'venue_profile')
                    ->where('provider_id', $profile->id))
                ->avg('rating'),
        ]);
    }
}
