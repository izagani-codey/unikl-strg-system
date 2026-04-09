<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\FormTemplate;

class Staff2AdminController extends BaseController
{
    public function index()
    {
        // System Stats
        $totalRequests = GrantRequest::count();
        $submitted = GrantRequest::where('status_id', RequestStatus::SUBMITTED->value)->count();
        $staff1Approved = GrantRequest::where('status_id', RequestStatus::STAFF1_APPROVED->value)->count();
        $deanApproved = GrantRequest::where('status_id', RequestStatus::DEAN_APPROVED->value)->count();
        $rejected = GrantRequest::where('status_id', RequestStatus::REJECTED->value)->count();

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
            'submitted',
            'staff1Approved',
            'deanApproved',
            'rejected',
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
            ->with('defaultTemplate')
            ->latest('created_at')
            ->paginate(20);

        $formTemplates = FormTemplate::where('is_active', true)->get();

        return view('staff2.admin-request-types', compact('requestTypes', 'formTemplates'));
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
            return back()->with('error', 'Unable to create request type at the moment. Please try again.')->withInput();
        }
    }

    public function updateRequestType($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            
            $validated = request()->validate([
                'name' => 'required|string|max:255|unique:request_types,name,' . $id,
                'description' => 'nullable|string',
                'default_template_id' => 'nullable|exists:form_templates,id',
            ]);

            // Update slug if name changed
            $validated['slug'] = \Str::slug($validated['name']);

            $requestType->update($validated);

            return back()->with('success', 'Request type updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating request type: ' . $e->getMessage());
            return back()->with('error', 'Unable to update request type at the moment. Please try again.')->withInput();
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
            return back()->with('error', 'Unable to delete request type at the moment. Please try again.');
        }
    }

    public function deploymentPlaybook()
    {
        return view('staff2.deployment-playbook');
    }
}
