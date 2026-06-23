<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('hotel.dashboard') }}" class="b-brand">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     alt="AfricStay"
                     class="logo logo-lg"
                     style="width:140px;height:auto;display:block;margin:0 auto;" />
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     alt="" class="logo logo-sm" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="nxl-navbar">

                <li class="nxl-item nxl-caption">
                    <label>{{ auth()->user()->hotel->name ?? 'AfricStay' }}</label>
                </li>

                <li class="nxl-item {{ request()->routeIs('hotel.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('hotel.dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                @php $role = auth()->user()->role; @endphp

                @if(in_array($role, ['owner','manager','receptionist']))
                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-home"></i></span>
                        <span class="nxl-mtext">Rooms</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">Room Status Board</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Manage Rooms</a></li>
                    </ul>
                </li>

                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-calendar"></i></span>
                        <span class="nxl-mtext">Bookings</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">All Bookings</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">New Walk-in Booking</a></li>
                    </ul>
                </li>
                @endif

                @if(in_array($role, ['owner','manager','cashier','accountant']))
                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-credit-card"></i></span>
                        <span class="nxl-mtext">Payments &amp; Wallet</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">Wallet</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Payments</a></li>
                        @if($role === 'owner')
                        <li class="nxl-item"><a class="nxl-link" href="#">Withdrawals</a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if(in_array($role, ['owner','manager','room_service']))
                <li class="nxl-item {{ request()->routeIs('hotel.room-service*') ? 'active' : '' }}">
                    <a href="#" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-coffee"></i></span>
                        <span class="nxl-mtext">Room Service</span>
                    </a>
                </li>
                @endif

                @if(in_array($role, ['owner','manager','housekeeper']))
                <li class="nxl-item {{ request()->routeIs('hotel.housekeeping*') ? 'active' : '' }}">
                    <a href="#" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-clipboard"></i></span>
                        <span class="nxl-mtext">Housekeeping</span>
                    </a>
                </li>
                @endif

                @if(in_array($role, ['owner','manager']))
                <li class="nxl-item {{ request()->routeIs('hotel.staff*') ? 'active' : '' }}">
                    <a href="#" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-users"></i></span>
                        <span class="nxl-mtext">Staff</span>
                    </a>
                </li>
                @endif

                @if(in_array($role, ['owner','manager','accountant']))
                <li class="nxl-item {{ request()->routeIs('hotel.reports*') ? 'active' : '' }}">
                    <a href="#" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
                        <span class="nxl-mtext">Reports</span>
                    </a>
                </li>
                @endif

                <li class="nxl-item nxl-caption">
                    <label>Account</label>
                </li>

                @if($role === 'owner')
                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('hotel.settings*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-settings"></i></span>
                        <span class="nxl-mtext">Settings</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="#">Hotel Profile</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="#">Subscription &amp; Tier</a></li>
                    </ul>
                </li>
                @endif

                <li class="nxl-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="nxl-link border-0 bg-transparent w-100 text-start">
                            <span class="nxl-micon">
                                <i class="feather-power text-danger"></i>
                            </span>
                            <span class="nxl-mtext text-danger">Logout</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div>
    </div>
</nav>