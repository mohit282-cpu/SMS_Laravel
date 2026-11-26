document.addEventListener('DOMContentLoaded', function() {

    if (typeof $ !== 'undefined' && $.fn && $.fn.datepicker) {
        $('.date-pick').datepicker();
    }

    // 1. Scroll-aware Liquid Glass Navbar Depth (rAF throttled)
    var navbar = document.querySelector('.sms-navbar');
    if (navbar) {
        var scrollTicking = false;
        var syncNavbarScroll = function() {
            if (window.scrollY > 12) {
                navbar.classList.add('sms-navbar-scrolled');
            } else {
                navbar.classList.remove('sms-navbar-scrolled');
            }
            scrollTicking = false;
        };
        syncNavbarScroll();
        window.addEventListener('scroll', function() {
            if (!scrollTicking) {
                scrollTicking = true;
                requestAnimationFrame(syncNavbarScroll);
            }
        }, { passive: true });
    }

    // 2. Doherty Threshold: Instant Top Progress Bar on Navigation & Form Submit
    var dohertyBar = document.createElement('div');
    dohertyBar.id = 'sms-doherty-bar';
    document.body.appendChild(dohertyBar);

    var triggerDohertyBar = function() {
        dohertyBar.classList.add('active');
    };

    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href') || '';
        if (
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            link.getAttribute('target') === '_blank' ||
            link.hasAttribute('download') ||
            link.dataset.toggle ||
            e.metaKey || e.ctrlKey || e.shiftKey
        ) {
            return;
        }
        triggerDohertyBar();
    });

    // 3. Origin Awareness: Set Modal Transform Origin from Trigger Element
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-toggle="modal"], [data-bs-toggle="modal"]');
        if (!trigger) return;
        var targetSelector = trigger.getAttribute('data-target') || trigger.getAttribute('href');
        if (!targetSelector || !targetSelector.startsWith('#')) return;
        var modal = document.querySelector(targetSelector);
        if (!modal) return;
        var dialog = modal.querySelector('.modal-dialog');
        if (!dialog) return;
        var rect = trigger.getBoundingClientRect();
        var ox = Math.round(((rect.left + rect.width / 2) / window.innerWidth) * 100);
        var oy = Math.round(((rect.top + rect.height / 2) / window.innerHeight) * 100);
        dialog.style.setProperty('--sms-modal-origin-x', ox + '%');
        dialog.style.setProperty('--sms-modal-origin-y', oy + '%');
    });

    // 4. Rubber Banding: Mobile Dock Bounce on Tap (Interruptible, rAF without sync reflow)
    document.querySelectorAll('.sms-dock-item').forEach(function(item) {
        item.addEventListener('click', function() {
            item.classList.remove('sms-rubber-bounce');
            requestAnimationFrame(function() {
                item.classList.add('sms-rubber-bounce');
            });
        });
    });

    // 5. Doherty Threshold: Instant Tactile Loading Feedback on Standard Form Submits
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.dataset.noLoading === 'true' || form.getAttribute('target') === '_blank') {
            return;
        }
        setTimeout(function() {
            if (e.defaultPrevented) {
                return;
            }
            triggerDohertyBar();
            var submitBtn = form.querySelector('button[type="submit"]:not(:disabled), input[type="submit"]:not(:disabled)');
            if (submitBtn && !submitBtn.classList.contains('sms-btn-loading')) {
                submitBtn.classList.add('sms-btn-loading');
            }
        }, 0);
    });

    // Reset loading states & progress bar if restored from browser back-forward cache
    window.addEventListener('pageshow', function() {
        dohertyBar.classList.remove('active');
        document.querySelectorAll('.sms-btn-loading').forEach(function(btn) {
            btn.classList.remove('sms-btn-loading');
        });
    });

    // 6. Peak-End Rule: Smooth Auto-Dismiss for Success Flash Alerts (paused on hover/focus)
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(function(alertEl) {
        var timer = null;
        var startDismissTimer = function() {
            timer = setTimeout(function() {
                if (typeof $ !== 'undefined' && $(alertEl).alert) {
                    $(alertEl).alert('close');
                } else {
                    alertEl.style.opacity = '0';
                    setTimeout(function() { alertEl.remove(); }, 220);
                }
            }, 6000);
        };
        startDismissTimer();
        alertEl.addEventListener('mouseenter', function() { clearTimeout(timer); });
        alertEl.addEventListener('mouseleave', startDismissTimer);
    });

});
