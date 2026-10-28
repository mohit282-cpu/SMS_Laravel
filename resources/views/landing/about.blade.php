@extends('layouts.landing')

@section('title', 'About Us - ' . ($settings['system_name'] ?? 'Apex Model Secondary School'))
@section('meta_description', 'Learn about Apex Model Secondary School\'s three-decade legacy of academic excellence, leadership development, and character discipline in Koshi Province, Nepal.')

@section('content')
    <!-- Page Banner Hero with School Pamphlet Background Image (Exact School-Demo) -->
    <section class="hero-section">
        <div class="hero-video-bg" style="background-image: url('{{ asset('landing_assets/images/school_pamphlet.jpg') }}'); background-size: cover; background-position: center; opacity: 0.65;"></div>
        <div class="hero-overlay" style="background: linear-gradient(180deg, rgba(11, 30, 56, 0.82) 0%, rgba(11, 30, 56, 0.65) 50%, rgba(11, 30, 56, 0.9) 100%);"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge-pill">
                    📜 Institutional Profile & Academic Pamphlet
                </div>
                <h1 class="hero-h1" style="font-size: 2.85rem;">
                    About Apex Model Secondary School
                </h1>
                <p class="hero-body-text prose-constrained" style="font-size: 1.15rem; margin-bottom: 2rem;">
                    Three decades of unwavering commitment to intellectual curiosity, moral discipline, and STEM academic leadership in Koshi Province, Nepal.
                </p>
                <div class="hero-cta-group">
                    <button type="button" onclick="openPamphletModal()" class="btn btn-gold">
                        🖼️ View Official School Pamphlet Poster →
                    </button>
                    <a href="{{ route('landing.contact') }}" class="btn btn-primary">
                        Contact Admissions Office
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Institutional Background & Heritage (Exact School-Demo) -->
    <section class="section">
        <div class="container">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;" class="admin-dashboard-grid">
                <div class="scroll-anim" data-anim="left">
                    <span class="section-tag">Established 2052 B.S.</span>
                    <h2 class="section-h2">A Legacy of Academic Leadership</h2>
                    <p style="font-size: 1.05rem; color: var(--text-body); line-height: 1.7; margin-bottom: 1.25rem;">
                        Founded in 1995 A.D. (2052 B.S.), Apex Model Secondary School was established with a singular mandate: to provide accessible, high-caliber academic education coupled with practical STEM technology skills in Koshi Province.
                    </p>
                    <p style="font-size: 1rem; color: var(--text-body); line-height: 1.7; margin-bottom: 1.75rem;">
                        Fully approved by the Ministry of Education and affiliated with the National Examination Board (NEB), Nepal, our campus nurtures over 1,400 students across Pre-Primary, Primary, Lower Secondary, Secondary (SEE), and Senior Secondary (10+2 Science, Management, Humanities) levels.
                    </p>

                    <div style="display: flex; gap: 1.5rem;" class="admin-grid-2col">
                        <div style="background-color: #f1f5f9; padding: 1.25rem; border-radius: var(--radius-md); border-left: 4px solid var(--accent-blue);">
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary-navy);">NEB Affiliated</h3>
                            <p style="font-size: 0.875rem; color: var(--text-body);">Recognized for 100% board exam pass rate and distinction achievements.</p>
                        </div>
                        <div style="background-color: #f1f5f9; padding: 1.25rem; border-radius: var(--radius-md); border-left: 4px solid var(--accent-gold);">
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary-navy);">STEM Integrated</h3>
                            <p style="font-size: 0.875rem; color: var(--text-body);">Hands-on robotics, computer science, and laboratory experimentation.</p>
                        </div>
                    </div>
                </div>

                <div class="scroll-anim" data-anim="right">
                    <img src="{{ asset('landing_assets/images/facilities/robotics_lab.jpg') }}" alt="Apex Campus" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border-subtle); width: 100%;">
                </div>
            </div>
        </div>
    </section>

    <!-- Vision, Mission & Core Values Grid (Exact School-Demo) -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Guiding Principles</div>
                <h2 class="section-h2">Vision, Mission & Core Values</h2>
            </div>

            <div class="streams-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stream-card scroll-anim" data-anim="left">
                    <div>
                        <div class="stream-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        </div>
                        <h3>Our Vision</h3>
                        <p>To be Koshi Province's benchmark secondary institution, celebrated for empowering ethical global leaders, innovators, and disciplined citizens.</p>
                    </div>
                </div>

                <div class="stream-card scroll-anim" data-anim="up">
                    <div>
                        <div class="stream-icon" style="background-color: #fef3c7; color: var(--accent-gold);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                        </div>
                        <h3>Our Mission</h3>
                        <p>To provide student-centric education combining rigorous academic standards, modern STEM technology, sportsmanship, and civic integrity.</p>
                    </div>
                </div>

                <div class="stream-card scroll-anim" data-anim="right">
                    <div>
                        <div class="stream-icon" style="background-color: #dcfce7; color: #166534;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                        </div>
                        <h3>Core Values</h3>
                        <p>Integrity, Excellence, Inclusivity, Environmental Responsibility, and Lifelong Intellectual Curiosity.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Executive Leadership Profiles (Exact School-Demo) -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Executive Directorate</div>
                <h2 class="section-h2">Academic Leadership Team</h2>
            </div>

            <div class="leadership-grid">
                <div class="leader-card scroll-anim" data-anim="left">
                    <div class="leader-quote">
                        "Education is not merely the transmission of information; it is the ignition of character and purpose. At Apex Model School, we ensure every student gains the confidence to lead in an interconnected world."
                    </div>
                    <div class="leader-meta">
                        <img src="{{ asset('landing_assets/images/principal.jpg') }}" alt="Prof. Dr. K. P. Adhikari" class="leader-avatar">
                        <div>
                            <h3>Prof. Dr. K. P. Adhikari</h3>
                            <p>Principal (Ph.D. in Education Administration)</p>
                        </div>
                    </div>
                </div>

                <div class="leader-card scroll-anim" data-anim="right">
                    <div class="leader-quote">
                        "We continuously upgrade our laboratory infrastructure, teacher training frameworks, and digital learning assets to provide scholars with exceptional academic opportunities."
                    </div>
                    <div class="leader-meta">
                        <img src="{{ asset('landing_assets/images/director.jpg') }}" alt="Mrs. Sunita Sharma" class="leader-avatar">
                        <div>
                            <h3>Mrs. Sunita Sharma</h3>
                            <p>Academic Director (M.Sc., 20+ Yrs Curriculum Leadership)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- School Pamphlet Lightbox Modal (Exact School-Demo) -->
    <div id="pamphletBackdrop" class="modal-backdrop" onclick="closePamphletModal()"></div>
    <div id="pamphletModal" class="modal-window" style="max-width: 860px; text-align: center;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--primary-navy);">Apex Model Secondary School — Official Prospectus Poster</h3>
            <button onclick="closePamphletModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <img src="{{ asset('landing_assets/images/school_pamphlet.jpg') }}" alt="Official School Pamphlet" style="width: 100%; height: auto; border-radius: var(--radius-md); box-shadow: var(--shadow-lg);">
    </div>
@endsection

@push('scripts')
<script>
    function openPamphletModal() {
        document.getElementById('pamphletBackdrop').classList.add('active');
        document.getElementById('pamphletModal').classList.add('active');
    }
    function closePamphletModal() {
        document.getElementById('pamphletBackdrop').classList.remove('active');
        document.getElementById('pamphletModal').classList.remove('active');
    }
</script>
@endpush
