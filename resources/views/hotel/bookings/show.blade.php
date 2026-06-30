@extends('layouts.hotel')
@section('title', 'Booking ' . $booking->booking_reference)
@section('page_title', 'Booking ' . $booking->booking_reference)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active">{{ $booking->booking_reference }}</li>
@endsection

@section('content')
@php
    $badge = match($booking->status) {
        'pending' => 'bg-secondary', 
        'confirmed' => 'bg-info text-white', 
        'checked_in' => 'bg-success', 
        'checked_out' => 'bg-dark', 
        'cancelled' => 'bg-danger', 
        default => 'bg-secondary',
    };
    $latestPayment = $booking->payments->sortByDesc('created_at')->first();
    $isPendingPayment = $booking->status === 'checked_in' && $booking->balance > 0;
    
    // Check if this is an online booking (full payment) vs virtual account booking
    $isOnlineBooking = $booking->booking_source === 'online' && $latestPayment && $latestPayment->type === 'full_payment';
    $hasVirtualAccount = $latestPayment && $latestPayment->virtual_account_number;
@endphp
@if($isOverdue)
<div class="alert alert-danger d-flex align-items-center mb-3">
    <i class="feather-alert-triangle me-2" style="font-size:20px;"></i>
    <div>
        <strong>Checkout overdue.</strong>
        This guest's check-out was due {{ $booking->check_out->format('d M Y h:i A') }} —
        overdue since {{ $booking->check_out->diffForHumans() }}.
    </div>
