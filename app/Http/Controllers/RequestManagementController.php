<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
use App\Services\RequestManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestManagementController extends Controller
{
    protected $requestManagementService;

    public function __construct(RequestManagementService $requestManagementService)
    {
        $this->requestManagementService = $requestManagementService;
    }

    public function index()
    {
        $requests = $this->requestManagementService->getUserRequests();
        $requestTypes = RequestType::all();
        
        return view('requests.index', compact('requests', 'requestTypes'));
    }

    public function create()
    {
        $requestTypes = RequestType::all();
        $votCodes = VotCode::active()->ordered()->get();
        
        return view('requests.create', compact('requestTypes', 'votCodes'));
    }

    public function store(StoreRequestRequest $request)
    {
        try {
            $grantRequest = $this->requestManagementService->createRequest($request);
            
            return redirect()->route('requests.show', $grantRequest->id)
                ->with('success', 'Request submitted successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to submit request: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $grantRequest = $this->requestManagementService->getRequestWithRelations($id);
        
        return view('requests.show', compact('grantRequest'));
    }

    public function edit($id)
    {
        $grantRequest = $this->requestManagementService->getRequestForEdit($id);
        $requestTypes = RequestType::all();
        $votCodes = VotCode::active()->ordered()->get();
        
        return view('requests.edit', compact('grantRequest', 'requestTypes', 'votCodes'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        try {
            $grantRequest = $this->requestManagementService->updateRequest($request, $id);
            
            return redirect()->route('requests.show', $grantRequest->id)
                ->with('success', 'Request updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update request: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->requestManagementService->deleteRequest($id);
            
            return redirect()->route('requests.index')
                ->with('success', 'Request deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete request: ' . $e->getMessage());
        }
    }
}
