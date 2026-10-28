/**
 * Apex Model Secondary School (Estd. 2052 B.S.)
 * Client Application Logic & UI Interactions
 */

document.addEventListener('DOMContentLoaded', () => {

    // 0. Mobile Bottom Dock - Cult UI AnimatedBackground Hover Tab Engine
    const mobileDock = document.querySelector('.mobile-bottom-dock');
    if (mobileDock) {
        const dockItems = mobileDock.querySelector('.dock-items');
        if (dockItems) {
            // Ensure animated background tab highlight element exists
            let tabBg = dockItems.querySelector('.dock-tab-bg');
            if (!tabBg) {
                tabBg = document.createElement('div');
                tabBg.className = 'dock-tab-bg';
                dockItems.appendChild(tabBg);
            }

            const links = dockItems.querySelectorAll('.dock-item a');
            const activeLink = dockItems.querySelector('.dock-item a.active') || links[0];

            function positionTabBg(target) {
                if (!target || !tabBg || !dockItems) return;
                const targetRect = target.getBoundingClientRect();
                const parentRect = dockItems.getBoundingClientRect();

                const left = targetRect.left - parentRect.left;
                const top = targetRect.top - parentRect.top;
                const width = targetRect.width;
                const height = targetRect.height;

                tabBg.style.transform = `translate3d(${left}px, ${top}px, 0)`;
                tabBg.style.width = `${width}px`;
                tabBg.style.height = `${height}px`;
                tabBg.style.opacity = '1';
            }

            // Position initial active tab background
            setTimeout(() => positionTabBg(activeLink), 60);
            window.addEventListener('resize', () => {
                const currentActive = dockItems.querySelector('.dock-item a.active') || activeLink;
                positionTabBg(currentActive);
            });

            // Tab hover & touch interaction (AnimatedBackground spring slide)
            links.forEach(link => {
                link.addEventListener('mouseenter', () => positionTabBg(link));
                link.addEventListener('focus', () => positionTabBg(link));
                link.addEventListener('click', () => {
                    links.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    positionTabBg(link);
                });
            });

            // Smooth return to active page tab when hover leaves dock
            dockItems.addEventListener('mouseleave', () => {
                const currentActive = dockItems.querySelector('.dock-item a.active') || activeLink;
                positionTabBg(currentActive);
            });
        }
    }

    // 1. Progressive Scroll Reveal Animation
    const scrollElements = document.querySelectorAll('.scroll-reveal');
    if (scrollElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.15 });

        scrollElements.forEach(el => revealObserver.observe(el));
    }

    // 2. Rolling Metrics Counter Animation
    const counterElements = document.querySelectorAll('.counter-val');
    let countersTriggered = false;

    function animateCounters() {
        counterElements.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'), 10);
            const duration = 1800; // ms
            const startTime = performance.now();

            function updateCount(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const currentVal = Math.floor(target * (1 - (1 - progress) * (1 - progress)));
                counter.textContent = currentVal.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(updateCount);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            }

            requestAnimationFrame(updateCount);
        });
    }

    const metricsSection = document.querySelector('.metrics-strip');
    if (metricsSection) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !countersTriggered) {
                countersTriggered = true;
                animateCounters();
            }
        }, { threshold: 0.3 });
        observer.observe(metricsSection);
    }

    // 3. Live Event Keyword Search Filtering on events.php
    const eventSearchInput = document.getElementById('eventSearchInput');
    const eventCards = document.querySelectorAll('.event-card-item');

    if (eventSearchInput && eventCards.length > 0) {
        eventSearchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            eventCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const desc = card.querySelector('p').textContent.toLowerCase();
                const cat = card.getAttribute('data-category').toLowerCase();

                if (!term || title.includes(term) || desc.includes(term) || cat.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 4. Notice Date / Keyword Filtering on notices.php
    const noticeSearchInput = document.getElementById('noticeSearchInput');
    const noticeMonthSelect = document.getElementById('noticeMonthSelect');
    const noticeCards = document.querySelectorAll('.notice-item-card');

    function filterNotices() {
        const term = noticeSearchInput ? noticeSearchInput.value.toLowerCase().trim() : '';
        const month = noticeMonthSelect ? noticeMonthSelect.value : '';

        noticeCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const desc = card.querySelector('p').textContent.toLowerCase();
            const dateStr = card.dataset.date || '';

            const matchesText = !term || title.includes(term) || desc.includes(term);
            const matchesMonth = !month || dateStr.includes(month);

            if (matchesText && matchesMonth) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (noticeSearchInput) noticeSearchInput.addEventListener('input', filterNotices);
    if (noticeMonthSelect) noticeMonthSelect.addEventListener('change', filterNotices);

    // 5. Global Modal Handlers
    window.openModal = function(title, contentHtml) {
        const backdrop = document.getElementById('modalBackdrop');
        const modal = document.getElementById('modalWindow');
        const modalBody = document.getElementById('modalBody');

        if (backdrop && modal && modalBody) {
            modalBody.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--primary-navy);">${title}</h2>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 1.6rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
                </div>
                <div>${contentHtml}</div>
            `;
            backdrop.classList.add('active');
            modal.classList.add('active');
        }
    };

    window.closeModal = function() {
        const backdrop = document.getElementById('modalBackdrop');
        const modal = document.getElementById('modalWindow');
        if (backdrop && modal) {
            backdrop.classList.remove('active');
            modal.classList.remove('active');
        }
    };
});