</div>
@endif
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Booking Details</h5>
                <div>
                    @if($isPendingPayment)
                        <span class="badge bg-warning text-dark me-1" id="paymentStatusBadge">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Awaiting Payment
                        </span>
                    @endif
                    <span class="badge {{ $badge }} text-capitalize">{{ str_replace('_',' ',$booking->status) }}</span>
                    @if($isOnlineBooking)
                        <span class="badge bg-primary ms-1">Online Booking</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-2"><div class="col-4 text-muted">Guest</div><div class="col-8 fw-semibold">{{ $booking->guest->name }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Contact</div><div class="col-8">{{ $booking->guest->phone ?? '—' }} {{ $booking->guest->email ? '· '.$booking->guest->email : '' }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Room</div><div class="col-8">Room {{ $booking->room->room_number }} ({{ ucfirst($booking->room->type) }})</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Dates</div><div class="col-8">{{ $booking->check_in->format('d M Y') }} – {{ $booking->check_out->format('d M Y') }} ({{ $booking->nights }} night(s))</div></div>
                @if($booking->notes)
                <div class="row mb-2"><div class="col-4 text-muted">Notes</div><div class="col-8">{{ $booking->notes }}</div></div>
                @endif
                @if($isOnlineBooking)
                <div class="row mb-2">
                    <div class="col-4 text-muted">Booking Type</div>
                    <div class="col-8"><span class="badge bg-primary">Online Booking (Full Payment)</span></div>
                </div>
                @endif
            </div>
        </div>

        @if(in_array($booking->status, ['checked_in', 'checked_out']))
            <div class="card mt-3">
                <div class="card-header"><h5 class="card-title mb-0">Housekeeping</h5></div>
                <div class="card-body">
                    @if($pendingHousekeepingTask)
                        <p class="text-muted fs-13 mb-2">
                            <i class="feather-droplet me-1"></i>
                            There's already an active housekeeping task for this room
                            (status: <span class="badge bg-warning text-dark text-capitalize">{{ str_replace('_',' ',$pendingHousekeepingTask->status) }}</span>).
                        </p>
                        <a href="{{ route('hotel.housekeeping.index') }}" class="btn btn-sm btn-outline-secondary">View Housekeeping</a>
                    @else
                        <p class="text-muted fs-13 mb-2">
                            @if($booking->status === 'checked_in')
                                Need the room cleaned while the guest is still staying (e.g. multi-day stay)? Request it without checking them out.
                            @else
                                No active housekeeping task found for this room — request one if it was missed.
                            @endif
                        </p>
                        <form action="{{ route('hotel.bookings.request-housekeeping', $booking->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="feather-droplet me-1"></i> Request Housekeeping
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @endif

        @if($booking->status === 'checked_in')
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Room Service &amp; Extras</h5>
                @if(in_array(auth()->user()->role, ['owner','manager']))
                <a href="{{ route('hotel.room-service.items') }}" class="btn btn-sm btn-outline-secondary">Manage Menu</a>
                @endif
            </div>
            <div class="card-body">
                @php $menuItems = $booking->hotel->roomServiceItems()->where('is_active', true)->get(); @endphp
                @if($menuItems->isEmpty())
                <p class="text-muted fs-13 mb-0">No menu items set up yet.
                    @if(in_array(auth()->user()->role, ['owner','manager']))
                    <a href="{{ route('hotel.room-service.items') }}">Add some</a>.
                    @endif
                </p>
                @else
                <form action="{{ route('hotel.room-service.orders.add', $booking->id) }}" method="POST" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-5">
                        <select name="item_id" class="form-select" required>
                            <option value="">Select item...</option>
                            @foreach($menuItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} — ₦{{ number_format($item->priceNaira(), 2) }} ({{ ucfirst($item->category) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="quantity" value="1" min="1" class="form-control" placeholder="Qty">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="notes" class="form-control" placeholder="Notes (optional)">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="feather-plus me-1"></i> Add
                        </button>
                    </div>
                </form>
                @endif

                @if($booking->roomServiceOrders->count())
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($booking->roomServiceOrders as $order)
                        <tr>
                            <td>{{ $order->quantity }}x {{ $order->item->name }}</td>
                            <td class="fw-bold">₦{{ number_format($order->totalPriceNaira(), 2) }}</td>
                            <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_',' ',$order->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
        @endif

        @if($booking->status === 'checked_in')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Check Out</h5></div>
            <div class="card-body">
                @if($booking->balance > 0)
                <div class="alert alert-warning">
                    Outstanding balance: <strong>₦{{ number_format($booking->balanceNaira(), 2) }}</strong>.
                    Guest must pay, or a manager/owner can override below.
                </div>
                <form action="{{ route('hotel.bookings.check-out', $booking->id) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="manager_override" value="1">
                    <div class="mb-2">
                        <input type="text" name="override_reason" class="form-control @error('override_reason') is-invalid @enderror" placeholder="Reason for override (manager/owner only)">
                        @error('override_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="feather-alert-triangle me-1"></i> Override &amp; Check Out
                    </button>
                </form>
                @else
                <form action="{{ route('hotel.bookings.check-out', $booking->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-dark">
                        <i class="feather-log-out me-1"></i> Confirm Check-Out
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endif

        <div class="card mb-3 border-{{ $booking->status === 'checked_out' ? 'success' : 'secondary' }}">
            <div class="card-header {{ $booking->status === 'checked_out' ? 'bg-success text-white' : 'bg-light' }}">
                <h5 class="card-title mb-0">
                    <i class="feather-file-text me-1"></i> 
                    Receipt
                    @if($booking->status !== 'checked_out')
                        <span class="badge bg-secondary text-capitalize ms-2">{{ str_replace('_',' ',$booking->status) }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <strong style="font-size:18px;">{{ $booking->hotel->name }}</strong>
                    <p class="text-muted mb-0" style="font-size:13px;">{{ $booking->hotel->address ?? '' }} {{ $booking->hotel->phone ? '· '.$booking->hotel->phone : '' }}</p>
                </div>
                <hr>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Booking reference</div>
                    <div class="col-6 text-end fw-semibold">{{ $booking->booking_reference }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Guest</div>
                    <div class="col-6 text-end">{{ $booking->guest->name }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Room</div>
                    <div class="col-6 text-end">Room {{ $booking->room->room_number }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Check-in</div>
                    <div class="col-6 text-end">{{ $booking->check_in->format('d M Y h:i A') }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Check-out</div>
                    <div class="col-6 text-end">{{ $booking->check_out->format('d M Y h:i A') }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Duration</div>
                    <div class="col-6 text-end">{{ $booking->nights }} {{ $booking->pricing_unit ?? 'night' }}(s)</div>
                </div>
                <hr>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Total amount</div>
                    <div class="col-6 text-end fw-bold">₦{{ number_format($booking->totalAmountNaira(), 2) }}</div>
                </div>
                <div class="row mb-1">
                    <div class="col-6 text-muted">Amount paid</div>
                    <div class="col-6 text-end text-success fw-bold">₦{{ number_format($booking->amountPaidNaira(), 2) }}</div>
                </div>
                <div class="row">
                    <div class="col-6 text-muted">Balance</div>
                    <div class="col-6 text-end fw-bold {{ $booking->balance > 0 ? 'text-danger' : 'text-success' }}">
                        ₦{{ number_format($booking->balanceNaira(), 2) }}
                        @if($booking->balance <= 0)
                            <span class="badge bg-success ms-1">Paid ✓</span>
                        @endif
                    </div>
                </div>
                
                @if($isOnlineBooking)
                <div class="alert alert-info mt-3 mb-0">
                    <i class="feather-credit-card me-1"></i> This booking was paid online in full.
                </div>
                @endif
                
                @if($booking->status === 'checked_out')
                <div class="alert alert-success mt-3 mb-0">
                    <i class="feather-check-circle me-1"></i> Checked out on {{ $booking->checked_out_at ? $booking->checked_out_at->format('d M Y h:i A') : 'N/A' }}
                </div>
                @endif
                
                <button onclick="window.print()" class="btn btn-sm btn-outline-dark mt-3">
                    <i class="feather-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>

        @if($booking->status !== 'cancelled' && $booking->status !== 'checked_out' && $booking->status !== 'checked_in')
        <form action="{{ route('hotel.bookings.cancel', $booking->id) }}" method="POST"
              onsubmit="return confirm('Cancel booking {{ $booking->booking_reference }}?');">
            @csrf
            <button type="submit" class="btn btn-link text-danger">Cancel this booking</button>
        </form>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Payment Summary</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total</span><span class="fw-bold" id="totalAmount">₦{{ number_format($booking->totalAmountNaira(), 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paid</span><span class="fw-bold text-success" id="paidAmount">₦{{ number_format($booking->amountPaidNaira(), 2) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between"><span class="fw-bold">Balance</span><span class="fw-bold {{ $booking->balance > 0 ? 'text-danger' : 'text-success' }}" id="balanceAmount">₦{{ number_format($booking->balanceNaira(), 2) }}</span></div>
                @if($isOnlineBooking)
                <div class="mt-2 text-center">
                    <span class="badge bg-primary">Paid Online</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Show Virtual Account if it exists, otherwise show Online Booking info --}}
        @if($latestPayment)
            @if($hasVirtualAccount)
            <div class="card" id="paymentAccountCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment Account</h5>
                    @if($latestPayment->status !== 'confirmed')
                        <span class="badge bg-warning text-dark" id="paymentStatusBadgeCard">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Pending
                        </span>
                    @else
                        <span class="badge bg-success" id="paymentStatusBadgeCard">
                            <i class="feather-check me-1"></i> Paid
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted fs-13 mb-1">Show or read this out to the guest:</p>
                    <div class="p-3 rounded mb-2" style="background:#f8f9fa;">
                        <div class="fs-12 text-muted">Account Number</div>
                        <div class="fw-bold fs-5" id="accountNumber">{{ $latestPayment->virtual_account_number }}</div>
                        <div class="fs-12 text-muted mt-2">Bank</div>
                        <div class="fw-semibold" id="accountBank">{{ $latestPayment->virtual_account_bank }}</div>
                        <div class="fs-12 text-muted mt-2">Account Name</div>
                        <div class="fw-semibold" id="accountName">{{ $latestPayment->virtual_account_name }}</div>
                        <div class="fs-12 text-muted mt-2">Amount Due</div>
                        <div class="fw-bold" id="amountDue">₦{{ number_format($latestPayment->amountNaira(), 2) }}</div>
                    </div>
                    <span class="badge {{ $latestPayment->status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' }}" id="paymentStatusBadgeBottom">
                        {{ ucfirst($latestPayment->status) }}
                    </span>
                </div>
            </div>
            @elseif($isOnlineBooking)
            <div class="card" id="onlineBookingCard">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="feather-credit-card me-1"></i> Online Booking
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-2">
                        <i class="feather-check-circle" style="font-size:48px;color:#0a3622;"></i>
                    </div>
                    <p class="text-muted fs-13 text-center">This booking was made online and paid in full.</p>
                    <div class="p-3 rounded" style="background:#f0f5f0;">
                        <div class="row mb-1">
                            <div class="col-6 text-muted">Payment Reference</div>
                            <div class="col-6 text-end fw-semibold">{{ $latestPayment->payment_reference }}</div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-6 text-muted">Amount Paid</div>
                            <div class="col-6 text-end fw-bold text-success">₦{{ number_format($latestPayment->amountNaira(), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-muted">Status</div>
                            <div class="col-6 text-end">
                                <span class="badge {{ $latestPayment->status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($latestPayment->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endif

        @if($isPendingPayment)
        <div class="mt-3 text-center">
            <small class="text-muted" id="lastCheckText">Checking for payment...</small>
        </div>
        @endif
    </div>
</div>

@if($isPendingPayment)
@push('scripts')
<script>
(function() {
    let checkCount = 0;
    const maxChecks = 60;
    const bookingId = '{{ $booking->id }}';
    const paymentId = '{{ $latestPayment->id ?? null }}';

    function checkPaymentStatus() {
        checkCount++;
        
        fetch('{{ route("hotel.bookings.check-payment", $booking->id) }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const lastCheck = document.getElementById('lastCheckText');
            if (lastCheck) {
                const now = new Date();
                lastCheck.textContent = 'Last checked: ' + now.toLocaleTimeString();
            }

            if (data.payment_confirmed) {
                window.location.reload();
                return;
            }

            if (data.payment_status === 'confirmed') {
                window.location.reload();
                return;
            }

            const statusBadge = document.getElementById('paymentStatusBadge');
            const statusBadgeCard = document.getElementById('paymentStatusBadgeCard');
            const statusBadgeBottom = document.getElementById('paymentStatusBadgeBottom');
            
            if (statusBadge) {
                statusBadge.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Awaiting Payment';
            }

            if (checkCount < maxChecks) {
                setTimeout(checkPaymentStatus, 5000);
            } else {
                if (lastCheck) {
                    lastCheck.textContent = '⏰ Still waiting for payment. The guest can pay via the account above.';
                    lastCheck.className = 'text-warning';
                }
            }
        })
        .catch(error => {
            console.error('Error checking payment:', error);
            if (checkCount < maxChecks) {
                setTimeout(checkPaymentStatus, 10000);
            }
        });
    }

    setTimeout(checkPaymentStatus, 3000);
})();
</script>
@endpush
@endif
@endsection