<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseInquiry;
use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnterpriseInquiryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['super_admin', 'support', 'operations']);

        $query = EnterpriseInquiry::with('assignee')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('hotel_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Stats — always across ALL records (no filter applied)
        $stats = [
            'total'     => EnterpriseInquiry::count(),
            'new'       => EnterpriseInquiry::where('status', 'new')->count(),
            'contacted' => EnterpriseInquiry::where('status', 'contacted')->count(),
            'converted' => EnterpriseInquiry::where('status', 'converted')->count(),
            'closed'    => EnterpriseInquiry::where('status', 'closed')->count(),
            'unassigned'=> EnterpriseInquiry::whereNull('assigned_to')->where('status', 'new')->count(),
        ];

        return view('platform.inquiries.index', [
            'inquiries'     => $query->paginate(20)->withQueryString(),
            'currentStatus' => $request->query('status', 'all'),
            'admins'        => PlatformAdmin::where('is_active', true)->get(),
            'stats'         => $stats,
        ]);
    }

    public function assign(Request $request, string $inquiry)
    {
        $this->authorizeRole(['super_admin', 'support', 'operations']);
        $inquiry = EnterpriseInquiry::findOrFail($inquiry);

        $validated = $request->validate(['assigned_to' => ['required', 'uuid']]);

        $inquiry->update([
            'assigned_to' => $validated['assigned_to'],
            'status'      => 'contacted',
        ]);

        return back()->with('success', 'Inquiry assigned.');
    }

    public function updateStatus(Request $request, string $inquiry)
    {
        $this->authorizeRole(['super_admin', 'support', 'operations']);
        $inquiry = EnterpriseInquiry::findOrFail($inquiry);

        $validated = $request->validate([
            'status'         => ['required', 'in:new,contacted,converted,closed'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry->update($validated);

        return back()->with('success', 'Inquiry updated.');
    }

    protected function authorizeRole(array $roles): void
    {
        if (! in_array(Auth::guard('platform')->user()->role, $roles)) {
            abort(403);
        }
    }
}