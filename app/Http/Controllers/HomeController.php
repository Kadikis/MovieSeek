<?php

namespace App\Http\Controllers;

use Inertia\Response as InertiaResponse;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Home');
    }
}
