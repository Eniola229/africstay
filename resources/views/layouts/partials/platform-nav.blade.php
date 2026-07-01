<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('platform.dashboard') }}" class="b-brand">
                <img src="{{ asset('ashboard/assets/images/favicon.png') }}"
                     alt="AfricStay"
                     class="logo logo-lg"
                     style="width:140px;height:auto;display:block;margin:0 auto;" />
                <img src="{{ asset('ashboard/assets/images/favicon.png') }}"
                     alt="" class="logo logo-sm" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="nxl-navbar">

                <li class="nxl-item nxl-caption">
                    <label>Main</label>
                </li>

                <li class="nxl-item {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('platform.dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                {{-- Hotels --}}
                @if(in_array($role, ['super_admin','operations','support','finance']))
                <li class="nxl-item nxl-caption">
                    <label>Management</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.hotels.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-home"></i></span>
                        <span class="nxl-mtext">Hotels</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index') }}">All Hotels</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index', ['subscription_status' => 'pending_payment']) }}">Pending Payment</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index', ['subscription_status' => 'past_due']) }}">Past Due</a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- Enterprise Inquiries --}}
                @if(in_array($role, ['super_admin','support','operations']))
                <li class="nxl-item {{ request()->routeIs('platform.inquiries.*') ? 'active' : '' }}">
                    <a href="{{ route('platform.inquiries.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-mail"></i></span>
                        <span class="nxl-mtext">Enterprise Inquiries</span>
                    </a>
                </li>
                @endif

                {{-- Revenue --}}
                @if(in_array($role, ['super_admin','finance']))
                <li class="nxl-item nxl-caption">
                    <label>Revenue</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.revenue.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                        <span class="nxl-mtext">Finance</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.revenue.index') }}">Revenue Reports</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.revenue.withdrawals') }}">Withdrawal Oversight</a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- Settings / Admin --}}
                @if($role === 'super_admin')
                <li class="nxl-item nxl-caption">
                    <label>Administration</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.admins.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-shield"></i></span>
                        <span class="nxl-mtext">Platform Admins</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.admins.index') }}">All Admins</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.admins.activity-log') }}">Activity Log</a>
                        </li>
                    </ul>
                </li>
                @endif

            </ul>
        </div>
    </div>
</nav>