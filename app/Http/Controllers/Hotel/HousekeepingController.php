<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HousekeepingController extends Controller
{
    public function __construct(protected SmsService $sms) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    /**
     * Housekeepers see only their assigned tasks (mobile-friendly list).
     * Owners/managers see the full board across all rooms.
     */
    public function index()
    {
        $user = Auth::user();
        $query = $this->hotel()->housekeepingTasks()->with(['room', 'assignee'])->latest();

        if ($user->role === 'housekeeper') {
            $query->where('assigned_to', $user->id)->where('status', '!=', 'verified');
        }

        return view('hotel.housekeeping.index', [
            'tasks' => $query->get(),
            'isSupervisor' => in_array($user->role, ['owner', 'manager']),
        ]);
    }

    /** Housekeeper checks off items / marks cleaned. */
    public function updateChecklist(Request $request, string $task)
    {
        $task = $this->hotel()->housekeepingTasks()->findOrFail($task);

        if ($task->assigned_to !== Auth::id() && ! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403);
        }

        $validated = $request->validate(['checklist' => ['required', 'array']]);
        $task->update(['checklist' => $validated['checklist'], 'status' => 'in_progress']);

        return back()->with('success', 'Checklist updated.');
    }

    public function markCleaned(string $task)
    {
        $task = $this->hotel()->housekeepingTasks()->with('room')->findOrFail($task);

        if ($task->assigned_to !== Auth::id() && ! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403);
        }

        $task->update(['status' => 'cleaned', 'completed_at' => now()]);

        ActivityLog::record($task->hotel_id, Auth::user(), 'MARK_ROOM_CLEANED', 'housekeeping', 'HousekeepingTask', $task->id,
            "Room {$task->room->room_number}", Auth::user()->name." marked Room {$task->room->room_number} as cleaned, pending verification.");

        return back()->with('success', "Room {$task->room->room_number} marked cleaned. Awaiting supervisor verification.");
    }

    /** Manager/owner reassigns a task to a different housekeeper. */
    public function reassign(Request $request, string $task)
    {
        $this->authorizeSupervisor();
        $task = $this->hotel()->housekeepingTasks()->findOrFail($task);

        $validated = $request->validate(['assigned_to' => ['required', 'uuid']]);
        $newAssignee = $this->hotel()->users()->where('role', 'housekeeper')->findOrFail($validated['assigned_to']);

        $task->update(['assigned_to' => $newAssignee->id]);

        if ($newAssignee->phone) {
            $this->sms->send($newAssignee->phone, "Room {$task->room->room_number} cleaning task reassigned to you at {$task->hotel->name}.");
        }

        return back()->with('success', 'Task reassigned.');
    }

    /** Final step of the closed loop: checkout -> dirty -> assigned -> cleaned -> verified -> available. */
    public function verify(string $task)
    {
        $this->authorizeSupervisor();
        $task = $this->hotel()->housekeepingTasks()->with('room')->findOrFail($task);

        if ($task->status !== 'cleaned') {
            return back()->withErrors(['task' => 'Only a task marked "cleaned" can be verified.']);
        }

        $task->update(['status' => 'verified', 'verified_by' => Auth::id(), 'verified_at' => now()]);
        $task->room->update(['status' => 'available']);

        ActivityLog::record($task->hotel_id, Auth::user(), 'VERIFY_ROOM_CLEANED', 'housekeeping', 'HousekeepingTask', $task->id,
            "Room {$task->room->room_number}", Auth::user()->name." verified Room {$task->room->room_number} is clean. Room is now available.");

        return back()->with('success', "Room {$task->room->room_number} verified and is now available.");
    }

    protected function authorizeSupervisor(): void
    {
        if (! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403, 'Only owners and managers can do this.');
        }
    }
}
