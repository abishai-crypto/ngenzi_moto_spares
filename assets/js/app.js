/**
 * NGENZI MOTO SPARE PARTS
 * Interactive JavaScript Library
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    // 2. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // 3. 30% Margin Calculator Utility (Available globally for forms)
    window.calculateMargin = function(costInputId, sellingInputId, resultContainerId) {
        const costEl = document.getElementById(costInputId);
        const sellingEl = document.getElementById(sellingInputId);
        const resEl = document.getElementById(resultContainerId);

        if (!costEl || !sellingEl || !resEl) return;

        const cost = parseFloat(costEl.value) || 0;
        const selling = parseFloat(sellingEl.value) || 0;

        if (cost <= 0 || selling <= 0) {
            resEl.innerHTML = `<span class="text-muted">Weka bei ya kununua na kuuza kuona faida na asilimia.</span>`;
            return;
        }

        const grossProfit = selling - cost;
        const marginPercent = (grossProfit / selling) * 100;
        const recommended30 = Math.ceil((cost / 0.70) / 100) * 100;

        let badgeClass = 'badge-margin-normal';
        let statusIcon = 'bi-check-circle-fill text-success';
        let statusText = 'Salama (Margin >= 30%)';

        if (marginPercent < 20.0) {
            badgeClass = 'badge-margin-critical';
            statusIcon = 'bi-exclamation-octagon-fill text-danger';
            statusText = 'ONYO KUBWA: Bei iko chini ya 20% margin!';
        } else if (marginPercent < 30.0) {
            badgeClass = 'badge-margin-warning';
            statusIcon = 'bi-exclamation-triangle-fill text-warning';
            statusText = 'TAHADHARI: Chini ya lengo la 30%';
        }

        resEl.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-3 rounded bg-light border">
                <div>
                    <div><strong>Faida ya Jumla (Gross Profit):</strong> TSh ${grossProfit.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    <div class="small text-muted">Bei Iliyopendekezwa (30% Margin): <strong>TSh ${recommended30.toLocaleString('en-US')}</strong></div>
                </div>
                <div class="text-end">
                    <span class="${badgeClass}"><i class="bi ${statusIcon} me-1"></i> ${marginPercent.toFixed(2)}% Margin</span>
                    <div class="small mt-1 text-muted">${statusText}</div>
                </div>
            </div>
        `;
    };
});
