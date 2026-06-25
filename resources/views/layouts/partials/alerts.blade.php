@if(session('success'))
    <div class="alert-africstay alert-success-africstay mb-3">
        <div class="container-fluid">
            <p>
                <i class="feather-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="alert-close-btn"
                        onclick="this.parentElement.parentElement.remove()">&times;</button>
            </p>
        </div>
    </div>
@endif

@if(session('warning'))
    <div class="alert-africstay alert-error-africstay mb-3">
        <div class="container-fluid">
            <p>
                <i class="feather-alert-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="alert-close-btn"
                        onclick="this.parentElement.parentElement.remove()">&times;</button>
            </p>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="alert-africstay alert-error-africstay mb-3">
        <div class="container-fluid">
            <p>
                <i class="feather-x-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="alert-close-btn"
                        onclick="this.parentElement.parentElement.remove()">&times;</button>
            </p>
        </div>
    </div>
@endif

@if(session('info'))
    <div class="alert-africstay alert-info-africstay mb-3">
        <div class="container-fluid">
            <p>
                <i class="feather-info me-2"></i>
                {{ session('info') }}
                <button type="button" class="alert-close-btn"
                        onclick="this.parentElement.parentElement.remove()">&times;</button>
            </p>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert-africstay alert-error-africstay mb-3">
        <div class="container-fluid">
            @foreach($errors->all() as $error)
                <p><i class="feather-alert-circle me-2"></i> {{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif

<style>
    .alert-africstay { padding: 12px 0; }
    .alert-africstay .container-fluid p {
        background: #fff;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .alert-success-africstay p { border-left: 4px solid #2ECC71; color: #1E8449; }
    .alert-error-africstay p { border-left: 4px solid #E74C3C; color: #C0392B; }
    .alert-info-africstay p { border-left: 4px solid #2980B9; color: #1F618D; }
    .alert-close-btn {
        background: none; border: none; margin-left: auto;
        font-size: 18px; line-height: 1; cursor: pointer; color: inherit; opacity: 0.6;
    }
    .alert-close-btn:hover { opacity: 1; }
</style>
