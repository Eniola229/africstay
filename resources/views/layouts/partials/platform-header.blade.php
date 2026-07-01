<header class="nxl-header">
    <div class="header-wrapper">

        <div class="header-left d-flex align-items-center">
            <a href="javascript:void(0);" class="mobile-toggle-icon" id="mobile-collapse">
                <i class="feather-menu"></i>
            </a>
        </div>

        <div class="header-right ms-auto">
            <ul class="nxl-h-item d-flex align-items-center list-unstyled mb-0 gap-2">

                <li class="nxl-h-item">
                    <span class="text-capitalize badge bg-secondary">{{ $role }}</span>
                </li>

                <li class="nxl-h-item nxl-h-dropdown d-flex">
                    <a href="javascript:void(0);" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                              style="width:34px;height:34px;background:#0a3622;color:#fff;font-size:13px;">
                            {{ strtoupper(substr(auth('platform')->user()->name, 0, 1)) }}
                        </span>
                        <span class="d-none d-sm-inline text-dark fw-semibold">{{ auth('platform')->user()->name }}</span>
                        <i class="feather-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="px-3 py-2 border-bottom">
                            <p class="mb-0 fw-bold">{{ auth('platform')->user()->name }}</p>
                            <small class="text-muted text-capitalize">{{ $role }}</small>
                        </div>
                        <form method="POST" action="{{ route('platform.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="feather-power me-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </li>

            </ul>
        </div>

    </div>
</header>