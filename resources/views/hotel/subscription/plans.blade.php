@extends('layouts.auth')
@section('title', 'Choose Your Plan')
@section('content')
<div class="auth-main" style="display:block; padding:40px 20px;">

    <div style="max-width:1100px; margin:0 auto;">

        <div class="text-center mb-5">
            <img src="{{ asset('dashboard/assets/images/favicon.png') }}" style="height:40px;" alt="AfricStay">
            <h2 class="fw-bold mt-3 mb-2">Choose a plan for {{ $hotel->name }}</h2>
            <p class="text-muted">
                @if($hotel->subscription_status === 'pending_payment')
                    One more step — activate your account to unlock rooms, bookings and payments.
                @else
                    Your current plan: <strong class="text-capitalize">{{ $hotel->tier }}</strong>
                    @if($hotel->subscription_ends_at)
                        — renews/expires {{ $hotel->subscription_ends_at->format('jS M Y') }}
                    @endif
                @endif
            </p>
        </div>

        {{-- Billing cycle toggle --}}
        <div class="d-flex justify-content-center mb-5">
            <div class="btn-group" role="group" id="billingToggle">
                <button type="button" class="btn btn-outline-primary active" data-cycle="monthly">Monthly</button>
                <button type="button" class="btn btn-outline-primary" data-cycle="yearly">
                    Yearly <span class="badge bg-success ms-1">Save 20%</span>
                </button>
            </div>
        </div>

        <div class="row g-4">
            @foreach($tiers as $tierKey => $pricing)
            @php
                $labels = ['starter' => 'Starter', 'growth' => 'Growth', 'pro' => 'Pro'];
                $feeLabels = ['starter' => '1.5%', 'growth' => '1.0%', 'pro' => '0.75%'];
                $roomLabels = ['starter' => 'Up to 15 rooms', 'growth' => 'Up to 50 rooms', 'pro' => 'Unlimited rooms'];
                $highlight = $tierKey === 'growth';
            @endphp
            <div class="col-md-4">
                <div class="card h-100 {{ $highlight ? 'border-primary shadow-lg' : '' }}" style="{{ $highlight ? 'transform:scale(1.02);' : '' }}">
                    @if($highlight)
                    <div class="text-center text-white fw-bold py-1" style="background:var(--bs-primary,#2ECC71);font-size:12px;letter-spacing:.5px;">
                        MOST POPULAR
                    </div>
                    @endif
                    <div class="card-body text-center d-flex flex-column">
                        <h4 class="fw-bold">{{ $labels[$tierKey] }}</h4>

                        <div class="my-3">
                            <span class="price-monthly">
                                <span class="fs-2 fw-bold">₦{{ number_format($pricing['monthly'] / 100) }}</span>
                                <span class="text-muted">/month</span>
                            </span>
                            <span class="price-yearly" style="display:none;">
                                <span class="fs-2 fw-bold">₦{{ number_format($pricing['yearly'] / 100) }}</span>
                                <span class="text-muted">/year</span>
                                <div class="text-muted fs-13 text-decoration-line-through">
                                    ₦{{ number_format($pricing['yearly_full_price'] / 100) }}
                                </div>
                            </span>
                        </div>

                        <p class="text-muted fs-13 mb-4">{{ $roomLabels[$tierKey] }} · Transaction fee {{ $feeLabels[$tierKey] }}</p>

                        <ul class="list-unstyled text-start fs-13 mb-4 flex-grow-1">
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> Booking &amp; check-in/out</li>
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> Virtual account payments</li>
                            @if($tierKey !== 'starter')
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> Housekeeping &amp; room service</li>
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> SMS notifications</li>
                            @endif
                            @if($tierKey === 'pro')
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> Multi-location dashboard</li>
                            <li class="mb-2"><i class="feather-check-circle text-success me-2"></i> API access</li>
                            @endif
                        </ul>

                        <form action="{{ route('hotel.subscription.checkout.start') }}" method="GET" class="plan-form">
                            <input type="hidden" name="tier" value="{{ $tierKey }}">
                            <input type="hidden" name="billing_cycle" class="billing-cycle-input" value="monthly">
                            <button type="submit" class="btn {{ $highlight ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                Choose {{ $labels[$tierKey] }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <p class="text-muted fs-13">
                Running multiple locations or need a custom setup?
                <a href="mailto:sales@africstayhms.com" class="auth-link">Talk to us about Enterprise</a>
            </p>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('#billingToggle button');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cycle = btn.dataset.cycle;

            document.querySelectorAll('.billing-cycle-input').forEach(input => input.value = cycle);
            document.querySelectorAll('.price-monthly').forEach(el => el.style.display = cycle === 'monthly' ? 'inline' : 'none');
            document.querySelectorAll('.price-yearly').forEach(el => el.style.display = cycle === 'yearly' ? 'inline' : 'none');
        });
    });
});
</script>
@include('layouts.partials.auth-footer')
@endsection
