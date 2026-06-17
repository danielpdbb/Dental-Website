<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Public services & pricing page — driven by the live services table
     * (managed under Admin → Services).
     */
    public function services(): View
    {
        return view('services', [
            'services' => Service::active()->orderBy('price')->get(),
        ]);
    }
}
