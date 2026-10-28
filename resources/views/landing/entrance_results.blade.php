<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Examination Result Portal - {{ $settings['system_name'] ?? 'Apex Model Secondary School' }}</title>
    <meta name="theme-color" content="#091b33">
    <link rel="icon" type="image/png" href="{{ asset('landing_assets/icons/icon-192.png') }}">

    <!-- Tailwind CSS CDN (Exact School-Demo Results Engine) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            800: '#091b33',
                            900: '#051124',
                        },
                        gold: {
                            500: '#d97706',
                            600: '#b45309',
                        },
                        crimson: '#881337',
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Outfit', sans-serif;
        }
        
        /* Print styling for A4 Marksheet */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            #marksheetContainer {
                display: block !important;
                box-shadow: none !important;
                border: 2px solid #0f172a !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 1.5rem !important;
                page-break-inside: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 12mm;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <!-- Top Navigation Header (Exact School-Demo) -->
    <header class="bg-navy-800 text-white shadow-md no-print">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('landing.home') }}" class="flex items-center gap-3 text-white no-underline">
                    <img src="{{ asset('landing_assets/images/logo.png') }}" alt="Apex Logo" class="w-12 h-12 object-contain bg-white/10 rounded-full p-1 border border-white/20">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-white leading-tight">{{ $settings['system_name'] ?? 'Apex Model Secondary School' }}</h1>
                        <p class="text-xs text-amber-300 font-medium tracking-wide">Educating Minds, Empowering Futures</p>
                    </div>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('landing.home') }}" class="px-3.5 py-1.5 text-xs font-semibold bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all border border-white/20">
                    ← Main Website
                </a>
                @if($isVisible)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Portal Active
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Portal Hidden
                </span>
                @endif
                @guest
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 text-xs font-semibold bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all border border-white/20">
                        Admin Portal 🔐
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-all">
                        Dashboard ⚡
                    </a>
                @endguest
            </div>
        </div>
    </header>

    @if(!$isVisible && $isSuperAdmin)
        <div class="bg-amber-500 text-navy-900 px-4 py-2.5 text-center text-xs sm:text-sm font-semibold shadow-inner border-b border-amber-600 no-print">
            <span class="inline-flex items-center justify-center flex-wrap gap-2">
                <svg class="w-4 h-4 fill-current flex-shrink-0" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span><strong>Super Admin Preview Notice:</strong> Entrance Results Portal is set to <strong>HIDDEN</strong> in Landing Settings. Regular visitors cannot see or access this page. You can enable it anytime in <a href="{{ route('landing_settings.index') }}" class="underline font-black hover:text-black">Landing Page Settings</a>.</span>
            </span>
        </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-grow max-w-4xl w-full mx-auto px-4 py-8">
        @if(!$isVisible && !$isSuperAdmin)
        <!-- Portal Inactive Message for Public Visitors -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 text-center max-w-2xl mx-auto my-12">
            <div class="w-20 h-20 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-6 border border-amber-200 shadow-sm">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m11-3.5a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <span class="inline-block text-xs font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-3.5 py-1 rounded-full mb-3">Portal Under Scheduled Review</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-navy-800 mb-3">Entrance Results Not Currently Available</h2>
            <p class="text-sm sm:text-base text-slate-600 mb-8 leading-relaxed">
                Online entrance examination verification for this admission cycle has been closed or is undergoing administrative evaluation. Verified results will be published once officially authorized by the examination committee.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('landing.home') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-navy-800 hover:bg-navy-900 text-white font-bold text-sm transition-all shadow-md">
                    Return to Homepage
                </a>
                <a href="{{ route('landing.contact') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-all border border-slate-300">
                    Contact Admissions Desk
                </a>
            </div>
        </div>
        @else
        <!-- Search Section -->
        <div class="no-print bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-8 mb-8">
            <div class="text-center mb-6">
                <span class="inline-block text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-3 py-1 rounded-full border border-amber-200 mb-2">
                    Official Grade-Sheet Engine
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-navy-800">Entrance & Admission Result Lookup</h2>
                <p class="text-sm text-slate-600 mt-1">Select your examination details and enter your symbol number or candidate name to fetch your verified marksheet.</p>
            </div>

            <form id="searchForm" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Academic Batch <span class="text-rose-600">*</span></label>
                        <select id="batch_year" name="batch_year" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-medium focus:ring-2 focus:ring-navy-800 focus:bg-white transition-all">
                            @if(empty($batches) || count($batches) == 0)
                                <option value="2083 B.S.">2083 B.S.</option>
                                <option value="2082 B.S.">2082 B.S.</option>
                            @else
                                @foreach($batches as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Select Examination <span class="text-rose-600">*</span></label>
                        <select id="exam_name" name="exam_name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-medium focus:ring-2 focus:ring-navy-800 focus:bg-white transition-all">
                            @if(empty($exams) || count($exams) == 0)
                                <option value="Second Mid-Terminal Examination 2083">Second Mid-Terminal Examination 2083</option>
                            @else
                                @foreach($exams as $e)
                                    <option value="{{ $e }}">{{ $e }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Select Grade / Class <span class="text-rose-600">*</span></label>
                        <select id="class_name" name="class_name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-medium focus:ring-2 focus:ring-navy-800 focus:bg-white transition-all">
                            @if(empty($classes) || count($classes) == 0)
                                <option value="Grade: 1">Grade: 1</option>
                                <option value="Grade: 8">Grade: 8</option>
                                <option value="Grade: 9">Grade: 9</option>
                            @else
                                @foreach($classes as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Symbol Number <span class="text-rose-600">*</span></label>
                        <input type="text" id="symbol_no" name="symbol_no" placeholder="e.g. 1001" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-medium focus:ring-2 focus:ring-navy-800 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Student Name or Roll No <span class="text-rose-600">*</span></label>
                        <input type="text" id="verification_query" name="verification_query" placeholder="Verification: e.g. Pooja or 1" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-medium focus:ring-2 focus:ring-navy-800 focus:bg-white transition-all">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="searchBtn" class="w-full bg-navy-800 hover:bg-navy-900 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-md flex items-center justify-center gap-2 text-sm tracking-wide">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        Search & Display Marksheet
                    </button>
                </div>
            </form>

            <div id="errorMsg" class="hidden mt-4 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span id="errorText"></span>
            </div>
        </div>

        <!-- Marksheet Container (Hidden by default until search) -->
        <div id="marksheetContainer" class="hidden bg-white border-2 border-slate-900 rounded-xl p-6 sm:p-8 shadow-2xl relative">
            
            <!-- Print Action Bar (Top) -->
            <div class="no-print mb-6 pb-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Official Marksheet Generated</span>
                <button onclick="window.print()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-5 rounded-lg text-sm transition-all shadow flex items-center gap-2">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                    Download / Print Marksheet
                </button>
            </div>

            <!-- Header Block with Double Line Accent -->
            <div class="text-center border-b-4 border-double border-navy-800 pb-4 mb-6">
                <div class="flex items-center justify-center gap-4 mb-2">
                    <img src="{{ asset('landing_assets/images/logo.png') }}" alt="Apex Emblem" class="w-16 h-16 object-contain">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-navy-800 tracking-tight uppercase">{{ $settings['system_name'] ?? 'Apex Model Secondary School' }}</h2>
                        <p class="text-xs font-bold text-rose-800 tracking-wider">Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal</p>
                        <p class="text-xs text-slate-600 font-medium">Estd. 2052 B.S. (1995 A.D.) | Phone: +977-25-560000 | Web: apexschool.edu.np</p>
                    </div>
                </div>
                <div class="inline-block mt-2 bg-navy-800 text-amber-300 font-bold px-6 py-1 rounded-full text-xs uppercase tracking-widest shadow-sm">
                    <span id="ms_exam_title">ENTRANCE & MID-TERMINAL EXAMINATION / GRADE-SHEET</span>
                </div>
            </div>

            <!-- Student Metadata Grid -->
            <div class="bg-slate-50 border border-slate-300 rounded-lg p-4 mb-6 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6">
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Student Name:</span> <span id="ms_student_name" class="font-black text-slate-900 uppercase"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Grade / Class:</span> <span id="ms_class" class="font-bold text-slate-900"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Symbol Number:</span> <span id="ms_symbol" class="font-mono font-bold text-slate-900"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Roll Number:</span> <span id="ms_roll" class="font-mono font-bold text-slate-900"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Section:</span> <span id="ms_section" class="font-bold text-slate-900"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Attendance:</span> <span id="ms_attendance" class="font-bold text-slate-900"></span></div>
                    <div class="flex"><span class="font-bold text-slate-700 w-32 uppercase text-xs">Academic Batch:</span> <span id="ms_batch" class="font-bold text-slate-900"></span></div>
                </div>
            </div>

            <!-- Main Marks Table -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-left border-collapse border border-slate-400 text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-navy-800 text-white font-bold uppercase tracking-wider text-xs">
                            <th class="border border-slate-400 px-3 py-2.5 text-center w-12">S.N.</th>
                            <th class="border border-slate-400 px-4 py-2.5">Subjects</th>
                            <th class="border border-slate-400 px-3 py-2.5 text-center">Full Marks</th>
                            <th class="border border-slate-400 px-3 py-2.5 text-center">Final Grade</th>
                            <th class="border border-slate-400 px-3 py-2.5 text-center">Grade Point (GP)</th>
                        </tr>
                    </thead>
                    <tbody id="ms_table_body">
                        <!-- Rows injected via JS -->
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border border-slate-400">
                            <td colspan="2" class="border border-slate-400 px-4 py-2.5 text-right uppercase text-xs">Grade Point Average (GPA):</td>
                            <td colspan="3" class="border border-slate-400 px-4 py-2.5 text-center text-sm font-black text-navy-800 bg-amber-50">
                                <span id="ms_gpa_val" class="text-base font-black"></span> / 4.0
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Remarks Strip -->
            <div class="bg-amber-50/80 border border-amber-300 rounded-lg px-4 py-2.5 mb-6 text-xs sm:text-sm font-bold text-amber-950 flex items-center justify-between">
                <div>REMARKS: <span id="ms_remarks" class="text-rose-900 font-extrabold uppercase"></span></div>
                <div class="text-slate-600 font-semibold text-xs">RESULT STATUS: <span class="text-emerald-700">VERIFIED</span></div>
            </div>

            <!-- Grading Rubric Reference Table -->
            <div class="mb-8">
                <h4 class="text-xs font-bold uppercase text-slate-700 mb-2 tracking-wider">Nepal National Grading System Standards (GPA Scale)</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse border border-slate-300 text-[10px] sm:text-xs">
                        <thead>
                            <tr class="bg-slate-200 text-slate-800 font-bold">
                                <th class="border border-slate-300 px-1.5 py-1">Interval %</th>
                                <th class="border border-slate-300 px-1.5 py-1">Grade</th>
                                <th class="border border-slate-300 px-1.5 py-1">Grade Point</th>
                                <th class="border border-slate-300 px-1.5 py-1">Description</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700 font-medium">
                            <tr><td class="border border-slate-300 py-0.5">90% - 100%</td><td class="border border-slate-300 font-bold text-emerald-700">A+</td><td class="border border-slate-300">4.0</td><td class="border border-slate-300">Outstanding</td></tr>
                            <tr class="bg-slate-50"><td class="border border-slate-300 py-0.5">80% - 89%</td><td class="border border-slate-300 font-bold text-emerald-600">A</td><td class="border border-slate-300">3.6</td><td class="border border-slate-300">Excellent</td></tr>
                            <tr><td class="border border-slate-300 py-0.5">70% - 79%</td><td class="border border-slate-300 font-bold text-blue-700">B+</td><td class="border border-slate-300">3.2</td><td class="border border-slate-300">Very Good</td></tr>
                            <tr class="bg-slate-50"><td class="border border-slate-300 py-0.5">60% - 69%</td><td class="border border-slate-300 font-bold text-blue-600">B</td><td class="border border-slate-300">2.8</td><td class="border border-slate-300">Good</td></tr>
                            <tr><td class="border border-slate-300 py-0.5">50% - 59%</td><td class="border border-slate-300 font-bold text-amber-700">C+</td><td class="border border-slate-300">2.4</td><td class="border border-slate-300">Satisfactory</td></tr>
                            <tr class="bg-slate-50"><td class="border border-slate-300 py-0.5">40% - 49%</td><td class="border border-slate-300 font-bold text-amber-600">C</td><td class="border border-slate-300">2.0</td><td class="border border-slate-300">Acceptable</td></tr>
                            <tr><td class="border border-slate-300 py-0.5">35% - 39%</td><td class="border border-slate-300 font-bold text-orange-700">D</td><td class="border border-slate-300">1.6</td><td class="border border-slate-300">Basic</td></tr>
                            <tr class="bg-slate-50"><td class="border border-slate-300 py-0.5">Below 35%</td><td class="border border-slate-300 font-bold text-rose-700">NG</td><td class="border border-slate-300">0.0</td><td class="border border-slate-300">Not Graded</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="pt-6 grid grid-cols-3 gap-4 items-end text-center border-t border-slate-300">
                <div>
                    <p class="text-xs font-bold text-slate-700">Issue Date:</p>
                    <p id="ms_issue_date" class="text-xs font-mono font-bold text-slate-900 mt-1"></p>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-full border-2 border-dashed border-rose-800/60 flex items-center justify-center text-[10px] font-bold text-rose-900/80 uppercase text-center p-1 leading-none bg-rose-50/40">
                        Official<br>School Seal
                    </div>
                </div>
                <div>
                    <div class="h-10 flex items-center justify-center">
                        <svg class="w-28 h-10 text-rose-800" viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 40 C30 10, 50 50, 70 20 C90 -10, 110 60, 130 30 C150 10, 170 50, 190 25" stroke="#991b1b" stroke-width="2.5" stroke-linecap="round"/>
                            <path d="M40 45 C70 35, 110 55, 160 40" stroke="#991b1b" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="border-t border-slate-800 pt-1 text-xs font-bold text-slate-900">
                        Controller of Examinations
                    </div>
                    <div class="text-[10px] text-slate-500">{{ $settings['system_name'] ?? 'Apex Model Secondary School' }}</div>
                </div>
            </div>

        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-navy-900 text-slate-400 py-6 border-t border-navy-800 no-print">
        <div class="max-w-6xl mx-auto px-4 text-center text-xs">
            <p>&copy; {{ date('Y') }} {{ $settings['system_name'] ?? 'Apex Model Secondary School' }}. All Rights Reserved. Result & Examination Subsystem.</p>
        </div>
    </footer>

    <!-- JS Logic -->
    <script>
        const searchForm = document.getElementById('searchForm');
        const searchBtn = document.getElementById('searchBtn');
        const errorMsg = document.getElementById('errorMsg');
        const errorText = document.getElementById('errorText');
        const marksheetContainer = document.getElementById('marksheetContainer');

        if (searchForm) {
            searchForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorMsg.classList.add('hidden');
                marksheetContainer.classList.add('hidden');

            const payload = {
                batch_year: document.getElementById('batch_year') ? document.getElementById('batch_year').value : '',
                exam_name: document.getElementById('exam_name').value,
                class_name: document.getElementById('class_name').value,
                symbol_no: document.getElementById('symbol_no').value,
                verification_query: document.getElementById('verification_query').value
            };

            searchBtn.disabled = true;
            searchBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Searching Database...
            `;

            try {
                const res = await fetch('{{ route("admissions.results.search") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await res.json();

                if (!result.success && !result.data) {
                    errorText.textContent = result.message || 'Verification failed. No matching candidate found.';
                    errorMsg.classList.remove('hidden');
                } else {
                    const data = result.data || result;
                    renderMarksheet(data);
                }
            } catch (err) {
                errorText.textContent = 'Server connection error. Please try again.';
                errorMsg.classList.remove('hidden');
            } finally {
                searchBtn.disabled = false;
                searchBtn.innerHTML = `
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    Search & Display Marksheet
                `;
            }
        });
    }

        function renderMarksheet(data) {
            const batchTag = data.batch_year ? ` (${data.batch_year})` : '';
            document.getElementById('ms_exam_title').textContent = (data.exam_name || 'OFFICIAL EXAMINATION').toUpperCase() + batchTag + ' / GRADE-SHEET';
            document.getElementById('ms_student_name').textContent = data.student_name || '';
            document.getElementById('ms_class').textContent = data.class_name || '';
            document.getElementById('ms_symbol').textContent = data.symbol_no || '';
            document.getElementById('ms_roll').textContent = data.roll_no || '';
            document.getElementById('ms_section').textContent = data.section_name || 'N/A';
            document.getElementById('ms_attendance').textContent = data.attendance || 'N/A';
            if (document.getElementById('ms_batch')) {
                document.getElementById('ms_batch').textContent = data.batch_year || 'N/A';
            }
            document.getElementById('ms_gpa_val').textContent = parseFloat(data.gpa).toFixed(2);
            document.getElementById('ms_remarks').textContent = data.remarks || 'PROMOTED';
            
            const today = new Date();
            document.getElementById('ms_issue_date').textContent = data.created_at ? data.created_at.split(' ')[0] : today.toISOString().split('T')[0];

            const tbody = document.getElementById('ms_table_body');
            tbody.innerHTML = '';

            let totalFM = 0;
            if (Array.isArray(data.marks) && data.marks.length > 0) {
                data.marks.forEach((item, idx) => {
                    totalFM += parseFloat(item.full_marks || 0);
                    const tr = document.createElement('tr');
                    tr.className = idx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
                    tr.innerHTML = `
                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">${item.sn || (idx + 1)}</td>
                        <td class="border border-slate-300 px-4 py-2 font-bold text-slate-800">${item.subject}</td>
                        <td class="border border-slate-300 px-3 py-2 text-center text-slate-700">${item.full_marks}</td>
                        <td class="border border-slate-300 px-3 py-2 text-center font-extrabold ${getGradeColor(item.grade)}">${item.grade}</td>
                        <td class="border border-slate-300 px-3 py-2 text-center font-mono font-bold text-slate-900">${parseFloat(item.gp).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">No detailed subjects found.</td></tr>`;
            }

            marksheetContainer.classList.remove('hidden');
            marksheetContainer.scrollIntoView({ behavior: 'smooth' });
        }

        function getGradeColor(grade) {
            if (!grade) return 'text-slate-800';
            const g = grade.toUpperCase();
            if (g === 'A+' || g === 'A') return 'text-emerald-700';
            if (g === 'B+' || g === 'B') return 'text-blue-700';
            if (g === 'C+' || g === 'C') return 'text-amber-700';
            if (g === 'D') return 'text-orange-700';
            return 'text-rose-700';
        }
    </script>
</body>
</html>
