@extends('layouts.hotel')
@section('title', 'Withdrawals')
@section('page_title', 'Withdrawals')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.wallet.index') }}">Wallet</a></li>
    <li class="breadcrumb-item active">Withdrawals</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Request a Withdrawal</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13">
                    Available balance: <strong>₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</strong>
                </p>

                <form action="{{ route('hotel.wallet.withdrawals.store') }}" method="POST" id="withdrawalForm">
                    @csrf

                    {{-- Hidden — carries the resolved bank_code and account_name --}}
                    <input type="hidden" name="bank_code"     id="bank_code_hidden">
                    <input type="hidden" name="account_name"  id="account_name_hidden">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₦)</label>
                        <input type="number" name="amount" id="amountInput" min="10000"
                               class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Minimum ₦10,000</small>
                    </div>

                    {{-- Bank selector — populated via JS --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Bank</label>
                        <select id="bankSelect"
                                name="bank_name"
                                class="form-select @error('bank_name') is-invalid @enderror"
                                required
                                disabled>
                            <option value="">Loading banks…</option>
                        </select>
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div id="bankLoadError" class="text-danger fs-13 mt-1 d-none">
                            Could not load banks. <a href="#" onclick="loadBanks();return false;">Retry</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Account number</label>
                        <div class="input-group">
                            <input type="text" id="accountNumberInput"
                                   name="account_number"
                                   maxlength="10"
                                   class="form-control @error('account_number') is-invalid @enderror"
                                   placeholder="10-digit NUBAN"
                                   required>
                            <button type="button" class="btn btn-outline-secondary" id="verifyBtn"
                                    onclick="verifyAccount()" disabled>
                                Verify
                            </button>
                        </div>
                        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Auto-filled after verification --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">Account name</label>
                        <div class="input-group">
                            <input type="text" id="accountNameDisplay"
                                   class="form-control bg-light"
                                   placeholder="Verified automatically"
                                   readonly>
                            <span class="input-group-text" id="verifyStatus"></span>
                        </div>
                        <div id="verifyError" class="text-danger fs-13 mt-1 d-none"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                        <i class="feather-arrow-up-right me-1"></i> Request Withdrawal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Withdrawal History</h5></div>
            <div class="card-body p-0">
                @if($withdrawals->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Bank</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $w)
                            <tr>
                                <td class="fw-bold">₦{{ number_format($w->amountNaira(), 2) }}</td>
                                <td>{{ $w->account_number }} ({{ $w->bank_name }})</td>
                                <td>
                                    <span class="badge {{ match($w->status) {
                                        'completed'  => 'bg-success',
                                        'processing' => 'bg-info text-white',
                                        'pending'    => 'bg-secondary',
                                        default      => 'bg-danger'
                                    } }}">
                                        {{ ucfirst($w->status) }}
                                    </span>
                                    @if($w->status === 'failed' && $w->failure_reason)
                                    <div class="text-muted fs-12">{{ $w->failure_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-muted fs-13">{{ $w->created_at->format('d M Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $withdrawals->links() }}</div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="feather-arrow-up-right mb-2 d-block" style="font-size:36px;"></i>
                    No withdrawals yet.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * All Flutterwave API calls are proxied through your own backend to keep the
 * secret key off the frontend. Add these two routes to web.php (hotel group):
 *
 *   GET  /wallet/banks              → WalletController::listBanks()
 *   GET  /wallet/verify-account     → WalletController::verifyAccount()
 *
 * See the WalletController additions in the fix notes.
 */
const BANKS_URL  = "{{ route('hotel.wallet.banks') }}";
const VERIFY_URL = "{{ route('hotel.wallet.verify-account') }}";

let banks      = [];   // [{ name, code }]
let verified   = false;

// ── On load: fetch bank list ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadBanks);

function loadBanks() {
    const sel      = document.getElementById('bankSelect');
    const errDiv   = document.getElementById('bankLoadError');
    sel.disabled   = true;
    sel.innerHTML  = '<option value="">Loading banks…</option>';
    errDiv.classList.add('d-none');

    fetch(BANKS_URL)
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(data => {
            banks = data; // [{ name, code }]
            sel.innerHTML = '<option value="">— Select your bank —</option>'
                + banks.map(b => `<option value="${escHtml(b.name)}" data-code="${escHtml(b.code)}">${escHtml(b.name)}</option>`).join('');
            sel.disabled = false;
        })
        .catch(() => {
            sel.innerHTML = '<option value="">Could not load banks</option>';
            errDiv.classList.remove('d-none');
        });
}

// ── When bank or account number changes, reset verification ───────────────────
document.getElementById('bankSelect').addEventListener('change', resetVerification);
document.getElementById('accountNumberInput').addEventListener('input', function () {
    resetVerification();
    // Auto-trigger verify once 10 digits are entered and a bank is selected
    if (this.value.replace(/\D/g,'').length === 10 && getSelectedBank()) {
        verifyAccount();
    }
});

function getSelectedBank() {
    const sel  = document.getElementById('bankSelect');
    const opt  = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset.code) return null;
    return { name: opt.value, code: opt.dataset.code };
}

function resetVerification() {
    verified = false;
    document.getElementById('accountNameDisplay').value  = '';
    document.getElementById('account_name_hidden').value = '';
    document.getElementById('bank_code_hidden').value    = '';
    document.getElementById('verifyStatus').innerHTML    = '';
    document.getElementById('verifyError').classList.add('d-none');
    document.getElementById('submitBtn').disabled = true;
    updateVerifyBtnState();
}

function updateVerifyBtnState() {
    const bank   = getSelectedBank();
    const accNum = document.getElementById('accountNumberInput').value.trim();
    document.getElementById('verifyBtn').disabled = !(bank && accNum.length >= 10);
}

// ── Verify account via backend proxy ─────────────────────────────────────────
function verifyAccount() {
    const bank   = getSelectedBank();
    const accNum = document.getElementById('accountNumberInput').value.trim();
    if (!bank || accNum.length < 10) return;

    const btn        = document.getElementById('verifyBtn');
    const statusSpan = document.getElementById('verifyStatus');
    const errDiv     = document.getElementById('verifyError');
    const nameInput  = document.getElementById('accountNameDisplay');

    btn.disabled     = true;
    btn.textContent  = '…';
    statusSpan.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary"></span>';
    errDiv.classList.add('d-none');
    nameInput.value  = '';

    const params = new URLSearchParams({ account_number: accNum, bank_code: bank.code });

    fetch(`${VERIFY_URL}?${params}`)
        .then(r => r.json())
        .then(data => {
            btn.textContent = 'Verify';
            if (data.success && data.account_name) {
                nameInput.value = data.account_name;
                document.getElementById('account_name_hidden').value = data.account_name;
                document.getElementById('bank_code_hidden').value    = bank.code;
                statusSpan.innerHTML = '<span class="text-success fw-bold">✓</span>';
                verified = true;
                document.getElementById('submitBtn').disabled = false;
            } else {
                statusSpan.innerHTML = '<span class="text-danger">✗</span>';
                errDiv.textContent   = data.message ?? 'Could not verify this account. Check the number and try again.';
                errDiv.classList.remove('d-none');
                btn.disabled = false;
            }
        })
        .catch(() => {
            btn.textContent  = 'Verify';
            btn.disabled     = false;
            statusSpan.innerHTML = '<span class="text-danger">✗</span>';
            errDiv.textContent   = 'Network error — please try again.';
            errDiv.classList.remove('d-none');
        });
}

// ── Guard: don't submit without verified account ──────────────────────────────
document.getElementById('withdrawalForm').addEventListener('submit', function (e) {
    if (!verified) {
        e.preventDefault();
        document.getElementById('verifyError').textContent = 'Please verify your account number before submitting.';
        document.getElementById('verifyError').classList.remove('d-none');
    }
});

function escHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
</script>
@endpush