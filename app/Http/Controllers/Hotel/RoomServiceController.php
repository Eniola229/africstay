<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomServiceItem;
use App\Models\RoomServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomServiceController extends Controller
{
    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    /** Manager sets up the chargeable menu. */
    public function items()
    {
        return view('hotel.room-service.items', ['items' => $this->hotel()->roomServiceItems()->orderBy('category')->get()]);
    }

    public function storeItem(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:food,drink,laundry,misc'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->hotel()->roomServiceItems()->create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'price' => (int) round($validated['price'] * 100),
            'is_active' => true,
        ]);

        return back()->with('success', 'Menu item added.');
    }

    public function toggleItem(string $item)
    {
        $this->authorizeManage();
        $item = $this->hotel()->roomServiceItems()->findOrFail($item);
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with('success', 'Menu item updated.');
    }

    /** Room-service / receptionist staff dashboard — see active orders, update status. */
    public function orders(Request $request)
    {
        $query = $this->hotel()->roomServiceOrders()->with(['item', 'booking.guest', 'booking.room'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('hotel.room-service.orders', [
            'orders' => $query->paginate(20)->withQueryString(),
            'currentStatus' => $request->query('status', 'all'),
        ]);
    }

    /** Add an extra to a guest's running bill — auto-included in the total at checkout. */
    public function addOrder(Request $request, string $booking)
    {
        $hotel = $this->hotel();
        $booking = $hotel->bookings()->where('status', 'checked_in')->findOrFail($booking);

        $validated = $request->validate([
            'item_id' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $item = $hotel->roomServiceItems()->where('is_active', true)->findOrFail($validated['item_id']);
        $totalPrice = $item->price * $validated['quantity'];

        DB::transaction(function () use ($hotel, $booking, $item, $validated, $totalPrice) {
            RoomServiceOrder::create([
                'booking_id' => $booking->id,
                'hotel_id' => $hotel->id,
                'item_id' => $item->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $item->price,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'requested_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Auto-added to the guest's running bill immediately, per spec.
            $booking->increment('total_amount', $totalPrice);
            $booking->update(['balance' => max(0, $booking->total_amount - $booking->amount_paid)]);
        });

        ActivityLog::record($hotel->id, Auth::user(), 'ADD_ROOM_SERVICE', 'room_service', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." added {$validated['quantity']}x {$item->name} to booking {$booking->booking_reference}.");

        return back()->with('success', "{$item->name} added to the bill.");
    }

    public function updateOrderStatus(Request $request, string $order)
    {
        $order = $this->hotel()->roomServiceOrders()->findOrFail($order);

        $validated = $request->validate(['status' => ['required', 'in:pending,in_progress,delivered,cancelled']]);
        $order->update(['status' => $validated['status']]);

        return back()->with('success', 'Order status updated.');
    }

    protected function authorizeManage(): void
    {
        if (! in_array(Auth::user()->role, ['owner', 'manager'])) {
            abort(403, 'Only owners and managers can manage the room service menu.');
        }
    }
}
