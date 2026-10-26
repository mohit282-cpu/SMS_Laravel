/**
 * Progressive Web App Client Module
 * Application: Laravel School Management System (Nepal Edition)
 * Version: 1.1.0
 *
 * Core Features:
 * 1. Safe Service Worker Lifecycle with Transaction State Machine
 * 2. User-Facing Update Prompt (Deferred during active form/financial submissions)
 * 3. BeforeInstallPrompt Banner & Install Handling
 * 4. Online/Offline Connectivity Status Indicators
 * 5. Web Push Notification Subscriptions
 * 6. Isolated, Scoped Offline Attendance Drafts (Data-minimized & Purged on Logout)
 */

(function () {
    'use strict';

    var deferredInstallPrompt = null;
    var newWorkerWaiting = null;

    /**
     * Update State Machine
     * States:
     * - IDLE: No pending background update, no in-flight transaction
     * - TRANSACTION_PENDING: Form submission or AJAX operation currently in-flight
     * - UPDATE_AVAILABLE: A new service worker is installed and waiting for user activation
     * - UPDATING: User activated update; sending SKIP_WAITING and refreshing safely
     */
    var PwaUpdateStateMachine = {
        STATE_IDLE: 'IDLE',
        STATE_TRANSACTION_PENDING: 'TRANSACTION_PENDING',
        STATE_UPDATE_AVAILABLE: 'UPDATE_AVAILABLE',
        STATE_UPDATING: 'UPDATING',

        state: 'IDLE',
        pendingTransactions: 0,
        transactionWatchdog: null,

        getState: function () {
            return this.state;
        },

        startTransaction: function () {
            this.pendingTransactions++;
            this.state = this.STATE_TRANSACTION_PENDING;

            // Clear any previous watchdog
            if (this.transactionWatchdog) {
                clearTimeout(this.transactionWatchdog);
            }

            // Safety watchdog: After 25 seconds without activity, automatically release lock
            // to prevent network failures or uncaught errors from freezing update capabilities permanently
            var self = this;
            this.transactionWatchdog = setTimeout(function () {
                console.warn('[PWA] Transaction watchdog triggered. Releasing stale transaction lock.');
                self.resetTransactions();
            }, 25000);

            this.updateUiState();
        },

        endTransaction: function () {
            if (this.pendingTransactions > 0) {
                this.pendingTransactions--;
            }

            if (this.pendingTransactions === 0) {
                if (this.transactionWatchdog) {
                    clearTimeout(this.transactionWatchdog);
                    this.transactionWatchdog = null;
                }

                if (newWorkerWaiting) {
                    this.state = this.STATE_UPDATE_AVAILABLE;
                } else {
                    this.state = this.STATE_IDLE;
                }
            }

            this.updateUiState();
        },

        resetTransactions: function () {
            this.pendingTransactions = 0;
            if (this.transactionWatchdog) {
                clearTimeout(this.transactionWatchdog);
                this.transactionWatchdog = null;
            }

            if (newWorkerWaiting) {
                this.state = this.STATE_UPDATE_AVAILABLE;
            } else {
                this.state = this.STATE_IDLE;
            }

            this.updateUiState();
        },

        isSafeToUpdate: function () {
            return this.state !== this.STATE_TRANSACTION_PENDING && this.pendingTransactions === 0;
        },

        updateUiState: function () {
            var updateBtn = document.getElementById('pwa-update-btn');
            if (!updateBtn) return;

            if (this.state === this.STATE_TRANSACTION_PENDING) {
                updateBtn.setAttribute('data-disabled', 'true');
                updateBtn.style.opacity = '0.6';
            } else {
                updateBtn.removeAttribute('data-disabled');
                updateBtn.style.opacity = '1';
            }
        }
    };

    // Attach transaction listeners to all forms and jQuery AJAX if present
    document.addEventListener('submit', function (e) {
        // Exclude logout form from blocking updates
        if (e.target && (e.target.id === 'logout-form' || (e.target.action && e.target.action.indexOf('logout') !== -1))) {
            return;
        }
        PwaUpdateStateMachine.startTransaction();
    }, true);

    // Release transaction lock on pageshow or navigation away
    window.addEventListener('pageshow', function () {
        PwaUpdateStateMachine.resetTransactions();
    });

    // jQuery integration for AJAX transactions (attendance, marks, payment modals)
    if (window.jQuery) {
        window.jQuery(document).ajaxStart(function () {
            PwaUpdateStateMachine.startTransaction();
        });
        window.jQuery(document).ajaxStop(function () {
            PwaUpdateStateMachine.endTransaction();
        });
        window.jQuery(document).ajaxError(function () {
            PwaUpdateStateMachine.endTransaction();
        });
    }

    /**
     * 1. Service Worker Registration & Safe Lifecycle
     */
    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        var swPath = window.PWA_SW_PATH || './sw.js';

        navigator.serviceWorker.register(swPath, { scope: './' })
            .then(function (registration) {
                console.log('[PWA] Service Worker registered with scope:', registration.scope);

                // Listen for waiting service workers
                registration.addEventListener('updatefound', function () {
                    var installingWorker = registration.installing;
                    if (!installingWorker) return;

                    installingWorker.addEventListener('statechange', function () {
                        if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            newWorkerWaiting = installingWorker;
                            if (PwaUpdateStateMachine.isSafeToUpdate()) {
                                PwaUpdateStateMachine.state = PwaUpdateStateMachine.STATE_UPDATE_AVAILABLE;
                            }
                            showUpdateNotification();
                        }
                    });
                });

                if (registration.waiting && navigator.serviceWorker.controller) {
                    newWorkerWaiting = registration.waiting;
                    if (PwaUpdateStateMachine.isSafeToUpdate()) {
                        PwaUpdateStateMachine.state = PwaUpdateStateMachine.STATE_UPDATE_AVAILABLE;
                    }
                    showUpdateNotification();
                }
            })
            .catch(function (error) {
                console.warn('[PWA] Service Worker registration failed:', error);
            });

        // Handle controller change (reloads only after user-confirmed update and when no transaction is pending)
        var refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (refreshing) return;

            // Strict safety check before reload
            if (!PwaUpdateStateMachine.isSafeToUpdate()) {
                console.warn('[PWA] Controller changed during pending transaction. Delaying reload until transaction completes.');
                var checkInterval = setInterval(function () {
                    if (PwaUpdateStateMachine.isSafeToUpdate()) {
                        clearInterval(checkInterval);
                        refreshing = true;
                        window.location.reload();
                    }
                }, 500);
                return;
            }

            refreshing = true;
            window.location.reload();
        });
    }

    /**
     * 2. App Update Notification
     */
    function showUpdateNotification() {
        var banner = document.getElementById('pwa-update-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'pwa-update-banner';
            banner.innerHTML =
                '<span id="pwa-update-msg">' + (window.PWA_I18N_UPDATE || 'A new version of the school portal is ready.') + '</span>' +
                '<button type="button" id="pwa-update-btn">' + (window.PWA_I18N_UPDATE_BTN || 'Update Now') + '</button>';
            document.body.appendChild(banner);

            document.getElementById('pwa-update-btn').addEventListener('click', function () {
                // Check if user is currently submitting a form or recording data
                if (!PwaUpdateStateMachine.isSafeToUpdate()) {
                    alert('Please wait until your current action or form submission completes before updating.');
                    return;
                }

                if (newWorkerWaiting) {
                    PwaUpdateStateMachine.state = PwaUpdateStateMachine.STATE_UPDATING;
                    var btn = document.getElementById('pwa-update-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerText = 'Updating...';
                    }
                    newWorkerWaiting.postMessage({ action: 'SKIP_WAITING' });
                }
            });
        }
        banner.style.display = 'flex';
        PwaUpdateStateMachine.updateUiState();
    }

    /**
     * 3. Installation Flow & Banner
     */
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredInstallPrompt = e;

        // Check 7-day cooldown
        var dismissedUntil = localStorage.getItem('pwa_install_dismissed_until');
        if (dismissedUntil && parseInt(dismissedUntil, 10) > Date.now()) {
            return;
        }

        showInstallBanner();
    });

    function showInstallBanner() {
        var banner = document.getElementById('pwa-install-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'pwa-install-banner';
            var iconSrc = window.PWA_ICON_PATH || './assets/images/icons/icon-192x192.png';
            var appName = window.PWA_APP_NAME || 'School Management System';

            banner.innerHTML =
                '<img src="' + iconSrc + '" alt="App Icon" class="pwa-banner-icon">' +
                '<div class="pwa-banner-content">' +
                '  <div class="pwa-banner-title">' + appName + '</div>' +
                '  <p class="pwa-banner-desc">' + (window.PWA_I18N_INSTALL_DESC || 'Install school portal for quick and reliable access') + '</p>' +
                '</div>' +
                '<div class="pwa-banner-actions">' +
                '  <button type="button" class="pwa-btn-install" id="pwa-btn-trigger-install">' + (window.PWA_I18N_INSTALL_BTN || 'Install') + '</button>' +
                '  <button type="button" class="pwa-btn-close" id="pwa-btn-dismiss-install" title="Dismiss">&times;</button>' +
                '</div>';
            document.body.appendChild(banner);

            document.getElementById('pwa-btn-trigger-install').addEventListener('click', triggerInstallPrompt);
            document.getElementById('pwa-btn-dismiss-install').addEventListener('click', dismissInstallBanner);
        }
        banner.style.display = 'flex';
    }

    function triggerInstallPrompt() {
        if (!deferredInstallPrompt) return;

        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.then(function (choiceResult) {
            if (choiceResult.outcome === 'accepted') {
                console.log('[PWA] User accepted the install prompt');
            } else {
                console.log('[PWA] User dismissed the install prompt');
                localStorage.setItem('pwa_install_dismissed_until', Date.now() + (7 * 24 * 60 * 60 * 1000));
            }
            deferredInstallPrompt = null;
            var banner = document.getElementById('pwa-install-banner');
            if (banner) banner.style.display = 'none';
        });
    }

    function dismissInstallBanner() {
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.style.display = 'none';
        localStorage.setItem('pwa_install_dismissed_until', Date.now() + (7 * 24 * 60 * 60 * 1000));
    }

    window.addEventListener('appinstalled', function () {
        console.log('[PWA] App successfully installed');
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.style.display = 'none';
        localStorage.setItem('pwa_is_installed', 'true');
    });

    /**
     * 4. Connectivity Indicators
     */
    function updateOnlineStatus() {
        var indicator = document.getElementById('pwa-offline-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'pwa-offline-indicator';
            document.body.appendChild(indicator);
        }

        if (navigator.onLine) {
            if (indicator.style.display === 'block') {
                indicator.className = 'online-restored';
                indicator.textContent = '● ' + (window.PWA_I18N_ONLINE || 'Connection Restored');
                setTimeout(function () {
                    indicator.style.display = 'none';
                    indicator.className = '';
                }, 3000);
            }
        } else {
            indicator.className = '';
            indicator.textContent = '● ' + (window.PWA_I18N_OFFLINE || 'Offline Mode - Live actions require connection');
            indicator.style.display = 'block';
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    /**
     * 5. Push Notifications Client Manager
     */
    /**
     * 5. Push Notifications Client Manager
     */
    window.PwaPush = {
        isSupported: function () {
            return ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);
        },
        getPermissionState: function () {
            if (!('Notification' in window)) return 'unsupported';
            return Notification.permission;
        },
        getSubscription: function () {
            if (!this.isSupported()) return Promise.resolve(null);
            return navigator.serviceWorker.ready.then(function (reg) {
                return reg.pushManager.getSubscription();
            });
        },
        getPublicKey: function () {
            if (window.PWA_VAPID_PUBLIC_KEY && typeof window.PWA_VAPID_PUBLIC_KEY === 'string' && window.PWA_VAPID_PUBLIC_KEY.trim() !== '') {
                return Promise.resolve(window.PWA_VAPID_PUBLIC_KEY.trim());
            }

            var endpoint = window.PWA_VAPID_PUBLIC_KEY_URL || './pwa/push/vapid-key';
            return fetch(endpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-cache'
            }).then(function (res) {
                return res.text().then(function (text) {
                    var data = {};
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        data = { status: 'error', message: 'Failed to parse VAPID public key response.' };
                    }

                    if (!res.ok || !data.vapid_public_key) {
                        throw new Error(data.message || 'VAPID public key is not configured on the server.');
                    }

                    window.PWA_VAPID_PUBLIC_KEY = data.vapid_public_key;
                    return data.vapid_public_key;
                });
            });
        },
        subscribe: function (publicKey, subscribeUrl) {
            if (!this.isSupported()) {
                return Promise.reject(new Error('Push notifications not supported on this browser.'));
            }

            var self = this;
            var keyPromise = (publicKey && typeof publicKey === 'string' && publicKey.trim() !== '')
                ? Promise.resolve(publicKey.trim())
                : self.getPublicKey();

            return keyPromise.then(function (key) {
                if (!key) {
                    throw new Error('VAPID public key is not configured on the server.');
                }

                return Notification.requestPermission().then(function (permission) {
                    if (permission !== 'granted') {
                        throw new Error('Notification permission was ' + permission);
                    }

                    return navigator.serviceWorker.ready;
                }).then(function (reg) {
                    var convertedKey = urlBase64ToUint8Array(key);

                    return reg.pushManager.getSubscription().then(function (existingSub) {
                        if (existingSub) {
                            var keyMatches = false;
                            try {
                                if (existingSub.options && existingSub.options.applicationServerKey) {
                                    var existingKey = new Uint8Array(existingSub.options.applicationServerKey);
                                    if (existingKey.length === convertedKey.length) {
                                        keyMatches = true;
                                        for (var i = 0; i < existingKey.length; i++) {
                                            if (existingKey[i] !== convertedKey[i]) {
                                                keyMatches = false;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } catch (e) {
                                keyMatches = false;
                            }

                            if (keyMatches) {
                                return existingSub;
                            }

                            return existingSub.unsubscribe().then(function () {
                                return reg.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: convertedKey
                                });
                            });
                        }

                        return reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: convertedKey
                        });
                    });
                }).then(function (subscription) {
                    var csrf = document.querySelector('meta[name="csrf-token"]') ?
                        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

                    var subJson = subscription.toJSON();
                    var payload = {
                        endpoint: subscription.endpoint,
                        keys: {
                            p256dh: subJson.keys ? subJson.keys.p256dh : '',
                            auth: subJson.keys ? subJson.keys.auth : ''
                        },
                        content_encoding: 'aesgcm'
                    };

                    return fetch(subscribeUrl || window.PWA_PUSH_SUBSCRIBE_URL || './pwa/push/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    }).then(function (res) {
                        return res.text().then(function (text) {
                            var data = {};
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = { status: 'error', message: 'Unexpected server response (HTTP ' + res.status + ')' };
                            }
                            if (!res.ok) {
                                throw new Error(data.message || 'Server rejected push subscription (HTTP ' + res.status + ')');
                            }
                            return data;
                        });
                    });
                });
            });
        },
        unsubscribe: function (unsubscribeUrl) {
            if (!this.isSupported()) return Promise.resolve();

            return navigator.serviceWorker.ready.then(function (reg) {
                return reg.pushManager.getSubscription();
            }).then(function (sub) {
                if (!sub) return Promise.resolve();

                var endpoint = sub.endpoint;
                return sub.unsubscribe().then(function () {
                    var csrf = document.querySelector('meta[name="csrf-token"]') ?
                        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

                    return fetch(unsubscribeUrl || window.PWA_PUSH_UNSUBSCRIBE_URL || './pwa/push/unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ endpoint: endpoint })
                    }).then(function (res) {
                        return res.text().then(function (text) {
                            var data = {};
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = { status: 'error', message: 'HTTP ' + res.status };
                            }
                            return data;
                        });
                    }).catch(function (fetchErr) {
                        // If browser unsubscription succeeded but server endpoint request failed (e.g. offline/network error),
                        // record pending unsubscription endpoint for safe background reconciliation.
                        try {
                            var raw = localStorage.getItem('lav_sms_pending_unsub');
                            var list = raw ? JSON.parse(raw) : [];
                            var exists = false;
                            for (var k = 0; k < list.length; k++) {
                                if (list[k].endpoint === endpoint) { exists = true; break; }
                            }
                            if (!exists) {
                                list.push({ endpoint: endpoint, time: Date.now() });
                                localStorage.setItem('lav_sms_pending_unsub', JSON.stringify(list));
                            }
                        } catch (e) {}
                        return { status: 'queued', message: 'Unsubscribed locally. Server sync queued.' };
                    });
                });
            });
        },
        sendTestNotification: function () {
            var csrf = document.querySelector('meta[name="csrf-token"]') ?
                document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

            return fetch(window.PWA_PUSH_TEST_URL || './pwa/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            }).then(function (res) {
                return res.text().then(function (text) {
                    var data = {};
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        data = { status: 'error', message: 'Unexpected server response (HTTP ' + res.status + ')' };
                    }
                    data.http_ok = res.ok;
                    data.http_status = res.status;
                    return data;
                });
            });
        },
        reconcilePendingUnsubscribes: function () {
            try {
                var raw = localStorage.getItem('lav_sms_pending_unsub');
                if (!raw) return Promise.resolve();
                var list = JSON.parse(raw);
                if (!Array.isArray(list) || list.length === 0) return Promise.resolve();

                var csrf = document.querySelector('meta[name="csrf-token"]') ?
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

                var remaining = [];
                var promises = list.map(function (item) {
                    return fetch(window.PWA_PUSH_UNSUBSCRIBE_URL || './pwa/push/unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ endpoint: item.endpoint })
                    }).then(function (res) {
                        if (!res.ok && res.status >= 500) {
                            remaining.push(item);
                        }
                    }).catch(function () {
                        remaining.push(item);
                    });
                });

                return Promise.all(promises).then(function () {
                    if (remaining.length > 0) {
                        localStorage.setItem('lav_sms_pending_unsub', JSON.stringify(remaining));
                    } else {
                        localStorage.removeItem('lav_sms_pending_unsub');
                    }
                });
            } catch (e) {
                return Promise.resolve();
            }
        }
    };

    /**
     * UI Controller for Web Push settings in Account views
     */
    window.PwaPushUI = {
        init: function () {
            if (window.PwaPush && window.PwaPush.reconcilePendingUnsubscribes) {
                window.PwaPush.reconcilePendingUnsubscribes();
            }

            var card = document.getElementById('pwa-push-card');
            if (!card) return;

            this.updateCard();

            var btnEnable = document.getElementById('pwa-btn-enable-push');
            var btnDisable = document.getElementById('pwa-btn-disable-push');
            var btnTest = document.getElementById('pwa-btn-test-push');

            var self = this;
            if (btnEnable) {
                btnEnable.addEventListener('click', function () {
                    btnEnable.disabled = true;
                    btnEnable.innerHTML = '<i class="icon-spinner2 spinner mr-1"></i> Enabling...';
                    window.PwaPush.subscribe().then(function () {
                        self.updateCard();
                        alert('Push notifications enabled successfully for this device!');
                    }).catch(function (err) {
                        btnEnable.disabled = false;
                        btnEnable.innerHTML = '<i class="icon-bell mr-1"></i> Enable Notifications';
                        self.updateCard();
                        alert('Failed to enable push notifications: ' + err.message);
                    });
                });
            }

            if (btnDisable) {
                btnDisable.addEventListener('click', function () {
                    btnDisable.disabled = true;
                    btnDisable.innerHTML = '<i class="icon-spinner2 spinner mr-1"></i> Disabling...';
                    window.PwaPush.unsubscribe().then(function () {
                        self.updateCard();
                        alert('Push notifications disabled for this device.');
                    }).catch(function (err) {
                        btnDisable.disabled = false;
                        btnDisable.innerHTML = '<i class="icon-bell-cross mr-1"></i> Disable Notifications';
                        self.updateCard();
                        alert('Failed to disable: ' + err.message);
                    });
                });
            }

            if (btnTest) {
                btnTest.addEventListener('click', function () {
                    btnTest.disabled = true;
                    btnTest.innerHTML = '<i class="icon-spinner2 spinner mr-1"></i> Sending...';
                    window.PwaPush.sendTestNotification().then(function (res) {
                        btnTest.disabled = false;
                        btnTest.innerHTML = '<i class="icon-paperplane mr-1"></i> Send Test Notification';
                        if (res && res.status === 'success') {
                            alert('Test notification accepted by push service! Check your device notifications.');
                        } else if (res && res.http_status === 429) {
                            alert('Rate limit exceeded: Please wait a moment before sending another test notification.');
                        } else if (res && res.status === 'no_subscriptions') {
                            alert('No active push subscriptions found on this device. Please enable notifications first.');
                        } else if (res && res.status === 'vapid_keys_missing') {
                            alert('VAPID keys not configured on server.');
                        } else {
                            alert('Test notification: ' + (res.message || 'Delivery could not be completed.'));
                        }
                    }).catch(function (err) {
                        btnTest.disabled = false;
                        btnTest.innerHTML = '<i class="icon-paperplane mr-1"></i> Send Test Notification';
                        alert('Error sending test notification: ' + (err.message || 'Network error'));
                    });
                });
            }
        },

        updateCard: function () {
            var badgePermission = document.getElementById('pwa-push-permission-badge');
            var badgeStatus = document.getElementById('pwa-push-status-badge');
            var btnEnable = document.getElementById('pwa-btn-enable-push');
            var btnDisable = document.getElementById('pwa-btn-disable-push');
            var btnTest = document.getElementById('pwa-btn-test-push');
            var msgEl = document.getElementById('pwa-push-msg');

            if (!window.PwaPush.isSupported()) {
                if (badgePermission) {
                    badgePermission.className = 'badge badge-secondary';
                    badgePermission.textContent = 'Unsupported';
                }
                if (badgeStatus) {
                    badgeStatus.className = 'badge badge-secondary';
                    badgeStatus.textContent = 'Not Available';
                }
                if (btnEnable) btnEnable.style.display = 'none';
                if (btnDisable) btnDisable.style.display = 'none';
                if (btnTest) btnTest.style.display = 'none';
                if (msgEl) msgEl.textContent = 'Web Push notifications are not supported by this browser.';
                return;
            }

            var perm = window.PwaPush.getPermissionState();
            if (badgePermission) {
                if (perm === 'granted') {
                    badgePermission.className = 'badge badge-success';
                    badgePermission.textContent = 'Granted';
                } else if (perm === 'denied') {
                    badgePermission.className = 'badge badge-danger';
                    badgePermission.textContent = 'Blocked / Denied';
                } else {
                    badgePermission.className = 'badge badge-warning';
                    badgePermission.textContent = 'Prompt Required';
                }
            }

            window.PwaPush.getSubscription().then(function (sub) {
                if (sub) {
                    if (badgeStatus) {
                        badgeStatus.className = 'badge badge-success';
                        badgeStatus.textContent = 'Subscribed (Active)';
                    }
                    if (btnEnable) btnEnable.style.display = 'none';
                    if (btnDisable) {
                        btnDisable.style.display = 'inline-block';
                        btnDisable.disabled = false;
                        btnDisable.innerHTML = '<i class="icon-bell-cross mr-1"></i> Disable Notifications';
                    }
                    if (btnTest) {
                        btnTest.style.display = 'inline-block';
                        btnTest.disabled = false;
                        btnTest.innerHTML = '<i class="icon-paperplane mr-1"></i> Send Test Notification';
                    }
                    if (msgEl) msgEl.textContent = 'This browser is registered to receive school updates and alerts.';
                } else {
                    if (badgeStatus) {
                        badgeStatus.className = 'badge badge-secondary';
                        badgeStatus.textContent = 'Not Subscribed';
                    }
                    if (btnEnable) {
                        btnEnable.style.display = 'inline-block';
                        btnEnable.disabled = false;
                        btnEnable.innerHTML = '<i class="icon-bell mr-1"></i> Enable Notifications';
                    }
                    if (btnDisable) btnDisable.style.display = 'none';
                    if (btnTest) btnTest.style.display = 'none';
                    if (msgEl) {
                        if (perm === 'denied') {
                            msgEl.textContent = 'Notifications are blocked in your browser settings. Please allow notifications in site permissions to receive alerts.';
                        } else {
                            msgEl.textContent = 'Click "Enable Notifications" to receive school alerts on this device.';
                        }
                    }
                }
            });
        }
    };

    function urlBase64ToUint8Array(base64String) {
        if (!base64String || typeof base64String !== 'string') {
            throw new Error('Invalid VAPID public key string.');
        }
        var padding = '===='.slice(0, (4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        var rawData;
        try {
            rawData = window.atob(base64);
        } catch (e) {
            throw new Error('Failed to decode VAPID public key: invalid base64 encoding.');
        }
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * 6. Offline Attendance Drafts & Synchronization Engine
     * Architecture:
     * - UUIDv4 client-generated idempotency keys
     * - User-scoped storage isolation (purged on logout/user switch)
     * - Multi-tab lock protection & structured states: pending, syncing, synced, conflict, failed
     * - IndexedDB primary storage with resilient localStorage fallback
     * - Data minimization: records ONLY student_record_id, status (present/absent/late/leave/half_day), and remarks
     * - Conflict resolution: prompts user when server has newer records rather than silently overwriting
     */

    function generateUUID() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Data minimization: Store ONLY student IDs and validated status codes ('P', 'A', 'L', 'H' or 'present', 'absent', 'late', 'leave', 'half_day')
    var VALID_STATUSES = ['present', 'absent', 'late', 'leave', 'half_day'];
    var STATUS_MAP = {
        'P': 'present',
        'A': 'absent',
        'L': 'late',
        'LV': 'leave',
        'H': 'half_day'
    };

    // Storage abstraction: IndexedDB with localStorage fallback
    var PwaAttendanceStore = {
        dbName: 'lav_sms_pwa_db',
        dbVersion: 1,
        storeName: 'attendance_drafts',
        _db: null,

        openDb: function (callback) {
            if (this._db) {
                return callback(null, this._db);
            }
            if (!window.indexedDB) {
                return callback(new Error('IndexedDB not supported'), null);
            }

            var self = this;
            var request = window.indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = function (event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains(self.storeName)) {
                    var store = db.createObjectStore(self.storeName, { keyPath: 'client_uuid' });
                    store.createIndex('user_id', 'user_id', { unique: false });
                    store.createIndex('sync_status', 'sync_status', { unique: false });
                    store.createIndex('batch_key', 'batch_key', { unique: false });
                }
            };

            request.onsuccess = function (event) {
                self._db = event.target.result;
                callback(null, self._db);
            };

            request.onerror = function (event) {
                console.warn('[PWA DB] IndexedDB open failed:', event.target.error);
                callback(event.target.error, null);
            };
        },

        getFallbackKey: function () {
            var userId = window.PWA_USER_ID;
            return userId ? 'lav_sms_att_drafts_uid_' + userId : null;
        },

        getAll: function (callback) {
            var userId = window.PWA_USER_ID;
            if (!userId) {
                return callback(null, []);
            }

            var self = this;
            this.openDb(function (err, db) {
                if (err || !db) {
                    // LocalStorage fallback
                    var key = self.getFallbackKey();
                    if (!key) return callback(null, []);
                    try {
                        var raw = localStorage.getItem(key);
                        var data = raw ? JSON.parse(raw) : {};
                        var list = [];
                        for (var k in data) {
                            if (Object.prototype.hasOwnProperty.call(data, k)) {
                                list.push(data[k]);
                            }
                        }
                        return callback(null, list);
                    } catch (e) {
                        console.error('[PWA DB] LocalStorage read corruption prevented:', e);
                        return callback(null, []);
                    }
                }

                try {
                    var tx = db.transaction(self.storeName, 'readonly');
                    var store = tx.objectStore(self.storeName);
                    var index = store.index('user_id');
                    var request = index.getAll(IDBKeyRange.only(userId));

                    request.onsuccess = function () {
                        callback(null, request.result || []);
                    };
                    request.onerror = function () {
                        callback(request.error, []);
                    };
                } catch (e) {
                    callback(e, []);
                }
            });
        },

        put: function (draft, callback) {
            var self = this;
            this.openDb(function (err, db) {
                if (err || !db) {
                    var key = self.getFallbackKey();
                    if (!key) return callback && callback(new Error('Unauthenticated'));
                    try {
                        var raw = localStorage.getItem(key);
                        var data = raw ? JSON.parse(raw) : {};
                        data[draft.client_uuid] = draft;
                        localStorage.setItem(key, JSON.stringify(data));
                        if (callback) callback(null, draft);
                    } catch (e) {
                        console.error('[PWA DB] LocalStorage quota/write error:', e);
                        if (callback) callback(e);
                    }
                    return;
                }

                try {
                    var tx = db.transaction(self.storeName, 'readwrite');
                    var store = tx.objectStore(self.storeName);
                    var req = store.put(draft);
                    req.onsuccess = function () {
                        if (callback) callback(null, draft);
                    };
                    req.onerror = function () {
                        if (callback) callback(req.error);
                    };
                } catch (e) {
                    if (callback) callback(e);
                }
            });
        },

        delete: function (clientUuid, callback) {
            var self = this;
            this.openDb(function (err, db) {
                if (err || !db) {
                    var key = self.getFallbackKey();
                    if (!key) return callback && callback(null);
                    try {
                        var raw = localStorage.getItem(key);
                        var data = raw ? JSON.parse(raw) : {};
                        delete data[clientUuid];
                        localStorage.setItem(key, JSON.stringify(data));
                        if (callback) callback(null);
                    } catch (e) {
                        if (callback) callback(e);
                    }
                    return;
                }

                try {
                    var tx = db.transaction(self.storeName, 'readwrite');
                    var store = tx.objectStore(self.storeName);
                    var req = store.delete(clientUuid);
                    req.onsuccess = function () {
                        if (callback) callback(null);
                    };
                    req.onerror = function () {
                        if (callback) callback(req.error);
                    };
                } catch (e) {
                    if (callback) callback(e);
                }
            });
        },

        clearAll: function (callback) {
            var self = this;
            // Clear localStorage
            for (var i = localStorage.length - 1; i >= 0; i--) {
                var key = localStorage.key(i);
                if (key && (key.indexOf('lav_sms_att_') === 0 || key === 'lav_sms_attendance_drafts')) {
                    localStorage.removeItem(key);
                }
            }

            // Clear IndexedDB
            this.openDb(function (err, db) {
                if (err || !db) {
                    if (callback) callback(null);
                    return;
                }
                try {
                    var tx = db.transaction(self.storeName, 'readwrite');
                    var store = tx.objectStore(self.storeName);
                    var req = store.clear();
                    req.onsuccess = function () {
                        if (callback) callback(null);
                    };
                    req.onerror = function () {
                        if (callback) callback(req.error);
                    };
                } catch (e) {
                    if (callback) callback(e);
                }
            });
        }
    };

    function enforceUserSessionIsolation() {
        var currentUserId = window.PWA_USER_ID ? String(window.PWA_USER_ID) : null;
        var lastKnownUser = localStorage.getItem('lav_sms_active_uid');

        if (lastKnownUser !== currentUserId) {
            if (currentUserId) {
                localStorage.setItem('lav_sms_active_uid', currentUserId);
            } else {
                localStorage.removeItem('lav_sms_active_uid');
            }
        }
    }

    /**
     * Public PwaAttendance API
     */
    window.PwaAttendance = {
        saveDraft: function (params, callback) {
            var userId = window.PWA_USER_ID;
            if (!userId) {
                var err = new Error('Cannot save attendance draft: User is unauthenticated.');
                console.warn('[PWA Attendance]', err.message);
                if (callback) callback(err, null);
                return;
            }

            var classId = parseInt(params.my_class_id, 10);
            var sectionId = params.section_id ? parseInt(params.section_id, 10) : null;
            var dateBs = String(params.attendance_date_bs || '').trim();
            var records = params.students || {};

            // Data minimization & validation: Store ONLY student_record_id, validated status, and optional remarks
            var sanitizedStudents = {};
            if (typeof records === 'object' && records !== null) {
                for (var stId in records) {
                    if (Object.prototype.hasOwnProperty.call(records, stId) && /^\d+$/.test(stId)) {
                        var rawStatus = records[stId].status || records[stId];
                        var status = String(rawStatus).trim().toLowerCase();
                        if (STATUS_MAP[status.toUpperCase()]) {
                            status = STATUS_MAP[status.toUpperCase()];
                        }
                        if (VALID_STATUSES.indexOf(status) !== -1) {
                            sanitizedStudents[parseInt(stId, 10)] = {
                                status: status,
                                remarks: records[stId].remarks ? String(records[stId].remarks).substring(0, 255) : ''
                            };
                        }
                    }
                }
            }

            var batchKey = classId + '_' + (sectionId || 0) + '_' + dateBs;
            var clientUuid = params.client_uuid || generateUUID();

            var draft = {
                client_uuid: clientUuid,
                schema_version: 1,
                user_id: userId,
                my_class_id: classId,
                class_name: params.class_name || ('Class ' + classId),
                section_id: sectionId,
                section_name: params.section_name || (sectionId ? ('Sec ' + sectionId) : 'All Sections'),
                attendance_date_bs: dateBs,
                students: sanitizedStudents,
                total_students: Object.keys(sanitizedStudents).length,
                batch_key: batchKey,
                created_at: params.created_at || new Date().toISOString(),
                updated_at: new Date().toISOString(),
                sync_status: 'pending',
                sync_attempts: 0,
                last_sync_error: null
            };

            PwaAttendanceStore.put(draft, function (e, saved) {
                if (e) {
                    console.error('[PWA Attendance] Failed to save draft:', e);
                    if (callback) callback(e, null);
                } else {
                    console.log('[PWA Attendance] Draft saved successfully:', clientUuid);
                    if (callback) callback(null, saved);
                }
            });
        },

        getDrafts: function (callback) {
            var userId = window.PWA_USER_ID;
            PwaAttendanceStore.getAll(function (err, drafts) {
                if (err) return callback && callback(err, null);
                if (!userId) {
                    return callback && callback(null, []);
                }
                var userDrafts = (drafts || []).filter(function (d) {
                    return String(d.user_id) === String(userId);
                });
                return callback && callback(null, userDrafts);
            });
        },

        deleteDraft: function (clientUuid, callback) {
            PwaAttendanceStore.delete(clientUuid, callback);
        },

        syncDraft: function (clientUuid, forceOverwrite, callback) {
            var self = this;
            PwaAttendanceStore.getAll(function (err, drafts) {
                if (err) return callback && callback(err);

                var draft = null;
                for (var i = 0; i < drafts.length; i++) {
                    if (drafts[i].client_uuid === clientUuid) {
                        draft = drafts[i];
                        break;
                    }
                }

                if (!draft) {
                    return callback && callback(new Error('Draft not found: ' + clientUuid));
                }

                // Prevent concurrent submissions of same draft
                if (draft.sync_status === 'syncing') {
                    console.warn('[PWA Attendance] Draft already syncing:', clientUuid);
                    return callback && callback(new Error('Draft synchronization already in-flight.'));
                }

                // Mark draft syncing
                draft.sync_status = 'syncing';
                PwaAttendanceStore.put(draft, function () {
                    var syncUrl = window.PWA_ATTENDANCE_SYNC_URL || './attendance/sync';
                    var csrfToken = document.querySelector('meta[name="csrf-token"]') ?
                        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

                    var payload = {
                        idempotency_key: draft.client_uuid,
                        my_class_id: draft.my_class_id,
                        section_id: draft.section_id,
                        attendance_date_bs: draft.attendance_date_bs,
                        students: draft.students,
                        created_at_client: draft.created_at,
                        force_overwrite: Boolean(forceOverwrite)
                    };

                    fetch(syncUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { status: response.status, body: data };
                        });
                    })
                    .then(function (res) {
                        if (res.status === 200 && res.body.status === 'success') {
                            draft.sync_status = 'synced';
                            draft.last_sync_error = null;
                            draft.synced_at = res.body.data && res.body.data.synced_at ? res.body.data.synced_at : new Date().toISOString();
                            PwaAttendanceStore.put(draft, function () {
                                if (callback) callback(null, { status: 'success', draft: draft, response: res.body });
                            });
                        } else if (res.status === 409 || res.body.status === 'conflict') {
                            draft.sync_status = 'conflict';
                            draft.last_sync_error = res.body.message || 'Server attendance was modified after draft was created.';
                            draft.conflict_info = res.body;
                            PwaAttendanceStore.put(draft, function () {
                                if (callback) callback(null, { status: 'conflict', draft: draft, response: res.body });
                            });
                        } else {
                            draft.sync_status = 'failed';
                            draft.last_sync_error = res.body.message || 'Server rejected synchronization request.';
                            PwaAttendanceStore.put(draft, function () {
                                if (callback) callback(null, { status: 'failed', draft: draft, response: res.body });
                            });
                        }
                    })
                    .catch(function (fetchErr) {
                        draft.sync_attempts = (draft.sync_attempts || 0) + 1;
                        draft.sync_status = draft.sync_attempts >= 3 ? 'failed' : 'pending';
                        draft.last_sync_error = 'Network connection failed. Ready to retry.';
                        PwaAttendanceStore.put(draft, function () {
                            if (callback) callback(fetchErr, { status: 'network_error', draft: draft });
                        });
                    });
                });
            });
        },

        syncAllPending: function (callback) {
            var self = this;
            this.getDrafts(function (err, drafts) {
                if (err) return callback && callback(err);

                var pending = drafts.filter(function (d) {
                    return d.sync_status === 'pending' || d.sync_status === 'failed';
                });

                if (pending.length === 0) {
                    return callback && callback(null, { total: 0, synced: 0 });
                }

                var index = 0;
                var results = { total: pending.length, succeeded: 0, conflicts: 0, failed: 0 };

                function processNext() {
                    if (index >= pending.length) {
                        return callback && callback(null, results);
                    }
                    var current = pending[index++];
                    self.syncDraft(current.client_uuid, false, function (syncErr, res) {
                        if (res && res.status === 'success') results.succeeded++;
                        else if (res && res.status === 'conflict') results.conflicts++;
                        else results.failed++;
                        processNext();
                    });
                }

                processNext();
            });
        },

        purgeAll: purgeAllAttendanceDrafts
    };

    /**
     * Attendance UI Controller for Blade Views
     */
    window.PwaAttendanceUI = {
        init: function () {
            var widget = document.getElementById('pwa-attendance-offline-widget');
            if (!widget) return;

            this.refreshWidget();

            window.addEventListener('online', this.refreshWidget.bind(this));
            window.addEventListener('offline', this.refreshWidget.bind(this));
        },

        refreshWidget: function () {
            var widget = document.getElementById('pwa-attendance-offline-widget');
            if (!widget) return;

            window.PwaAttendance.getDrafts(function (err, drafts) {
                if (err || !drafts || drafts.length === 0) {
                    widget.style.display = 'none';
                    return;
                }

                widget.style.display = 'block';
                var countBadge = document.getElementById('pwa-pending-count-badge');
                if (countBadge) countBadge.textContent = drafts.length;

                var tbody = document.getElementById('pwa-drafts-tbody');
                if (!tbody) return;
                tbody.innerHTML = '';

                drafts.forEach(function (d) {
                    var tr = document.createElement('tr');
                    var badgeClass = 'badge-warning';
                    var statusText = 'Pending';

                    if (d.sync_status === 'syncing') {
                        badgeClass = 'badge-info';
                        statusText = 'Syncing...';
                    } else if (d.sync_status === 'synced') {
                        badgeClass = 'badge-success';
                        statusText = 'Synced ✓';
                    } else if (d.sync_status === 'conflict') {
                        badgeClass = 'badge-danger';
                        statusText = 'Conflict !';
                    } else if (d.sync_status === 'failed') {
                        badgeClass = 'badge-secondary';
                        statusText = 'Failed';
                    }

                    var actions = '';
                    if (d.sync_status === 'conflict') {
                        actions = '<button type="button" class="btn btn-xs btn-outline-danger mr-1" onclick="PwaAttendanceUI.resolveConflict(\'' + d.client_uuid + '\')"><i class="icon-warning22"></i> Resolve Conflict</button>';
                    } else if (d.sync_status !== 'synced') {
                        actions = '<button type="button" class="btn btn-xs btn-outline-primary mr-1" onclick="PwaAttendanceUI.syncSingle(\'' + d.client_uuid + '\')"><i class="icon-sync"></i> Sync</button>';
                    }
                    actions += '<button type="button" class="btn btn-xs btn-outline-secondary" onclick="PwaAttendanceUI.discardSingle(\'' + d.client_uuid + '\')"><i class="icon-trash"></i> Discard</button>';

                    tr.innerHTML =
                        '<td class="font-weight-bold">' + d.attendance_date_bs + ' BS</td>' +
                        '<td>' + (d.class_name || ('Class ' + d.my_class_id)) + ' - ' + (d.section_name || 'All') + '</td>' +
                        '<td><span class="badge badge-light">' + (d.total_students || Object.keys(d.students).length) + ' Students</span></td>' +
                        '<td><span class="badge ' + badgeClass + '">' + statusText + '</span>' + (d.last_sync_error ? '<div class="text-danger font-size-xs mt-1">' + d.last_sync_error + '</div>' : '') + '</td>' +
                        '<td class="text-right">' + actions + '</td>';

                    tbody.appendChild(tr);
                });
            });
        },

        syncAll: function () {
            var btn = document.getElementById('pwa-btn-sync-all');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="icon-spinner2 spinner mr-1"></i> Syncing...';
            }

            var self = this;
            window.PwaAttendance.syncAllPending(function (err, results) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="icon-sync mr-1"></i> Sync All';
                }
                self.refreshWidget();

                if (results && results.conflicts > 0) {
                    alert('Sync completed with ' + results.succeeded + ' successful and ' + results.conflicts + ' conflict(s). Please review conflicts.');
                } else if (results && results.succeeded > 0) {
                    alert('Successfully synchronized ' + results.succeeded + ' attendance batch(es) to the server.');
                    window.location.reload();
                } else if (results && results.total === 0) {
                    alert('No pending attendance drafts to sync.');
                }
            });
        },

        syncSingle: function (uuid) {
            var self = this;
            window.PwaAttendance.syncDraft(uuid, false, function (err, res) {
                self.refreshWidget();
                if (res && res.status === 'success') {
                    alert('Attendance batch synchronized successfully!');
                    window.location.reload();
                } else if (res && res.status === 'conflict') {
                    self.resolveConflict(uuid);
                } else {
                    alert('Sync failed: ' + (res && res.response && res.response.message ? res.response.message : (err ? err.message : 'Unknown error')));
                }
            });
        },

        resolveConflict: function (uuid) {
            var self = this;
            var choice = confirm(
                'Attendance records on the server were modified after your offline draft was saved.\n\n' +
                'Click [OK] to OVERWRITE the server with your offline draft.\n' +
                'Click [Cancel] to review or discard your offline draft.'
            );

            if (choice) {
                window.PwaAttendance.syncDraft(uuid, true, function (err, res) {
                    self.refreshWidget();
                    if (res && res.status === 'success') {
                        alert('Server attendance updated successfully with your draft.');
                        window.location.reload();
                    } else {
                        alert('Failed to overwrite server: ' + (res && res.response ? res.response.message : 'Error'));
                    }
                });
            }
        },

        discardSingle: function (uuid) {
            var confirmed = confirm(
                'WARNING: This offline attendance draft has not been finalized on the server.\n\n' +
                'Discarding it will PERMANENTLY DELETE your locally saved entries for this date.\n\n' +
                'Are you sure you want to discard this draft?'
            );

            if (confirmed) {
                var self = this;
                window.PwaAttendance.deleteDraft(uuid, function () {
                    self.refreshWidget();
                });
            }
        }
    };

    // Run session isolation check immediately
    enforceUserSessionIsolation();

    // Export state machine for testing and monitoring
    window.PwaUpdateStateMachine = PwaUpdateStateMachine;

    // Background reconciliation when connectivity is restored
    window.addEventListener('online', function () {
        if (window.PwaPush && window.PwaPush.reconcilePendingUnsubscribes) {
            window.PwaPush.reconcilePendingUnsubscribes();
        }
    });

    // Initialize on DOM load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            registerServiceWorker();
            window.PwaAttendanceUI.init();
            window.PwaPushUI.init();
        });
    } else {
        registerServiceWorker();
        window.PwaAttendanceUI.init();
        window.PwaPushUI.init();
    }
})();
