<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function connecting(): Response
    {
        return Inertia::render('Docs/Connecting', [
            'title' => 'Verbinden met PostgreSQL',
        ]);
    }
}
