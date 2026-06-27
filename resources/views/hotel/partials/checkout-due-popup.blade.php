@auth
@php $hotelId = Auth::user()->hotel_id; @endphp

<!-- Checkout-due toast container (bottom-right) -->
<div aria-live="polite" aria-atomic="true"
     style="position:fixed;bottom:24px;right:24px;z-index:9999;min-width:360px;max-width:420px">
    <div id="checkoutDueStack"></div>
</div>

<!-- Toast template (hidden) -->
<template id="checkoutDueTemplate">
    <div class="toast show mb-2 border-0 shadow checkout-due-toast" role="alert" aria-atomic="true"
         style="border-left:4px solid #e67e22!important">
        <div class="toast-header" style="background:#fef9f0;border-bottom:1px solid #fdebd0">
            <span style="width:10px;height:10px;border-radius:50%;background:#e67e22;display:inline-block;margin-right:8px"></span>
            <strong class="me-auto text-warning-emphasis">Checkout Due Soon</strong>
            <small class="text-muted toast-time"></small>
            <button type="button" class="btn-close ms-2" onclick="this.closest('.toast').remove()"></button>
        </div>
        <div class="toast-body py-2 px-3" style="background:#fff;font-size:14px">
            <div class="toast-rooms-list"></div>
            <div class="mt-2">
                <a href="{{ route('hotel.bookings.index', ['status' => 'checked_in']) }}"
                   class="btn btn-sm btn-warning text-white w-100">
                    View Checked-in Bookings
                </a>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    // Guard: only load if Echo is available (it might not be on every page)
    if (typeof window.Echo === 'undefined') {
        console.warn('AfricStay: Laravel Echo not found — checkout due alerts disabled.');
        return;
    }

    window.Echo.channel('hotel.{{ $hotelId }}.alerts')
        .listen('.checkout.due', function (payload) {
            showCheckoutDueToast(payload.rooms || []);
        });

    function showCheckoutDueToast(rooms) {
        if (!rooms.length) return;

        const template = document.getElementById('checkoutDueTemplate');
        const stack    = document.getElementById('checkoutDueStack');
        const clone    = template.content.cloneNode(true);
        const toast    = clone.querySelector('.checkout-due-toast');
        const list     = clone.querySelector('.toast-rooms-list');
        const timeEl   = clone.querySelector('.toast-time');

        timeEl.textContent = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        const title = rooms.length === 1
            ? `Room <strong>${rooms[0].room_number}</strong> — checkout at <strong>${rooms[0].check_out}</strong>`
            : `<strong>${rooms.length} rooms</strong> are due for checkout`;

        list.innerHTML = `<p class="mb-2">${title}</p>`;

        if (rooms.length > 1) {
            const ul = document.createElement('ul');
            ul.className = 'mb-0 ps-3';
            rooms.forEach(r => {
                const li = document.createElement('li');
                li.innerHTML = `Room <strong>${r.room_number}</strong> — ${r.guest_name} — checkout <strong>${r.check_out}</strong>
                    ${r.balance_naira > 0 ? `<span class="badge bg-danger ms-1">₦${Number(r.balance_naira).toLocaleString()} due</span>` : ''}`;
                ul.appendChild(li);
            });
            list.appendChild(ul);
        } else if (rooms[0].balance_naira > 0) {
            list.innerHTML += `<span class="badge bg-danger">₦${Number(rooms[0].balance_naira).toLocaleString()} outstanding</span>`;
        }

        stack.prepend(clone);

        // Auto-dismiss after 2 minutes (the staff should have seen it)
        setTimeout(() => toast?.remove(), 120_000);
    }
})();
</script>
@endauth