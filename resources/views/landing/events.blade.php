@extends('layouts.landing')

@section('title', 'Campus Life & Events Gallery - ' . ($settings['system_name'] ?? 'Apex Model Secondary School'))
@section('meta_description', 'Explore vibrant campus events, annual sports championships, STEM science exhibitions, and cultural functions at Apex Model Secondary School.')

@section('extra_css')
<style>
    .search-bar-wrap {
        max-width: 600px;
        margin: 0 auto 3rem auto;
        position: relative;
    }
    .search-bar-input {
        width: 100%;
        padding: 0.85rem 1.25rem 0.85rem 2.85rem;
        border-radius: var(--radius-full);
        border: 1px solid var(--border-subtle);
        box-shadow: var(--shadow-sm);
        font-size: 1rem;
        outline: none;
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .search-bar-input:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }
    .modal-gallery-grid {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .modal-gallery-thumb {
        width: 72px;
        height: 52px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
        opacity: 0.6;
        border: 2px solid transparent;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .modal-gallery-thumb:hover, .modal-gallery-thumb.active {
        opacity: 1;
        border-color: var(--accent-blue);
    }
</style>
@endsection

@section('content')
    <!-- Page Header Hero Banner with Campus Event Background Image (Exact School-Demo) -->
    <section class="hero-section">
        <div class="hero-video-bg" style="background-image: url('{{ asset('landing_assets/images/events/science_fair.jpg') }}'); background-size: cover; background-position: center; opacity: 0.65;"></div>
        <div class="hero-overlay" style="background: linear-gradient(180deg, rgba(11, 30, 56, 0.85) 0%, rgba(11, 30, 56, 0.7) 50%, rgba(11, 30, 56, 0.9) 100%);"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge-pill">
                    📸 Campus Co-Curricular & Event Gallery
                </div>
                <h1 class="hero-h1" style="font-size: 2.85rem;">
                    Campus Life & Events Gallery
                </h1>
                <p class="hero-body-text prose-constrained" style="font-size: 1.15rem; margin-bottom: 0;">
                    A vibrant showcase of student talent, STEM science fairs, athletic championships, and annual cultural celebrations at Apex Model School.
                </p>
            </div>
        </div>
    </section>

    <!-- Events Grid Section (Exact School-Demo) -->
    <main class="section">
        <div class="container">

            <!-- Live Keyword Search Bar -->
            <div class="search-bar-wrap">
                <input type="text" id="eventSearchInput" class="search-bar-input" placeholder="Search event by name, keyword, or category (e.g. Science Fair, Sports)...">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            </div>

            <!-- Events Grid -->
            <div class="events-grid">
                @foreach($events as $ev)
                    @php
                        $imgs = json_decode($ev->image_paths, true) ?? [$ev->image_paths];
                        $imgCount = is_array($imgs) ? count($imgs) : 1;
                        $firstImg = is_array($imgs) && count($imgs) > 0 ? $imgs[0] : $ev->image_paths;
                        if(strpos($firstImg, 'assets/') === 0) {
                            $firstImg = 'landing_assets/' . substr($firstImg, 7);
                        }
                        $formattedImgs = [];
                        if (is_array($imgs)) {
                            foreach($imgs as $im) {
                                if(strpos($im, 'assets/') === 0) {
                                    $formattedImgs[] = asset('landing_assets/' . substr($im, 7));
                                } else {
                                    $formattedImgs[] = asset($im);
                                }
                            }
                        } else {
                            $formattedImgs[] = asset($firstImg);
                        }
                        $eventData = [
                            'id' => $ev->id,
                            'title' => $ev->title,
                            'category' => $ev->category,
                            'event_date_bs' => $ev->event_date_bs,
                            'description' => $ev->description,
                            'image_paths' => json_encode($formattedImgs)
                        ];
                        $eventDir = $loop->index % 3 === 0 ? 'left' : ($loop->index % 3 === 1 ? 'up' : 'right');
                    @endphp
                    <div class="event-card event-card-item scroll-anim" data-anim="{{ $eventDir }}" data-category="{{ $ev->category }}" onclick='showEventDetails(@json($eventData))'>
                        <div class="event-img-wrap">
                            <span class="category-pill">{{ $ev->category }}</span>
                            @if($imgCount > 1)
                                <span class="multi-photo-badge">📷 {{ $imgCount }} Photos</span>
                            @endif
                            <img src="{{ asset($firstImg) }}" alt="{{ $ev->title }}">
                        </div>
                        <div class="event-body">
                            <div class="event-date">📅 {{ $ev->event_date_bs }} B.S.</div>
                            <h3>{{ $ev->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($ev->description, 110, '...') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($events, 'links') && $events->hasPages())
                <div style="margin-top: 2.5rem; display: flex; justify-content: center;">
                    {{ $events->links() }}
                </div>
            @endif

        </div>
    </main>
@endsection

@push('scripts')
<script>
    function showEventDetails(ev) {
        let images = [];
        try {
            images = typeof ev.image_paths === 'string' ? JSON.parse(ev.image_paths) : ev.image_paths;
        } catch (e) {
            images = [ev.image_paths];
        }

        let mainImgSrc = images[0] || '{{ asset("landing_assets/images/events/science_fair.jpg") }}';
        
        let thumbsHtml = '';
        if (images.length > 1) {
            thumbsHtml = '<div class="modal-gallery-grid">';
            images.forEach((img, idx) => {
                thumbsHtml += `
                    <img src="${img}" class="modal-gallery-thumb ${idx === 0 ? 'active' : ''}" onclick="switchModalImage('${img}', this)" alt="Thumbnail ${idx + 1}">
                `;
            });
            thumbsHtml += '</div>';
        }

        const content = `
            <img id="mainModalImage" src="${mainImgSrc}" alt="${ev.title}" style="width: 100%; height: 280px; object-fit: cover; border-radius: 10px; margin-bottom: 0.85rem; box-shadow: var(--shadow-md);">
            ${thumbsHtml}
            <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 1rem; margin-bottom: 0.75rem;">
                <span class="category-pill" style="position: static; font-size: 0.75rem;">${ev.category}</span>
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--accent-gold);">📅 ${ev.event_date_bs} B.S.</span>
            </div>
            <p style="font-size: 0.95rem; color: var(--text-body); line-height: 1.65; margin-bottom: 1.5rem;">${ev.description}</p>
            <button onclick="closeModal()" class="btn btn-outline" style="width: 100%;">Close Gallery</button>
        `;
        openModal(ev.title, content);
    }

    function switchModalImage(src, thumbEl) {
        const mainImg = document.getElementById('mainModalImage');
        if (mainImg) {
            mainImg.src = src;
        }
        document.querySelectorAll('.modal-gallery-thumb').forEach(t => t.classList.remove('active'));
        if (thumbEl) {
            thumbEl.classList.add('active');
        }
    }
</script>
@endpush
