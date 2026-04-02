<?php

namespace App\Http\Controllers;

use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\FormTemplate;

class Staff2AdminController extends Controller
{
    public function index()
    {
        // System Stats
        $totalRequests = GrantRequest::count();
        $pendingVerification = GrantRequest::where('status_id', 1)->count();
        $withStaff2 = GrantRequest::where('status_id', 2)->count();
        $approved = GrantRequest::where('status_id', 5)->count();
        $declined = GrantRequest::where('status_id', 6)->count();

        // Request Types Stats
        $byType = RequestType::query()
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->take(6)
            ->get();

        // Recent High Priority Requests
        $recentHighPriority = GrantRequest::query()
            ->with('user', 'requestType')
            ->where('is_priority', true)
            ->latest()
            ->take(8)
            ->get();

        // User Stats
        $totalUsers = User::count();
        $admissionUsers = User::where('role', 'admission')->count();
        $staff1Users = User::where('role', 'staff1')->count();
        $staff2Users = User::where('role', 'staff2')->count();

        // Form Templates
        $totalTemplates = FormTemplate::count();
        $recentTemplates = FormTemplate::with('uploader')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('staff2.admin-panel', compact(
            'totalRequests',
            'pendingVerification',
            'withStaff2',
            'approved',
            'declined',
            'byType',
            'recentHighPriority',
            'totalUsers',
            'admissionUsers',
            'staff1Users',
            'staff2Users',
            'totalTemplates',
            'recentTemplates'
        ));
    }

    public function users()
    {
        $users = User::query()
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20);

        return view('staff2.admin-users', compact('users'));
    }

    public function requestTypes()
    {
        $requestTypes = RequestType::query()
            ->withCount('requests')
            ->latest('created_at')
            ->paginate(20);

        return view('staff2.admin-request-types', compact('requestTypes'));
    }

    public function storeRequestType()
    {
        try {
            $validated = request()->validate([
                'name' => 'required|string|max:255|unique:request_types',
                'description' => 'nullable|string',
            ]);

            // Create slug from name
            $validated['slug'] = \Str::slug($validated['name']);

            RequestType::create($validated);

            return back()->with('success', 'Request type created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error creating request type: ' . $e->getMessage());
            return back()->with('error', 'Error creating request type: ' . $e->getMessage())->withInput();
        }
    }

    public function updateRequestType($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            
            $validated = request()->validate([
                'name' => 'required|string|max:255|unique:request_types,name,' . $id,
                'description' => 'nullable|string',
            ]);

            // Update slug if name changed
            $validated['slug'] = \Str::slug($validated['name']);

            $requestType->update($validated);

            return back()->with('success', 'Request type updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating request type: ' . $e->getMessage());
            return back()->with('error', 'Error updating request type: ' . $e->getMessage())->withInput();
        }
    }

    public function destroyRequestType($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            
            // Check if there are requests using this type
            if ($requestType->requests()->count() > 0) {
                return back()->with('error', 'Cannot delete request type that has associated requests.');
            }

            $requestType->delete();

            return back()->with('success', 'Request type deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting request type: ' . $e->getMessage());
            return back()->with('error', 'Error deleting request type: ' . $e->getMessage());
        }
    }

    public function deploymentPlaybook()
    {
        return view('staff2.deployment-playbook');
    }
}
