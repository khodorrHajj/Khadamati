<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('Citizen.Dashboard');
    }
}
