@extends('layouts.landing')

@section('title', ($settings['system_name'] ?? 'Apex Model Secondary School') . ' - Official Campus Portal')
@section('meta_description', 'Official portal of Apex Model Secondary School. Excellence in Early Childhood, Primary, Lower Secondary, Secondary, and 10+2 Education in Koshi Province, Nepal.')

@section('content')
    <!-- Dynamic Hero Section with Real Campus Drone Video (Exact School-Demo) -->
    <section class="hero-section">
        <video class="hero-video-bg" autoplay loop muted playsinline poster="{{ asset('landing_assets/images/facilities/robotics_lab.jpg') }}">
            <source src="{{ asset('landing_assets/videos/hero-drone.mp4') }}" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                @if(($settings['admissions_open'] ?? '1') == '1')
                    <div class="hero-badge-pill">
                        ✨ Admissions Open for Academic Session 2083/84 B.S.
                    </div>
                @endif

                <h1 class="hero-h1">
                    Inspiring Academic Excellence,<br><span>Leadership & Character.</span>
                </h1>

                <p class="hero-body-text prose-constrained">
                    Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.
                </p>

                <div class="hero-cta-group">
                    <a href="{{ route('landing.events') }}" class="btn btn-primary">
                        Explore Campus Life & Events →
                    </a>
                    <a href="{{ route('landing.contact') }}" class="btn btn-gold">
                        Admissions Inquiry
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Rolling Metrics Counter Strip (Exact School-Demo) -->
    <div class="container">
        <div class="metrics-strip scroll-anim" data-anim="up">
            <div class="metrics-grid">
                <div class="metric-item">
                    <div class="metric-number"><span class="counter-val" data-target="{{ $settings['stats_heritage_years'] ?? 30 }}">0</span><span>+</span></div>
                    <div class="metric-label">Years of Academic Heritage</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number"><span class="counter-val" data-target="{{ $settings['stats_enrolled_scholars'] ?? 1400 }}">0</span><span>+</span></div>
                    <div class="metric-label">Active Enrolled Scholars</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number"><span class="counter-val" data-target="{{ $settings['stats_pass_rate'] ?? 100 }}">0</span><span>%</span></div>
                    <div class="metric-label">Board Examination Success</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number"><span class="counter-val" data-target="{{ $settings['stats_faculty_count'] ?? 65 }}">0</span><span>+</span></div>
                    <div class="metric-label">Dedicated Qualified Faculty</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Streams & Levels Section with Real Image Cards (Exact School-Demo) -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Academic Pathways</span>
                <h2 class="section-h2">Comprehensive Academic Programs</h2>
                <p class="section-desc prose-constrained">Structured curriculum approved by the Ministry of Education & National Examination Board (NEB) fostering intellectual curiosity at every age.</p>
            </div>

            <div class="streams-grid">
                <div class="stream-card scroll-anim" data-anim="left">
                    <img src="{{ asset('landing_assets/images/events/assembly.jpg') }}" alt="Pre-Primary & Primary" class="stream-card-img">
                    <div class="stream-card-body">
                        <h3>Pre-Primary & Primary</h3>
                        <p>Playgroup to Grade 5 focusing on foundational literacy, numeracy, creative arts, and joyful inquiry-based learning environments.</p>
                        <span style="font-size: 0.8rem; font-weight: 800; color: var(--accent-blue);">Grades PG – 5</span>
                    </div>
                </div>

                <div class="stream-card scroll-anim" data-anim="up">
                    <img src="{{ asset('landing_assets/images/facilities/computer_lab.jpg') }}" alt="Lower Secondary" class="stream-card-img">
                    <div class="stream-card-body">
                        <h3>Lower Secondary (BLE)</h3>
                        <p>Grades 6 to 8 building strong analytical skills in mathematics, environmental science, digital literacy, and languages.</p>
                        <span style="font-size: 0.8rem; font-weight: 800; color: var(--accent-gold);">Grades 6 – 8</span>
                    </div>
                </div>

                <div class="stream-card scroll-anim" data-anim="up">
                    <img src="{{ asset('landing_assets/images/facilities/robotics_lab.jpg') }}" alt="Secondary SEE Prep" class="stream-card-img">
                    <div class="stream-card-body">
                        <h3>Secondary (SEE Prep)</h3>
                        <p>Grades 9 & 10 preparing scholars for Secondary Education Examination (SEE) with intensive laboratory experiments and tutorials.</p>
                        <span style="font-size: 0.8rem; font-weight: 800; color: #166534;">Grades 9 – 10</span>
                    </div>
                </div>

                <div class="stream-card scroll-anim" data-anim="right">
                    <img src="{{ asset('landing_assets/images/facilities/library.jpg') }}" alt="Senior Secondary 10+2" class="stream-card-img">
                    <div class="stream-card-body">
                        <h3>Senior Secondary 10+2</h3>
                        <p>NEB affiliated streams in Science, Management, and Humanities equipping students for university admissions and career leadership.</p>
                        <span style="font-size: 0.8rem; font-weight: 800; color: #7e22ce;">Grades 11 – 12</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Facilities Showcase Section (Exact School-Demo) -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Campus Infrastructure</span>
                <h2 class="section-h2">World-Class Learning Facilities</h2>
                <p class="section-desc prose-constrained">State-of-the-art infrastructure designed to foster hands-on exploration, physical health, and technological mastery.</p>
            </div>

            <div class="facilities-grid">
                <div class="facility-card scroll-anim" data-anim="left">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/facilities/robotics_lab.jpg') }}" alt="Robotics & STEM Lab">
                    </div>
                    <div class="facility-body">
                        <h3>Advanced STEM & Robotics Lab</h3>
                        <p>Equipped with 3D printers, microcontroller kits, and robotic assembly stations empowering practical engineering mindset.</p>
                    </div>
                </div>

                <div class="facility-card scroll-anim" data-anim="up">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/facilities/computer_lab.jpg') }}" alt="Computer Laboratory">
                    </div>
                    <div class="facility-body">
                        <h3>High-Speed Computer Lab</h3>
                        <p>Fully networked workstations with high-speed fiber connectivity, programming IDEs, and multimedia software.</p>
                    </div>
                </div>

                <div class="facility-card scroll-anim" data-anim="right">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/facilities/library.jpg') }}" alt="Resource Center">
                    </div>
                    <div class="facility-body">
                        <h3>Knowledge Resource Center</h3>
                        <p>Comprehensive physical and digital library featuring over 10,000 academic titles, periodicals, and quiet study zones.</p>
                    </div>
                </div>

                <div class="facility-card scroll-anim" data-anim="left">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/facilities/sports_ground.jpg') }}" alt="Sports Grounds">
                    </div>
                    <div class="facility-body">
                        <h3>Sports Complex & Futsal Ground</h3>
                        <p>Dedicated basketball courts, badminton courts, track grounds, and futsal arena supporting house championships.</p>
                    </div>
                </div>

                <div class="facility-card scroll-anim" data-anim="up">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/facilities/school_bus.jpg') }}" alt="School Transport">
                    </div>
                    <div class="facility-body">
                        <h3>Safe Student Transport Fleets</h3>
                        <p>Modern bus fleet covering major transportation corridors across Koshi Province with trained safety attendants.</p>
                    </div>
                </div>

                <div class="facility-card scroll-anim" data-anim="right">
                    <div class="facility-img-wrap">
                        <img src="{{ asset('landing_assets/images/events/science_fair.jpg') }}" alt="Science Laboratories">
                    </div>
                    <div class="facility-body">
                        <h3>Integrated Science Laboratories</h3>
                        <p>Individual Physics, Chemistry, and Biology laboratories enabling real-world scientific experimentation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- School Leadership Vision Section (Exact School-Demo) -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Leadership Vision</span>
                <h2 class="section-h2">Guided by Educational Pioneers</h2>
                <p class="section-desc prose-constrained">Meet the dedicated leaders steering {{ $settings['system_name'] ?? 'our institution' }} towards academic distinction.</p>
            </div>

            <div class="leadership-grid">
                <div class="leader-card scroll-anim" data-anim="left">
                    <div class="leader-header-block">
                        <img src="{{ asset('landing_assets/images/principal.jpg') }}" alt="{{ !empty($settings['principal_name']) ? $settings['principal_name'] : 'Principal' }}" class="leader-avatar-lg">
                        <div>
                            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--primary-navy);">{{ !empty($settings['principal_name']) ? $settings['principal_name'] : 'Principal' }}</h3>
                            <p style="font-size: 0.85rem; color: var(--accent-gold); font-weight: 700; margin-top: 0.2rem;">{{ !empty($settings['principal_title']) ? $settings['principal_title'] : 'Principal & Academic Head' }}</p>
                            <span style="font-size: 0.75rem; background-color: #dbeafe; color: #1e40af; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; display: inline-block; margin-top: 0.4rem;">Academic Administration</span>
                        </div>
                    </div>
                    <div class="leader-quote">
                        "Our institution stands as a beacon of academic excellence and holistic student formation. Our mission is to inspire critical thinking, foster ethical leadership, and equip scholars with 21st-century competencies."
                    </div>
                </div>

                <div class="leader-card scroll-anim" data-anim="right">
                    <div class="leader-header-block">
                        <img src="{{ asset('landing_assets/images/director.jpg') }}" alt="{{ !empty($settings['director_name']) ? $settings['director_name'] : 'Academic Director' }}" class="leader-avatar-lg">
                        <div>
                            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--primary-navy);">{{ !empty($settings['director_name']) ? $settings['director_name'] : 'Academic Director' }}</h3>
                            <p style="font-size: 0.85rem; color: var(--accent-gold); font-weight: 700; margin-top: 0.2rem;">{{ !empty($settings['director_title']) ? $settings['director_title'] : 'Director of Academics & Curriculum' }}</p>
                            <span style="font-size: 0.75rem; background-color: #dcfce7; color: #166534; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; display: inline-block; margin-top: 0.4rem;">Curriculum Directorate</span>
                        </div>
                    </div>
                    <div class="leader-quote">
                        "Our forward-thinking curriculum integrates rigorous STEM inquiry, modern robotics laboratories, and rich co-curricular engagement, ensuring every student discovers their potential and leads with integrity."
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent School Life & Events Strip (Exact School-Demo) -->
    <section class="section section-alt">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="section-tag">Campus Life</span>
                    <h2 class="section-h2" style="margin-bottom: 0;">Recent Happenings & Events</h2>
                </div>
                <a href="{{ route('landing.events') }}" class="btn btn-outline btn-sm">View All Gallery Events →</a>
            </div>

            <div class="events-grid">
                @foreach(($recentEvents ?? $recent_events ?? []) as $event)
                    @php
                        $imgs = json_decode($event->image_paths, true) ?? [$event->image_paths];
                        $imgCount = is_array($imgs) ? count($imgs) : 1;
                        $firstImg = is_array($imgs) && count($imgs) > 0 ? $imgs[0] : $event->image_paths;
                        if(strpos($firstImg, 'assets/') === 0) {
                            $firstImg = 'landing_assets/' . substr($firstImg, 7);
                        }
                        $animDirs = ['left', 'up', 'right'];
                        $currentAnim = $animDirs[$loop->index % 3];
                    @endphp
                    <div class="event-card scroll-anim" data-anim="{{ $currentAnim }}" onclick="location.href='{{ route('landing.events') }}'">
                        <div class="event-img-wrap">
                            <span class="category-pill">{{ $event->category }}</span>
                            @if($imgCount > 1)
                                <span class="multi-photo-badge">📷 {{ $imgCount }} Photos</span>
                            @endif
                            <img src="{{ asset($firstImg) }}" alt="{{ $event->title }}">
                        </div>
                        <div class="event-body">
                            <div class="event-date">📅 {{ $event->event_date_bs }} B.S.</div>
                            <h3>{{ $event->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($event->description, 110, '...') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
