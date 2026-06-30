@extends('layouts.auth')
@section('title', 'Choose Your Plan')
@section('content')
<style>
.plans-grid {
    grid-template-columns: 1fr;
}
@media (min-width: 768px) {
    .plans-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.current-plan-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #2ECC71;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
</style>
<div class="auth-main" style="display:block; padding:20px 15px;">

    <div style="max-width:1100px; margin:0 auto;">

        <div class="text-center mb-4" style="margin-bottom: 24px;">
            <img src="{{ asset('ashboard/assets/images/favicon.png') }}" style="height:40px;" alt="AfricStay">
            <h2 class="fw-bold mt-3 mb-2" style="font-size: clamp(20px, 5vw, 28px);">Choose a plan for {{ $hotel->name }}</h2>
            <p class="text-muted" style="font-size: clamp(13px, 4vw, 15px);">
                @if($subscriptionStatus === 'pending_payment')
                    One more step — activate your account to unlock rooms, bookings and payments.
                @elseif($subscriptionStatus === 'active')
                    Your current plan: <strong class="text-capitalize">{{ $currentTier }}</strong>
                    @if($subscriptionEndsAt)
                        — renews/expires {{ $subscriptionEndsAt->format('jS M Y') }}
                    @endif
                @endif
            </p>
        </div>

        {{-- Billing cycle toggle --}}
        <div style="display: flex; justify-content: center; margin-bottom: 32px; padding: 0 10px;">
            <div id="billingToggle" style="border: 1px solid #2ECC71; border-radius: 8px; overflow: hidden; background: white; display: flex; width: 100%; max-width: 400px;">
                <button type="button" class="btn active" data-cycle="monthly" style="flex: 1; background: #2ECC71; color: white; border: none; padding: 12px 16px; font-weight: 600; border-radius: 0; font-size: 14px; cursor: pointer;">Monthly</button>
                <button type="button" class="btn" data-cycle="yearly" style="flex: 1; background: white; color: #2ECC71; border: none; border-left: 1px solid #2ECC71; padding: 12px 16px; font-weight: 600; font-size: 14px; cursor: pointer;">
                    Yearly <span class="badge ms-1" style="background: #2ECC71; color: white; font-size: 11px;">Save 20%</span>
                </button>
            </div>
        </div>

        <div class="plans-grid" style="display: grid; gap: 20px; margin-bottom: 32px;">
            @foreach($tiers as $tierKey => $pricing)
            @php
                $labels = ['starter' => 'Starter', 'growth' => 'Growth', 'pro' => 'Pro'];
                $feeLabels = ['starter' => '1.5%', 'growth' => '1.0%', 'pro' => '0.75%'];
                $roomLabels = ['starter' => 'Up to 15 rooms', 'growth' => 'Up to 50 rooms', 'pro' => 'Unlimited rooms'];
                $highlight = $tierKey === 'growth';
                
                // Tier hierarchy for upgrades
                $tierOrder = ['starter' => 0, 'growth' => 1, 'pro' => 2];
                $isCurrentPlan = $subscriptionStatus === 'active' && $currentTier === $tierKey;
                $isUpgrade = $subscriptionStatus === 'active' && $currentTier && $tierOrder[$tierKey] > $tierOrder[$currentTier];
                $isDowngrade = $subscriptionStatus === 'active' && $currentTier && $tierOrder[$tierKey] < $tierOrder[$currentTier];
                
                // Determine button text and state
                if ($isCurrentPlan) {
                    $buttonText = 'Current Plan - Renew';
                    $buttonState = 'current';
                } elseif ($isUpgrade) {
                    $buttonText = 'Upgrade to ' . $labels[$tierKey];
                    $buttonState = 'upgrade';
                } elseif ($isDowngrade) {
                    $buttonText = 'Downgrade to ' . $labels[$tierKey];
                    $buttonState = 'downgrade';
                } else {
                    $buttonText = 'Choose ' . $labels[$tierKey];
                    $buttonState = 'choose';
                }
            @endphp
            <div style="border: 2px solid {{ $highlight && !$isCurrentPlan ? '#2ECC71' : ($isCurrentPlan ? '#2ECC71' : '#e5e7eb') }}; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; position: relative; {{ ($highlight && !$isCurrentPlan) || $isCurrentPlan ? 'box-shadow: 0 10px 30px rgba(46, 204, 113, 0.2);' : '' }}">
                @if($highlight && !$isCurrentPlan)
                <div class="text-center text-white fw-bold py-2" style="background: linear-gradient(135deg, #2ECC71, #27AE60); font-size: 12px; letter-spacing: 1px;">
                    MOST POPULAR
                </div>
                @elseif($isCurrentPlan)
                <div class="current-plan-badge">✓ ACTIVE</div>
                @endif
                <div style="padding: 24px 20px; text-align: center; flex: 1; display: flex; flex-direction: column;">
                    <h4 class="fw-bold" style="color: #0f172a; margin-bottom: 16px; font-size: clamp(18px, 5vw, 20px);">{{ $labels[$tierKey] }}</h4>

                    <div style="margin-bottom: 20px;">
                        <span class="price-monthly" style="display: block;">
                            <span style="font-size: clamp(24px, 8vw, 32px); font-weight: 700; color: #0f172a;">₦{{ number_format($pricing['monthly'] / 100) }}</span>
                            <span class="text-muted" style="font-size: 14px;">/month</span>
                        </span>
                        <span class="price-yearly" style="display:none;">
                            <span style="font-size: clamp(24px, 8vw, 32px); font-weight: 700; color: #0f172a;">₦{{ number_format($pricing['yearly'] / 100) }}</span>
                            <span class="text-muted" style="font-size: 14px;">/year</span>
                            <div class="text-muted" style="font-size: 12px; text-decoration: line-through; margin-top: 4px;">
                                ₦{{ number_format($pricing['yearly_full_price'] / 100) }}
                            </div>
                        </span>
                    </div>

                    <p class="text-muted" style="font-size: 12px; margin-bottom: 20px;">{{ $roomLabels[$tierKey] }} · Transaction fee {{ $feeLabels[$tierKey] }}</p>

                    <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 24px; flex-grow: 1; font-size: 13px;">
                        @if($tierKey === 'starter')
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Room management &amp; booking</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Check-in &amp; check-out</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Virtual account payment per guest</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Basic daily revenue report</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Online booking page</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Email support</li>
                            <li style="margin-bottom: 10px; color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> Housekeeping &amp; room service</li>
                            <li style="margin-bottom: 10px; color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> SMS notifications</li>
                            <li style="margin-bottom: 10px; color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> Multi-location dashboard</li>
                            <li style="color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> API access</li>
                        @elseif($tierKey === 'growth')
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Everything in Starter</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Housekeeping management</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Room service &amp; extras</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Role-based staff management</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Full financial reports</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> SMS notifications</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Withdraw to bank anytime</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Priority email &amp; WhatsApp support</li>
                            <li style="margin-bottom: 10px; color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> Multi-location dashboard</li>
                            <li style="color: #9ca3af;"><i class="feather-x" style="color: #d1d5db; margin-right: 8px;"></i> API access</li>
                        @elseif($tierKey === 'pro')
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Everything in Growth</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Multi-location revenue dashboard</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Accountant role</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Advanced analytics &amp; custom reports</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Branded booking page (logo + colors)</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> API access</li>
                            <li style="margin-bottom: 10px;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Dedicated account manager</li>
                            <li style="color: #2ECC71;"><i class="feather-check-circle" style="color: #2ECC71; margin-right: 8px;"></i> Phone support</li>
                        @endif
                    </ul>

                    <form action="{{ route('hotel.subscription.checkout.start') }}" method="GET" class="plan-form" style="width: 100%;">
                        <input type="hidden" name="tier" value="{{ $tierKey }}">
                        <input type="hidden" name="billing_cycle" class="billing-cycle-input" value="monthly">
                        <button type="submit" 
                            style="width: 100%; 
                                    background: {{ $isCurrentPlan ? '#E8F8F5' : ($highlight ? '#2ECC71' : 'white') }}; 
                                    color: {{ $isCurrentPlan ? '#2ECC71' : ($highlight ? 'white' : '#2ECC71') }}; 
                                    border: 2px solid #2ECC71; 
                                    font-weight: 600; 
                                    padding: 14px 20px; 
                                    border-radius: 8px; 
                                    cursor: pointer; 
                                    font-size: 14px; 
                                    transition: all 0.3s;"
                            {{ $isDowngrade ? 'disabled' : '' }}>
                            {{ $buttonText }}
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <p class="text-muted" style="font-size: 12px;">
                Running multiple locations or need a custom setup?
                <a href="#" class="auth-link" data-bs-toggle="modal" data-bs-target="#enterpriseModal" style="color: #2ECC71; font-weight: 600; text-decoration: none;">Talk to us about Enterprise</a>
            </p>
        </div>

    </div>
</div>

<div class="modal fade" id="enterpriseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tell us about your hotels</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('public.enterprise-inquiry.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Your name</label>
                        <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" required>
                        @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hotel / group name</label>
                        <input type="text" name="hotel_name" value="{{ $hotel->name }}" class="form-control @error('hotel_name') is-invalid @enderror" required>
                        @error('hotel_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="phone" value="{{ $hotel->phone }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tell us a bit about your setup</label>
                        <textarea name="message" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn" style="background: #2ECC71; color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 6px; cursor: pointer;">Send to AfricStay Sales</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('#billingToggle button');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => {
                b.style.background = 'white';
                b.style.color = '#2ECC71';
            });
            btn.style.background = '#2ECC71';
            btn.style.color = 'white';
            
            const cycle = btn.dataset.cycle;
            document.querySelectorAll('.billing-cycle-input').forEach(input => input.value = cycle);
            document.querySelectorAll('.price-monthly').forEach(el => el.style.display = cycle === 'monthly' ? 'block' : 'none');
            document.querySelectorAll('.price-yearly').forEach(el => el.style.display = cycle === 'yearly' ? 'block' : 'none');
        });
    });
});
</script>
@include('layouts.partials.auth-footer')
@endsection