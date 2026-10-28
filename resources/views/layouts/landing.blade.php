<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Apex Model Secondary School - Excellence in Academics, Leadership & Integrity')</title>
    <meta name="description" content="@yield('meta_description', 'Apex Model Secondary School established 2052 B.S. in Koshi Province, Nepal. Premier academic institution offering Early Childhood to Senior Secondary 10+2 programs.')">
    <meta name="theme-color" content="#0b1e38">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('landing_assets/icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('landing_assets/icons/icon-192.png') }}">

    <!-- Google Fonts & Original School-Demo Stylesheet -->
    <link rel="stylesheet" href="{{ asset('landing_assets/css/style.css') }}">
    @yield('extra_css')
</head>
<body>

    <!-- Floating Pill Navigation Header (Exact School-Demo UI) -->
    <header class="site-header">
        <div class="header-inner">
            <a href="{{ route('landing.home') }}" class="brand-block">
                <img src="{{ asset('landing_assets/images/logo.png') }}" alt="Apex Model Secondary School Crest" class="brand-crest">
                <div class="brand-text-block">
                    <div class="brand-title">{{ $settings['system_name'] ?? 'Apex Model Secondary School' }}</div>
                    <span class="brand-subtitle">Estd. {{ $settings['established_year'] ?? '2052 B.S. (1995 A.D.)' }} • Koshi Province</span>
                </div>
            </a>

            <nav>
                <ul class="main-nav-list">
                    <li>
                        <a href="{{ route('landing.home') }}" class="main-nav-link {{ request()->routeIs('landing.home') ? 'active' : '' }}">
                            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.about') }}" class="main-nav-link {{ request()->routeIs('landing.about') ? 'active' : '' }}">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            <span>About Us</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('landing.events') }}" class="main-nav-link {{ request()->routeIs('landing.events') ? 'active' : '' }}">
                            <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                            <span>Events & Life</span>
                        </a>
                    </li>
                    @if(($settings['entrance_results_visible'] ?? '0') === '1')
                    <li>
                        <a href="{{ route('admissions.results') }}" class="main-nav-link {{ request()->routeIs('admissions.results*') ? 'active' : '' }}">
                            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                            <span>Entrance Results</span>
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('landing.contact') }}" class="main-nav-link {{ request()->routeIs('landing.contact') ? 'active' : '' }}">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            <span>Contact</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Dynamic Page Content -->
    @yield('content')

    <!-- Footer (Exact School-Demo UI) -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <img src="{{ asset('landing_assets/images/logo.png') }}" alt="{{ $settings['system_name'] ?? 'Apex Model School' }} Logo">
                        <h2>{{ $settings['system_name'] ?? 'Apex Model School' }}</h2>
                    </div>
                    <p class="footer-p prose-constrained-sm">{{ $settings['system_name'] ?? 'Apex Model Secondary School' }} (Estd. {{ $settings['established_year'] ?? '2052 B.S.' }}) is committed to academic rigor, leadership formation, and character integrity approved by NEB and Ministry of Education, Nepal.</p>
                </div>

                <div>
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing.home') }}">Home Page</a></li>
                        <li><a href="{{ route('landing.about') }}">About Institution</a></li>
                        <li><a href="{{ route('landing.events') }}">Events & Gallery</a></li>
                        @if(($settings['entrance_results_visible'] ?? '0') === '1')
                            <li><a href="{{ route('admissions.results') }}">Entrance Exam Results</a></li>
                        @endif
                        <li><a href="{{ route('landing.contact') }}">Admissions Contact</a></li>
                        @guest
                            <li><a href="{{ route('login') }}">Staff & Student Portal</a></li>
                        @else
                            <li><a href="{{ route('dashboard') }}">Control Dashboard</a></li>
                        @endguest
                    </ul>
                </div>

                <div>
                    <h3>Contact Information</h3>
                    <p class="footer-p">{{ $settings['address'] ?? 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal' }}</p>
                    <p class="footer-p">Phone: {{ $settings['phone'] ?? '+977-25-560000' }}<br>WhatsApp: +977-9800000000</p>
                    <p class="footer-p">Email: {{ $settings['system_email'] ?? 'info@apexschool.edu.np' }}</p>
                </div>

                <div>
                    <h3>Campus Hours</h3>
                    <p class="footer-p">Sunday – Thursday: 9:00 AM – 4:00 PM</p>
                    <p class="footer-p">Friday: 9:00 AM – 1:30 PM</p>
                    <p class="footer-p" style="color: #ef4444;">Saturday: Closed</p>
                </div>
            </div>

            <div class="footer-bottom">
                <div>© {{ date('Y') }} {{ $settings['system_name'] ?? 'Apex Model Secondary School' }}. All Rights Reserved.</div>
                <div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="footer-portal-btn footer-portal-btn-auth">
                            <svg width="14" height="14" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                            <span>Open Control Dashboard ⚡</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="footer-portal-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            <span>Staff & Student ERP Portal</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </footer>

    <!-- Minimalist Mobile Bottom Dock (< 768px) - Cult UI AnimatedBackground Style -->
    <div class="mobile-bottom-dock">
        <ul class="dock-items">
            <li class="dock-item"><a href="{{ route('landing.home') }}" class="{{ request()->routeIs('landing.home') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span>Home</span>
            </a></li>
            <li class="dock-item"><a href="{{ route('landing.about') }}" class="{{ request()->routeIs('landing.about') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <span>About</span>
            </a></li>
            <li class="dock-item"><a href="{{ route('landing.events') }}" class="{{ request()->routeIs('landing.events') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                <span>Events</span>
            </a></li>
            @if(($settings['entrance_results_visible'] ?? '0') === '1')
            <li class="dock-item"><a href="{{ route('admissions.results') }}" class="{{ request()->routeIs('admissions.results*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span>Results</span>
            </a></li>
            @endif
            <li class="dock-item"><a href="{{ route('landing.contact') }}" class="{{ request()->routeIs('landing.contact') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <span>Contact</span>
            </a></li>
        </ul>
    </div>

    <!-- Global Modal Handlers -->
    <div id="modalBackdrop" class="modal-backdrop" onclick="closeModal()"></div>
    <div id="modalWindow" class="modal-window">
        <div id="modalBody" class="modal-body"></div>
    </div>

    <script src="{{ asset('landing_assets/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
