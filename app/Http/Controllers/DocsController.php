<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function connecting(): View
    {
        return view('docs-connecting', [
            'title' => 'Verbinden met PostgreSQL',
            'showNav' => false,
        ]);
    }
}
