<header class="nxl-header">
    <div class="header-wrapper">

        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display:none;"><i class="feather-arrow-right"></i></a>
            </div>
            @if(auth()->user()->hotel && auth()->user()->hotel->subscription_status === 'past_due')
            <span class="badge bg-warning text-dark d-none d-md-inline-flex align-items-center gap-1">
                <i class="feather-alert-triangle" style="font-size:12px;"></i>
                Subscription expiring — <a href="{{ route('hotel.subscription.plans') }}" class="text-dark fw-bold ms-1">Renew now</a>
            </span>
            @endif
        </div>

        <div class="header-right ms-auto">
            <div class="d-flex align-items-center">

                <div class="nxl-h-item d-none d-md-flex me-2">
                    <a href="{{ route('hotel.subscription.plans') }}"
                       class="btn btn-sm"
                       style="background:#D5F5E3;color:#1E8449;font-weight:700;border:none;">
                        <i class="feather-dollar-sign me-1" style="font-size:13px;"></i>
                        ₦{{ number_format(auth()->user()->hotel->walletBalanceNaira(), 2) }}
                    </a>
                </div>

                <div class="nxl-h-item d-none d-sm-flex">
                    <div class="full-screen-switcher">
                        <a href="javascript:void(0);" class="nxl-head-link me-0" onclick="$('body').fullScreenHelper('toggle');">
                            <i class="feather-maximize maximize"></i>
                            <i class="feather-minimize minimize"></i>
                        </a>
                    </div>
                </div>

                <div class="nxl-h-item dark-light-theme">
                    <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                    <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display:none;"><i class="feather-sun"></i></a>
                </div>

                <div class="dropdown nxl-h-item">
                    <a class="nxl-head-link me-3" data-bs-toggle="dropdown" href="#" data-bs-auto-close="outside">
                        <i class="feather-bell"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-notifications-menu">
                        <div class="d-flex justify-content-between align-items-center notifications-head">
                            <h6 class="fw-bold text-dark mb-0">Notifications</h6>
                        </div>
                        <div class="text-center py-4 text-muted">
                            <i class="feather-check-circle fs-3 mb-2 d-block"></i>
                            No new notifications
                        </div>
                    </div>
                </div>

                <div class="dropdown nxl-h-item">
                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                        <div class="img-fluid user-avtar me-0 d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;border-radius:50%;background:#2ECC71;color:#fff;font-weight:700;font-size:14px;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="user-avtar d-flex align-items-center justify-content-center"
                                     style="width:40px;height:40px;border-radius:50%;background:#2ECC71;color:#fff;font-weight:700;font-size:16px;flex-shrink:0;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <div class="ms-2">
                                    <h6 class="text-dark mb-0">{{ auth()->user()->name }}</h6>
                                    <span class="fs-12 fw-medium text-muted text-capitalize">{{ auth()->user()->role }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="#" class="dropdown-item">
                                <i class="feather-user"></i><span>Profile</span>
                            </a>
                            <a href="{{ route('hotel.subscription.plans') }}" class="dropdown-item">
                                <i class="feather-credit-card"></i><span>Subscription &amp; Billing</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item border-0 w-100 text-start text-danger">
                                    <i class="feather-log-out"></i><span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</header>
