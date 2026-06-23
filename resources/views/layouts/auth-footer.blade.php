<div class="support-float-btn" id="supportBtn">
    <i class="feather-headphones"></i>
    <span>Support</span>
</div>

<div class="support-modal" id="supportModal">
    <div class="support-modal-content">
        <div class="support-modal-header">
            <h5>Contact AfricStay Support</h5>
            <button class="support-close" id="closeModal">&times;</button>
        </div>
        <div class="support-modal-body">
            <p class="text-muted mb-4">How would you like to reach us?</p>
            <div class="support-options">
                <a href="mailto:support@africstayhms.com" class="support-option">
                    <div class="support-option-icon">
                        <i class="feather-mail"></i>
                    </div>
                    <div class="support-option-content">
                        <h6>Email Support</h6>
                        <p>support@africstayhms.com</p>
                    </div>
                </a>
                <a href="https://wa.me/2348000000000" target="_blank" class="support-option">
                    <div class="support-option-icon whatsapp">
                        <i class="feather-message-square"></i>
                    </div>
                    <div class="support-option-content">
                        <h6>WhatsApp</h6>
                        <p>Chat with us on WhatsApp</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .support-float-btn {
        position: fixed; bottom: 30px; right: 30px;
        background: var(--bs-primary, #2ECC71); color: white;
        padding: 12px 20px; border-radius: 50px; cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 8px;
        font-size: 14px; font-weight: 600; transition: all 0.3s ease; z-index: 1000;
    }
    .support-float-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
    .support-float-btn i { font-size: 20px; }

    .support-modal {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 1050;
    }
    .support-modal.active { display: flex; align-items: center; justify-content: center; }
    .support-modal-content {
        background: white; border-radius: 12px; width: 90%; max-width: 450px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .support-modal-header {
        padding: 20px 24px; border-bottom: 1px solid #e9ecef;
        display: flex; justify-content: space-between; align-items: center;
    }
    .support-modal-header h5 { margin: 0; font-size: 18px; font-weight: 600; color: #212529; }
    .support-close {
        background: none; border: none; font-size: 28px; line-height: 1; color: #6c757d;
        cursor: pointer; padding: 0; width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center; border-radius: 4px;
    }
    .support-close:hover { background: #f8f9fa; color: #212529; }
    .support-modal-body { padding: 24px; }
    .support-options { display: flex; flex-direction: column; gap: 12px; }
    .support-option {
        display: flex; align-items: center; gap: 16px; padding: 16px;
        border: 2px solid #e9ecef; border-radius: 8px; text-decoration: none;
        color: #212529; transition: all 0.3s ease;
    }
    .support-option:hover {
        border-color: var(--bs-primary, #2ECC71); background: #f3fcf7;
        transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46, 204, 113, 0.12);
    }
    .support-option-icon {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--bs-primary, #2ECC71); color: white;
        display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
    }
    .support-option-icon.whatsapp { background: #25D366; }
    .support-option-content h6 { margin: 0 0 4px 0; font-size: 16px; font-weight: 600; }
    .support-option-content p { margin: 0; font-size: 13px; color: #6c757d; }

    @media (max-width: 767.98px) {
        .support-float-btn { bottom: 20px; right: 20px; padding: 10px 16px; font-size: 13px; }
        .support-float-btn span { display: none; }
        .support-modal-content { width: 95%; margin: 0 10px; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supportBtn   = document.getElementById('supportBtn');
    const supportModal = document.getElementById('supportModal');
    const closeModal   = document.getElementById('closeModal');
    if (supportBtn) {
        supportBtn.addEventListener('click',  () => supportModal.classList.add('active'));
        closeModal.addEventListener('click',  () => supportModal.classList.remove('active'));
        supportModal.addEventListener('click', e => {
            if (e.target === supportModal) supportModal.classList.remove('active');
        });
    }
});
</script>