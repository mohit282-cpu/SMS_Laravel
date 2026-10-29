@extends('layouts.landing')

@section('title', 'Contact & Admissions Desk - ' . ($settings['system_name'] ?? 'Apex Model Secondary School'))
@section('meta_description', 'Contact Apex Model Secondary School admissions office. Enquire about admissions from Nursery to Grade 12 in Koshi Province, Nepal.')

@section('extra_css')
<style>
    .contact-grid-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
    }
    @media (max-width: 900px) {
        .contact-grid-layout { grid-template-columns: 1fr; }
    }
    .contact-card-wrap {
        background-color: var(--surface-white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-subtle);
        padding: 2.25rem;
        box-shadow: var(--shadow-sm);
    }
    .form-label {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--primary-navy);
        display: block;
        margin-bottom: 0.35rem;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem 0.9rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-subtle);
        font-size: 0.9375rem;
        outline: none;
        background-color: #fafafa;
        transition: border-color var(--transition-fast), background-color var(--transition-fast);
        box-sizing: border-box;
    }
    .form-control:focus {
        border-color: var(--accent-blue);
        background-color: #ffffff;
    }
    .info-item {
        padding-bottom: 1.2rem;
        border-bottom: 1px solid var(--border-subtle);
    }
    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
</style>
@endsection

@section('content')
    <!-- Page Header Banner (Exact School-Demo) -->
    <section class="section section-alt" style="padding: 6.5rem 0 2rem 0;">
        <div class="container">
            <div style="text-align: center;">
                <span class="section-tag">Admissions Office</span>
                <h1 class="section-h2" style="margin-top: 0.25rem;">Contact & Admissions Desk</h1>
                <p class="section-desc prose-constrained">Reach out to our administration desk for inquiries regarding admissions, campus visits, or academic programs.</p>
            </div>
        </div>
    </section>

    <!-- Main Content (Exact School-Demo) -->
    <main class="section">
        <div class="container">
            <div class="contact-grid-layout">
                
                <!-- Inquiry Form (Left Column) -->
                <div class="contact-card-wrap scroll-anim" data-anim="left">
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.4rem;">Send an Inquiry</h2>
                    <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.5rem;">Submit your details below and our admissions desk will get back to you.</p>

                    @if(session('success_msg'))
                        <div style="background-color: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.9rem;">
                            {{ session('success_msg') }}
                        </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                        <div style="background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.9rem;">
                            <ul style="margin: 0; padding-left: 1.2rem;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('landing.submit_inquiry') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.1rem;">
                        @csrf

                        <div>
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. Ramesh Kumar Adhikari" value="{{ old('full_name') }}" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" class="admin-grid-2col">
                            <div>
                                <label class="form-label" for="phone">Phone / Mobile *</label>
                                <input type="tel" name="phone" id="phone" class="form-control" placeholder="+977-9800000000" value="{{ old('phone') }}" required>
                            </div>
                            <div>
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="{{ old('email') }}">
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="academic_level">Seeking Admission Level</label>
                            <select name="academic_level" id="academic_level" class="form-control">
                                <option value="Pre-Primary & Primary (PG - Grade 5)">Pre-Primary & Primary (PG - Grade 5)</option>
                                <option value="Lower Secondary (Grades 6 - 8)">Lower Secondary (Grades 6 - 8)</option>
                                <option value="Secondary SEE (Grades 9 & 10)">Secondary SEE (Grades 9 & 10)</option>
                                <option value="Senior Secondary 10+2 Science">Senior Secondary 10+2 Science</option>
                                <option value="Senior Secondary 10+2 Management">Senior Secondary 10+2 Management</option>
                                <option value="Senior Secondary 10+2 Humanities">Senior Secondary 10+2 Humanities</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="message">Inquiry Message *</label>
                            <textarea name="message" id="message" rows="4" class="form-control" placeholder="Write your inquiry or question here..." required>{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; margin-top: 0.25rem;">
                            Submit Inquiry →
                        </button>
                    </form>
                </div>

                <!-- Campus Details & Map (Right Column) -->
                <div class="contact-card-wrap scroll-anim" data-anim="right" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 1.25rem;">Campus Information</h2>

                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            <div class="info-item">
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Address & Location</span>
                                <div style="font-size: 0.95rem; font-weight: 700; color: var(--primary-navy); margin-top: 0.2rem;">{{ $settings['system_name'] ?? 'School Management System Academy' }}</div>
                                <div style="font-size: 0.875rem; color: var(--text-body);">{{ !empty($settings['address']) ? $settings['address'] : 'Campus Administration Desk' }}</div>
                            </div>

                            <div class="info-item">
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Telephone & Email</span>
                                <div style="font-size: 0.875rem; color: var(--text-body); margin-top: 0.2rem;">Phone: <strong>{{ !empty($settings['phone']) ? $settings['phone'] : 'Not Configured' }}</strong></div>
                                <div style="font-size: 0.875rem; color: var(--text-body);">Email: <strong>{{ !empty($settings['system_email']) ? $settings['system_email'] : 'Not Configured' }}</strong></div>
                            </div>

                            <div class="info-item">
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Office Operating Hours</span>
                                <div style="font-size: 0.875rem; color: var(--text-body); margin-top: 0.2rem;">Sunday – Friday: <strong>8:00 AM – 4:00 PM (B.S.)</strong></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Closed on Saturdays and official Nepalese public holidays.</div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; height: 220px; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-subtle);">
                        <iframe src="https://maps.google.com/maps?q=Sunsari,%20Koshi%20Province,%20Nepal&t=&z=13&ie=UTF8&iwloc=&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>

            </div>
        </div>
    </main>
@endsection
