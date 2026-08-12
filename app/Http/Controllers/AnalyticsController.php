<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Return GA4 visitor count.
     *
     * NOTE: Placeholder implementation. In production you would exchange a JWT for an OAuth token and call the Google Analytics Data API.
     * For demo we return a hard‑coded value read from an env variable.
     */
    public function ga4Visitors(Request $request)
    {
        $visitors = env('GA4_VISITORS', 0);
        return response()->json(['visitors' => (int) $visitors]);
    }
}
