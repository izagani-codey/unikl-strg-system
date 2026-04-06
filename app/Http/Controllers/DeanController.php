<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\Http\Request;

class DeanController extends Controller
{
    public function dashboard()
    {
        // Canonical dean dashboard is served by DashboardController at /dashboard.
        return redirect()->route('dashboard');
    }

    public function requests()
    {
        return redirect()->route('dashboard');
    }

    public function show($id)
    {
        return redirect()->route('requests.show', $id);
    }

    public function approve(Request $httpRequest, $id)
    {
        return redirect()->route('requests.show', $id)
            ->with('error', 'Please use the main request action panel to apply dean decisions with signature.');
    }

    public function reject(Request $httpRequest, $id)
    {
        return redirect()->route('requests.show', $id)
            ->with('error', 'Please use the main request action panel to apply dean decisions with signature.');
    }

    public function returnToStaff1(Request $httpRequest, $id)
    {
        return redirect()->route('requests.show', $id)
            ->with('error', 'Please use the main request action panel to apply dean decisions with signature.');
    }

    public function returnToStaff2(Request $httpRequest, $id)
    {
        return redirect()->route('requests.show', $id)
            ->with('error', 'Please use the main request action panel to apply dean decisions with signature.');
    }
}
