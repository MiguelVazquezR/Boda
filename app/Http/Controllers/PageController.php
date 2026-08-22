<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\ScheduleItem;
use App\Models\WeddingSetting;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Landing page principal de la boda.
     * Devuelve todos los datos necesarios para las secciones de la página.
     */
    public function home()
    {
        return Inertia::render('Home', [
            'settings' => WeddingSetting::current(),
            'faqs' => Faq::published()->get(),
            'galleryPhotos' => GalleryPhoto::approved()->latest()->get(),
            'scheduleItems' => ScheduleItem::active()->ordered()->get(),
        ]);
    }
}
