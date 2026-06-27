<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function __construct(protected CloudinaryService $cloudinary) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function index()
    {
        $rooms = $this->hotel()->rooms()->with('media')->orderBy('room_number')->paginate(20);

        return view('hotel.rooms.index', ['rooms' => $rooms, 'hotel' => $this->hotel()]);
    }

    public function create()
    {
        $this->authorizeManage();

        return view('hotel.rooms.create', [
            'hotel' => $this->hotel(),
            'roomTypes' => ['standard', 'deluxe', 'suite', 'family'],
            'pricingUnits' => [
                'night' => 'Per night (calendar day)',
                'hour' => 'Per hour',
                'day24' => 'Per 24-hour block',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $hotel = $this->hotel();
     
        // ── Scrub blank slots out of images/videos before validation ─────────────
        // This mirrors OnboardingController::saveRooms() and prevents the
        // "images.N.url is required when images is present" error.
        $request->merge([
            'images' => collect($request->input('images', []))
                ->filter(fn ($img) => ! blank($img['url'] ?? null))
                ->values()
                ->all(),
            'videos' => collect($request->input('videos', []))
                ->filter(fn ($vid) => ! blank($vid['url'] ?? null))
                ->values()
                ->all(),
        ]);
     
        $validated = $request->validate([
            'room_number'        => ['required', 'string', 'max:20',
                                     'unique:rooms,room_number,NULL,id,hotel_id,'.$hotel->id],
            'name'               => ['nullable', 'string', 'max:100'],
            'type'               => ['required', 'in:standard,deluxe,suite,family'],
            'floor'              => ['nullable', 'string', 'max:20'],
            'price_per_night'    => ['required', 'numeric', 'min:0'],
            'max_guests'         => ['required', 'integer', 'min:1', 'max:20'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'pricing_unit'       => ['required', 'in:night,hour,day24'],
            'images'             => ['nullable', 'array'],
            'images.*.url'       => ['required', 'url'],          // no required_with — blank slots already removed
            'images.*.public_id' => ['nullable', 'string'],
            'videos'             => ['nullable', 'array'],
            'videos.*.url'       => ['required', 'url'],
            'videos.*.public_id' => ['nullable', 'string'],
        ]);
     
        $limit = Hotel::TIER_ROOM_LIMITS[$hotel->tier];
        if ($limit !== null && $hotel->rooms()->count() >= $limit) {
            return back()->withErrors([
                'room_number' => "Your {$hotel->tier} tier allows up to {$limit} rooms. Upgrade to add more.",
            ]);
        }
     
        $room = Room::create([
            'hotel_id'       => $hotel->id,
            'room_number'    => $validated['room_number'],
            'name'           => $validated['name'] ?? null,
            'type'           => $validated['type'],
            'floor'          => $validated['floor'] ?? null,
            'price_per_night'=> (int) round($validated['price_per_night'] * 100),
            'max_guests'     => $validated['max_guests'],
            'description'    => $validated['description'] ?? null,
            'pricing_unit'   => $validated['pricing_unit'],
            'status'         => 'available',
        ]);
     
        $this->attachMedia($room, $validated['images'] ?? [], 'image');
        $this->attachMedia($room, $validated['videos'] ?? [], 'video');
     
        ActivityLog::record(
            $hotel->id, Auth::user(), 'CREATE_ROOM', 'room', 'Room', $room->id,
            "Room {$room->room_number}",
            "Added Room {$room->room_number} ({$room->type})."
        );
     
        return redirect()->route('hotel.rooms.index')->with('success', "Room {$room->room_number} added.");
    }

    public function edit(Room $room)
    {
        $this->authorizeManage();
        $this->authorizeRoomBelongsToHotel($room);

        return view('hotel.rooms.edit', [
            'room' => $room->load('media'),
            'roomTypes' => ['standard', 'deluxe', 'suite', 'family'],
            'pricingUnits' => [
                'night' => 'Per night (calendar day)',
                'hour' => 'Per hour',
                'day24' => 'Per 24-hour block',
            ],
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $this->authorizeManage();
        $this->authorizeRoomBelongsToHotel($room);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:standard,deluxe,suite,family'],
            'floor' => ['nullable', 'string', 'max:20'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pricing_unit' => ['required', 'in:night,hour,day24'],
        ]);

        $old = $room->only(['price_per_night', 'type', 'max_guests', 'pricing_unit']);

        $room->update([
            ...$validated,
            'price_per_night' => (int) round($validated['price_per_night'] * 100),
        ]);

        ActivityLog::record($room->hotel_id, Auth::user(), 'UPDATE_ROOM', 'room', 'Room', $room->id,
            "Room {$room->room_number}", "Updated Room {$room->room_number}.", $old, $room->only(['price_per_night', 'type', 'max_guests', 'pricing_unit']));

        return back()->with('success', 'Room updated.');
    }

    /** Add more photos/videos to an existing room (called via the Cloudinary widget on the edit page). */
    public function addMedia(Request $request, Room $room)
    {
        $this->authorizeManage();
        $this->authorizeRoomBelongsToHotel($room);

        $validated = $request->validate([
            'type' => ['required', 'in:image,video'],
            'url' => ['required', 'url'],
            'public_id' => ['nullable', 'string'],
        ]);

        $room->media()->create([
            'type' => $validated['type'],
            'url' => $validated['url'],
            'cloudinary_public_id' => $validated['public_id'] ?? null,
            'is_primary' => $validated['type'] === 'image' && ! $room->images()->exists(),
            'sort_order' => $room->media()->where('type', $validated['type'])->count(),
        ]);

        return back()->with('success', 'Media added.');
    }

    public function removeMedia(Room $room, string $mediaId)
    {
        $this->authorizeManage();
        $this->authorizeRoomBelongsToHotel($room);

        $media = $room->media()->where('id', $mediaId)->firstOrFail();

        if ($media->cloudinary_public_id) {
            $this->cloudinary->destroy($media->cloudinary_public_id, $media->type === 'video' ? 'video' : 'image');
        }

        $media->delete();

        return back()->with('success', 'Media removed.');
    }

    public function blockForMaintenance(Request $request, Room $room)
    {
        $this->authorizeManage();
        $this->authorizeRoomBelongsToHotel($room);

        $validated = $request->validate([
            'maintenance_reason' => ['required', 'string', 'max:255'],
            'maintenance_expected_return' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $room->update([
            'status' => 'maintenance',
            ...$validated,
        ]);

        ActivityLog::record($room->hotel_id, Auth::user(), 'UPDATE_ROOM_STATUS', 'room', 'Room', $room->id,
            "Room {$room->room_number}", "Blocked Room {$room->room_number} for maintenance: {$validated['maintenance_reason']}.");

        return back()->with('success', "Room {$room->room_number} marked under maintenance.");
    }

    protected function attachMedia(Room $room, array $items, string $type): void
    {
        foreach ($items as $i => $item) {
            $room->media()->create([
                'type' => $type,
                'url' => $item['url'],
                'cloudinary_public_id' => $item['public_id'] ?? null,
                'is_primary' => $type === 'image' && $i === 0,
                'sort_order' => $i,
            ]);
        }
    }

    protected function authorizeManage(): void
    {
        if (! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403, 'Only owners and managers can manage rooms.');
        }
    }

    protected function authorizeRoomBelongsToHotel(Room $room): void
    {
        if ($room->hotel_id !== $this->hotel()->id) {
            abort(404);
        }
    }
}