<?php

namespace App\Http\Controllers;

class CitizenController extends Controller
{
    public function dashboard()
    {
        return view('Citizen.Dashboard');
    }
}