<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2 || strlen($q) > 100) {
            return response()->json(['vendors' => [], 'projects' => [], 'clients' => [], 'staff' => []]);
        }

        $like = "%{$q}%";

        $vendors = Vendor::where('company_name', 'like', $like)
            ->orWhere('vendor_code', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->limit(10)
            ->get(['id', 'company_name', 'vendor_code', 'email']);

        $projects = Project::where('name', 'like', $like)
            ->orWhere('project_code', 'like', $like)
            ->orWhere('client', 'like', $like)
            ->orWhere('po_number', 'like', $like)
            ->limit(10)
            ->get(['id', 'name', 'project_code', 'client']);

        $clients = Client::where('company_name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhere('registration_no', 'like', $like)
            ->limit(10)
            ->get(['id', 'company_name', 'email']);

        $staff = StaffProfile::where('name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->limit(10)
            ->get(['id', 'name', 'email', 'job_title']);

        return response()->json([
            'vendors' => $vendors,
            'projects' => $projects,
            'clients' => $clients,
            'staff' => $staff,
        ]);
    }
}
