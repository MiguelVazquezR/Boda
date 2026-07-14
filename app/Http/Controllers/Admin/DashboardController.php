<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\Guest;
use App\Models\WeddingSetting;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'kpis' => [
                'total' => Guest::count(),
                'confirmed' => Guest::where('rsvp_status', 'confirmed')->count(),
                'declined' => Guest::where('rsvp_status', 'declined')->count(),
                'pending' => Guest::where('rsvp_status', 'pending')->count(),
            ],
            'recentActivity' => Guest::whereNotNull('rsvp_responded_at')
                ->orderByDesc('rsvp_responded_at')
                ->limit(10)
                ->get(['id', 'full_name', 'rsvp_status', 'confirmed_passes', 'rsvp_message', 'rsvp_responded_at']),
            'adminSettings' => WeddingSetting::current()->only(['event_datetime', 'rsvp_deadline']),
            'pendingPhotosCount' => GalleryPhoto::pending()->count(),
        ]);
    }
}
