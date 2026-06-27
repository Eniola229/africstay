<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseInquiry;
use Illuminate\Http\Request;

class EnterpriseInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:150'],
            'hotel_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($validated['email']) && blank($validated['phone'])) {
            return back()->withErrors(['email' => 'Please provide at least an email or phone number so our team can reach you.'])->withInput();
        }

        EnterpriseInquiry::create([...$validated, 'status' => 'new']);

        return back()->with('success', "Thanks! Our team will reach out about Enterprise pricing for {$validated['hotel_name']} shortly.");
    }
}
