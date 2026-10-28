/**
 * Apex Model Secondary School (Estd. 2052 B.S.)
 * Client Application Logic & UI Interactions
 */

document.addEventListener('DOMContentLoaded', () => {

    // 0a. Desktop Navigation - Cult UI AnimatedBackground SegmentedControl Pill Engine
    const mainNavList = document.querySelector('.main-nav-list');
    if (mainNavList) {
        let navTabBg = mainNavList.querySelector('.nav-tab-bg');
        if (!navTabBg) {
            navTabBg = document.createElement('div');
            navTabBg.className = 'nav-tab-bg';
            mainNavList.appendChild(navTabBg);
        }

        const navLinks = mainNavList.querySelectorAll('.main-nav-link');
        const activeNavLink = mainNavList.querySelector('.main-nav-link.active') || navLinks[0];

        function positionNavTabBg(target) {
            if (!target || !navTabBg || !mainNavList) return;
            const targetRect = target.getBoundingClientRect();
            const parentRect = mainNavList.getBoundingClientRect();

            const left = targetRect.left - parentRect.left;
            const top = targetRect.top - parentRect.top;
            const width = targetRect.width;
            const height = targetRect.height;

            navTabBg.style.transform = `translate3d(${left}px, ${top}px, 0)`;
            navTabBg.style.width = `${width}px`;
            navTabBg.style.height = `${height}px`;
            navTabBg.style.opacity = '1';

            navLinks.forEach(link => {
                if (link === target) {
                    link.classList.add('nav-pill-active');
                } else {
                    link.classList.remove('nav-pill-active');
                }
            });
        }

        // Initial active tab positioning
        if (activeNavLink) {
            setTimeout(() => positionNavTabBg(activeNavLink), 50);
            window.addEventListener('load', () => positionNavTabBg(activeNavLink));
            window.addEventListener('resize', () => {
                const currentActive = mainNavList.querySelector('.main-nav-link.active') || activeNavLink;
                positionNavTabBg(currentActive);
            });
        }

        // Click-driven sliding transition (liquid glass pill glides on click/switch, NOT cursor hover)
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                positionNavTabBg(link);
            });
        });
    }

    // 0b. Mobile Bottom Dock - Auto-Collapse to Icons (Expands with Text Below on Navbar Interaction)
    const mobileDock = document.querySelector('.mobile-bottom-dock');
    if (mobileDock) {
        const dockItems = mobileDock.querySelector('.dock-items');
        if (dockItems) {
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

            let collapseTimer = null;

            function expandDock() {
                mobileDock.classList.add('is-expanded');
                setTimeout(() => {
                    const current = dockItems.querySelector('.dock-item a.active') || activeLink;
                    positionTabBg(current);
                }, 40);

                if (collapseTimer) clearTimeout(collapseTimer);
                // Auto-collapse back to icon mode after 3.5s of no interaction on the dock
                collapseTimer = setTimeout(() => {
                    collapseDock();
                }, 3500);
            }

            function collapseDock() {
                if (mobileDock.classList.contains('is-expanded')) {
                    mobileDock.classList.remove('is-expanded');
                    setTimeout(() => {
                        const current = dockItems.querySelector('.dock-item a.active') || activeLink;
                        positionTabBg(current);
                    }, 40);
                }
            }

            // Position initial active tab background in compact icon mode
            setTimeout(() => positionTabBg(activeLink), 60);
            window.addEventListener('load', () => {
                const currentActive = dockItems.querySelector('.dock-item a.active') || activeLink;
                positionTabBg(currentActive);
            });
            window.addEventListener('resize', () => {
                const currentActive = dockItems.querySelector('.dock-item a.active') || activeLink;
                positionTabBg(currentActive);
            });

            // Enlarge and reveal text below icons when hovering or interacting with the navbar
            mobileDock.addEventListener('touchstart', expandDock, { passive: true });
            mobileDock.addEventListener('mouseenter', expandDock);
            mobileDock.addEventListener('pointerenter', expandDock);
            mobileDock.addEventListener('mouseleave', collapseDock);
            mobileDock.addEventListener('pointerleave', collapseDock);

            // Tab click & touch switching
            links.forEach(link => {
                link.addEventListener('click', () => {
                    expandDock();
                    links.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    positionTabBg(link);
                });
            });

            // When interacting with page content (scrolling or touching the page): collapse to icons only!
            window.addEventListener('scroll', () => {
                collapseDock();
            }, { passive: true });

            document.addEventListener('touchstart', (e) => {
                if (!mobileDock.contains(e.target)) {
                    collapseDock();
                }
            }, { passive: true });
        }
    }

    // 1. Cult UI Directional ScrollAnimation System (Reversible on Scroll, Optimized for Mobile & Desktop)
    const animElements = document.querySelectorAll('.scroll-anim, .scroll-reveal');
    if (animElements.length > 0) {
        const isMobile = window.innerWidth <= 768;
        const animThreshold = isMobile ? 0.05 : 0.12;
        const animMargin = isMobile ? '0px 0px -10px 0px' : '0px 0px -25px 0px';

        const animObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view', 'visible');
                } else {
                    // Reversible animation: when scrolled out of view, remove in-view
                    // so it smoothly reverses back to its directional offset
                    entry.target.classList.remove('in-view', 'visible');
                }
            });
        }, { threshold: animThreshold, rootMargin: animMargin });

        animElements.forEach(el => {
            // Check initial position on page load: if in viewport, activate immediately
            const rect = el.getBoundingClientRect();
            if (rect.top < (window.innerHeight || document.documentElement.clientHeight) && rect.bottom > 0) {
                el.classList.add('in-view', 'visible');
            }
            animObserver.observe(el);
        });
    }

    // 2. Rolling Metrics Counter Animation (Bidirectional Re-triggering)
    const metricsSection = document.querySelector('.metrics-strip');
    const counterElements = document.querySelectorAll('.counter-val');
    let activeCounterFrames = [];

    function runCounters() {
        // Cancel any active animation frames before starting fresh
        activeCounterFrames.forEach(id => cancelAnimationFrame(id));
        activeCounterFrames = [];

        counterElements.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'), 10);
            if (isNaN(target)) return;

            counter.textContent = '0';
            const duration = 1600; // ms
            const startTime = performance.now();

            function updateCount(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out cubic deceleration: 1 - (1 - progress)^3
                const easeVal = 1 - Math.pow(1 - progress, 3);
                const currentVal = Math.floor(target * easeVal);
                counter.textContent = currentVal.toLocaleString();

                if (progress < 1) {
                    const reqId = requestAnimationFrame(updateCount);
                    activeCounterFrames.push(reqId);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            }

            const reqId = requestAnimationFrame(updateCount);
            activeCounterFrames.push(reqId);
        });
    }

    function resetCounterDisplay() {
        activeCounterFrames.forEach(id => cancelAnimationFrame(id));
        activeCounterFrames = [];
        counterElements.forEach(counter => {
            counter.textContent = '0';
        });
    }

    if (metricsSection && counterElements.length > 0) {
        let metricsInView = false;
        const metricsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (!metricsInView) {
                        metricsInView = true;
                        runCounters();
                    }
                } else {
                    metricsInView = false;
                    resetCounterDisplay();
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -20px 0px' });

        metricsObserver.observe(metricsSection);

        // Check if metrics strip is in viewport on initial load
        const mRect = metricsSection.getBoundingClientRect();
        if (mRect.top < (window.innerHeight || document.documentElement.clientHeight) && mRect.bottom > 0) {
            metricsInView = true;
            runCounters();
        }
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
