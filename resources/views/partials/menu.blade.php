<div class="sidebar sidebar-dark sidebar-main sidebar-expand-md sms-sidebar">

    <!-- Sidebar content -->
    <div class="sidebar-content">

        <!-- User menu -->
        <div class="sidebar-user sms-sidebar-user">
            <div class="media align-items-center">
                <div class="mr-3 position-relative">
                    <a href="{{ route('my_account') }}" aria-label="{{ __('Account Settings') }}">
                        <img src="{{ Auth::user()->photo }}" width="40" height="40" class="rounded-circle" style="object-fit: cover; border: 1.5px solid rgba(255, 255, 255, 0.25);" alt="{{ Auth::user()->name }}">
                    </a>
                </div>

                <div class="media-body">
                    <div class="media-title font-weight-semibold text-white mb-0" style="font-size: 0.88rem;">
                        {{ Auth::user()->name }}
                    </div>
                    <div class="mt-1">
                        <span class="sms-role-badge" style="font-size: 0.7rem; padding: 2px 8px;">
                            {{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}
                        </span>
                    </div>
                </div>

                <div class="ml-2">
                    <a href="{{ route('my_account') }}" class="text-white-50" title="{{ __('Account Settings') }}" aria-label="{{ __('Account Settings') }}">
                        <i class="icon-cog3" style="font-size: 1.05rem;" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- /user menu -->

        <!-- Main navigation -->
        <div class="sidebar-nav-container card-sidebar-mobile">
            <ul class="nav nav-sidebar" data-nav-type="accordion">

                <!-- Main -->
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ (Route::is('dashboard')) ? 'active' : '' }}">
                        <i class="icon-home4"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>

                {{--Academics--}}
                @if(Qs::userIsAcademic())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['tt.index', 'ttr.edit', 'ttr.show', 'ttr.manage']) ? 'nav-item-expanded nav-item-open' : '' }} ">
                        <a href="#" class="nav-link"><i class="icon-graduation2"></i> <span> {{ __('Academics') }}</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('Manage Academics') }}">

                        {{--Timetables--}}
                            <li class="nav-item"><a href="{{ route('tt.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['tt.index']) ? 'active' : '' }}">{{ __('Timetables') }}</a></li>
                            <li class="nav-item"><a href="{{ route('homework.index') }}" class="nav-link {{ Route::is('homework.*') ? 'active' : '' }}">{{ __('Homework & Daily Diary') }}</a></li>
                            @if(Qs::userIsTeamSAT())
                                <li class="nav-item"><a href="{{ route('lesson_plans.index') }}" class="nav-link {{ Route::is('lesson_plans.*') ? 'active' : '' }}">{{ __('Lesson Plans & Diary') }}</a></li>
                            @endif
                            <li class="nav-item"><a href="{{ route('elearning.classes') }}" class="nav-link {{ Route::is('elearning.classes*') ? 'active' : '' }}">{{ __('Online Classes') }}</a></li>
                            <li class="nav-item"><a href="{{ route('elearning.quizzes') }}" class="nav-link {{ Route::is('elearning.quizzes*') ? 'active' : '' }}">{{ __('Online Quizzes') }}</a></li>
                            <li class="nav-item"><a href="{{ route('elearning.forum') }}" class="nav-link {{ Route::is('elearning.forum*') ? 'active' : '' }}">{{ __('Discussion Forum') }}</a></li>
                        </ul>
                    </li>
                    @endif

                {{--Administrative--}}
                @if(Qs::userIsAdministrative())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.create', 'payments.invoice', 'payments.receipts', 'payments.edit', 'payments.manage', 'payments.show', 'payments.monthly_billing', 'payments.reports']) ? 'nav-item-expanded nav-item-open' : '' }} ">
                        <a href="#" class="nav-link"><i class="icon-office"></i> <span> {{ __('Administrative') }}</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('Administrative') }}">

                            {{--Payments--}}
                            @if(Qs::userIsTeamAccount())
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.create', 'payments.edit', 'payments.manage', 'payments.show', 'payments.invoice', 'payments.monthly_billing', 'payments.reports']) ? 'nav-item-expanded' : '' }}">

                                <a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.edit', 'payments.create', 'payments.manage', 'payments.show', 'payments.invoice', 'payments.monthly_billing', 'payments.reports']) ? 'active' : '' }}">{{ __('Payments') }}</a>

                                <ul class="nav nav-group-sub">
                                    <li class="nav-item"><a href="{{ route('payments.create') }}" class="nav-link {{ Route::is('payments.create') ? 'active' : '' }}">{{ __('Create Payment') }}</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.edit', 'payments.show']) ? 'active' : '' }}">{{ __('Manage Payments') }}</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.monthly_billing') }}" class="nav-link {{ Route::is('payments.monthly_billing') ? 'active' : '' }}">{{ __('Monthly Billing (BS)') }}</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.reports') }}" class="nav-link {{ Route::is('payments.reports') ? 'active' : '' }}">{{ __('Fee Reports (BS)') }}</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.manage') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.manage', 'payments.invoice', 'payments.receipts']) ? 'active' : '' }}">{{ __('Student Payments') }}</a></li>

                                </ul>

                            </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{--Manage Students--}}
                @if(Qs::userIsTeamSAT())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.create', 'students.list', 'students.edit', 'students.show', 'students.promotion', 'students.promotion_manage', 'students.graduated']) ? 'nav-item-expanded nav-item-open' : '' }} ">
                        <a href="#" class="nav-link"><i class="icon-users"></i> <span> {{ __('Students') }}</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('Manage Students') }}">
                            {{--Admit Student--}}
                            @if(Qs::userIsTeamSA())
                                <li class="nav-item">
                                    <a href="{{ route('students.create') }}"
                                       class="nav-link {{ (Route::is('students.create')) ? 'active' : '' }}">{{ __('Admit Student') }}</a>
                                </li>
                            @endif

                            {{--Student Information--}}
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.list', 'students.edit', 'students.show']) ? 'nav-item-expanded' : '' }}">
                                <a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['students.list', 'students.edit', 'students.show']) ? 'active' : '' }}">{{ __('Student Information') }}</a>
                                <ul class="nav nav-group-sub">
                                    @foreach(($shellClasses ?? collect()) as $c)
                                        <li class="nav-item"><a href="{{ route('students.list', $c->id) }}" class="nav-link {{ (Route::is('students.list') && (string)request()->route('class_id') === (string)$c->id) ? 'active' : '' }}">{{ $c->name }}</a></li>
                                    @endforeach
                                </ul>
                            </li>

                            @if(Qs::userIsTeamSA())

                            {{--Student Promotion--}}
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.promotion', 'students.promotion_manage']) ? 'nav-item-expanded' : '' }}"><a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion', 'students.promotion_manage' ]) ? 'active' : '' }}">{{ __('Student Promotion') }}</a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('students.promotion') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion']) ? 'active' : '' }}">{{ __('Promote Students') }}</a></li>
                                <li class="nav-item"><a href="{{ route('students.promotion_manage') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion_manage']) ? 'active' : '' }}">{{ __('Manage Promotions') }}</a></li>
                            </ul>

                            </li>

                            {{--Student Graduated--}}
                            <li class="nav-item"><a href="{{ route('students.graduated') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.graduated' ]) ? 'active' : '' }}">{{ __('Students Graduated') }}</a></li>
                                @endif

                        </ul>
                    </li>
                @endif

                @if(Qs::userIsTeamSA())
                    {{--Manage Users--}}
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['users.index', 'users.show', 'users.edit']) ? 'active' : '' }}"><i class="icon-users4"></i> <span> {{ __('Users') }}</span></a>
                    </li>

                    {{--Manage Parents--}}
                    <li class="nav-item">
                        <a href="{{ route('parents.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['parents.index', 'parents.show']) ? 'active' : '' }}"><i class="icon-user-tie"></i> <span> {{ __('Parents') }}</span></a>
                    </li>

                    {{--Manage Classes--}}
                    <li class="nav-item">
                        <a href="{{ route('classes.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['classes.index','classes.edit']) ? 'active' : '' }}"><i class="icon-windows2"></i> <span> {{ __('Classes') }}</span></a>
                    </li>

                    {{--Manage Dorms--}}
                    <li class="nav-item">
                        <a href="{{ route('dorms.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['dorms.index','dorms.edit']) ? 'active' : '' }}"><i class="icon-home9"></i> <span> {{ __('Dormitories') }}</span></a>
                    </li>

                    {{--Manage Sections--}}
                    <li class="nav-item">
                        <a href="{{ route('sections.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['sections.index','sections.edit',]) ? 'active' : '' }}"><i class="icon-fence"></i> <span>{{ __('Sections') }}</span></a>
                    </li>

                    {{--Manage Subjects--}}
                    <li class="nav-item">
                        <a href="{{ route('subjects.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['subjects.index','subjects.edit',]) ? 'active' : '' }}"><i class="icon-pin"></i> <span>{{ __('Subjects') }}</span></a>
                    </li>
                @endif

                {{--Exam--}}
                @if(Qs::userIsTeamSAT())
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['exams.index', 'exams.edit', 'grades.index', 'grades.edit', 'marks.index', 'marks.manage', 'marks.bulk', 'marks.tabulation', 'marks.show', 'marks.batch_fix',]) ? 'nav-item-expanded nav-item-open' : '' }} ">
                    <a href="#" class="nav-link"><i class="icon-books"></i> <span> {{ __('Exams') }}</span></a>

                    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Manage Exams') }}">
                        @if(Qs::userIsTeamSA())

                        {{--Exam list--}}
                            <li class="nav-item">
                                <a href="{{ route('exams.index') }}"
                                   class="nav-link {{ (Route::is('exams.index')) ? 'active' : '' }}">{{ __('Exam List') }}</a>
                            </li>

                            {{--Grades list--}}
                            <li class="nav-item">
                                    <a href="{{ route('grades.index') }}"
                                       class="nav-link {{ in_array(Route::currentRouteName(), ['grades.index', 'grades.edit']) ? 'active' : '' }}">{{ __('Grades') }}</a>
                            </li>

                            {{--Tabulation Sheet--}}
                            <li class="nav-item">
                                <a href="{{ route('marks.tabulation') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.tabulation']) ? 'active' : '' }}">{{ __('Tabulation Sheet') }}</a>
                            </li>

                            {{--Marks Batch Fix--}}
                            <li class="nav-item">
                                <a href="{{ route('marks.batch_fix') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.batch_fix']) ? 'active' : '' }}">{{ __('Batch Fix') }}</a>
                            </li>
                        @endif

                        @if(Qs::userIsTeamSAT())
                            {{--Marks Manage--}}
                            <li class="nav-item">
                                <a href="{{ route('marks.index') }}"
                                   class="nav-link {{ in_array(Route::currentRouteName(), ['marks.index']) ? 'active' : '' }}">{{ __('Marks') }}</a>
                            </li>

                            {{--Marksheet--}}
                            <li class="nav-item">
                                <a href="{{ route('marks.bulk') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.bulk', 'marks.show']) ? 'active' : '' }}">{{ __('Marksheet') }}</a>
                            </li>

                            @endif

                    </ul>
                </li>
                @endif


                {{--End Exam--}}

                @if(Qs::userIsTeamSA())
                    {{--Academic Years (Nepal BS)--}}
                    <li class="nav-item">
                        <a href="{{ route('academic_years.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['academic_years.index', 'academic_years.edit']) ? 'active' : '' }}"><i class="icon-calendar52"></i> <span>{{ __('Academic Years (BS)') }}</span></a>
                    </li>

                    {{--Events & Calendar (Nepal BS)--}}
                    <li class="nav-item">
                        <a href="{{ route('events.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['events.index', 'events.create', 'events.edit', 'events.show']) ? 'active' : '' }}"><i class="icon-calendar3"></i> <span>{{ __('Events & Calendar (BS)') }}</span></a>
                    </li>

                    {{--Holiday Calendar (Nepal BS)--}}
                    <li class="nav-item">
                        <a href="{{ route('holidays.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['holidays.index', 'holidays.edit']) ? 'active' : '' }}"><i class="icon-calendar"></i> <span>{{ __('Holiday Calendar') }}</span></a>
                    </li>

                    {{--Nepal Administrative Locations--}}
                    <li class="nav-item">
                        <a href="{{ route('nepal_locations.manage') }}" class="nav-link {{ Route::is('nepal_locations.manage') ? 'active' : '' }}"><i class="icon-location4"></i> <span>{{ __('Nepal Locations') }}</span></a>
                    </li>

                    {{--Library Management--}}
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['books.index', 'books.edit', 'books.show', 'books.circulations', 'books.categories', 'book_requests.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-books"></i> <span>{{ __('Library') }}</span></a>
                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('Library') }}">
                            <li class="nav-item">
                                <a href="{{ route('books.index') }}" class="nav-link {{ Route::is('books.index') ? 'active' : '' }}">{{ __('Books & Inventory') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('books.circulations') }}" class="nav-link {{ Route::is('books.circulations') ? 'active' : '' }}">{{ __('Issue & Return') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('book_requests.index') }}" class="nav-link {{ Route::is('book_requests.index') ? 'active' : '' }}">{{ __('Book Requests') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('books.categories') }}" class="nav-link {{ Route::is('books.categories') ? 'active' : '' }}">{{ __('Book Categories') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{--Attendance Management (Nepal BS)--}}
                @if(Qs::userIsTeamSAT())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['attendance.index', 'attendance.monthly', 'attendance.class_report', 'staff_attendance.index', 'staff_attendance.monthly']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-user-check"></i> <span>{{ __('Attendance') }}</span></a>
                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('Attendance') }}">
                            <li class="nav-item">
                                <a href="{{ route('attendance.index') }}" class="nav-link {{ Route::is('attendance.index') ? 'active' : '' }}">{{ __('Daily Student Attendance') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('attendance.monthly') }}" class="nav-link {{ Route::is('attendance.monthly') ? 'active' : '' }}">{{ __('Monthly Attendance Sheet') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('attendance.class_report') }}" class="nav-link {{ Route::is('attendance.class_report') ? 'active' : '' }}">{{ __('Class Comparison Report') }}</a>
                            </li>
                            @if(Qs::userIsTeamSA())
                                <li class="nav-item">
                                    <a href="{{ route('staff_attendance.index') }}" class="nav-link {{ Route::is('staff_attendance.index') ? 'active' : '' }}">{{ __('Daily Staff Attendance') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('staff_attendance.monthly') }}" class="nav-link {{ Route::is('staff_attendance.monthly') ? 'active' : '' }}">{{ __('Staff Monthly Summary') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if(!Qs::userIsTeamSA())
                    {{--School Calendar (Nepal BS)--}}
                    <li class="nav-item">
                        <a href="{{ route('events.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['events.index', 'events.show']) ? 'active' : '' }}">
                            <i class="icon-calendar3"></i> <span>{{ __('School Calendar') }}</span>
                        </a>
                    </li>
                @endif

                {{--Noticeboard--}}
                <li class="nav-item">
                    <a href="{{ route('notices.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['notices.index', 'notices.show', 'notices.create', 'notices.edit']) ? 'active' : '' }}">
                        <i class="icon-newspaper"></i> <span>{{ __('Noticeboard') }}</span>
                    </a>
                </li>

                {{--Study Materials--}}
                <li class="nav-item">
                    <a href="{{ route('study_materials.index') }}" class="nav-link {{ Route::is('study_materials.index') ? 'active' : '' }}">
                        <i class="icon-file-text2"></i> <span>{{ __('Study Materials') }}</span>
                    </a>
                </li>

                {{--Student Care & Leaves--}}
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['leaves.index', 'wellness.index', 'certificates.index', 'documents.id_cards', 'documents.admit_cards']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="icon-heart5"></i> <span>{{ __('Care & Documents') }}</span></a>
                    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Care & Documents') }}">
                        <li class="nav-item"><a href="{{ route('leaves.index') }}" class="nav-link {{ Route::is('leaves.*') ? 'active' : '' }}">{{ __('Leave Applications') }}</a></li>
                        <li class="nav-item"><a href="{{ route('wellness.index') }}" class="nav-link {{ Route::is('wellness.*') ? 'active' : '' }}">{{ __('Wellness, ECA & Merit') }}</a></li>
                        <li class="nav-item"><a href="{{ route('certificates.index') }}" class="nav-link {{ Route::is('certificates.*') ? 'active' : '' }}">{{ __('Certificates & Vault') }}</a></li>
                        @if(Qs::userIsTeamSA())
                            <li class="nav-item"><a href="{{ route('documents.id_cards') }}" class="nav-link {{ Route::is('documents.id_cards') ? 'active' : '' }}">{{ __('Smart ID Cards') }}</a></li>
                            <li class="nav-item"><a href="{{ route('documents.admit_cards') }}" class="nav-link {{ Route::is('documents.admit_cards') ? 'active' : '' }}">{{ __('Exam Admit Cards') }}</a></li>
                        @endif
                    </ul>
                </li>

                @if(Qs::userIsTeamSA())
                    {{--Finance, Inventory & Nepal EMIS--}}
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['accounting.index', 'payroll.index', 'inventory.index', 'nepal_emis.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-coins"></i> <span>{{ __('ERP Finance & EMIS') }}</span></a>
                        <ul class="nav nav-group-sub" data-submenu-title="{{ __('ERP Finance & EMIS') }}">
                            <li class="nav-item"><a href="{{ route('accounting.index') }}" class="nav-link {{ Route::is('accounting.*') ? 'active' : '' }}">{{ __('Accounting & Day Book') }}</a></li>
                            <li class="nav-item"><a href="{{ route('payroll.index') }}" class="nav-link {{ Route::is('payroll.*') ? 'active' : '' }}">{{ __('HR & Payroll') }}</a></li>
                            <li class="nav-item"><a href="{{ route('inventory.index') }}" class="nav-link {{ Route::is('inventory.*') ? 'active' : '' }}">{{ __('Inventory & Stock') }}</a></li>
                            <li class="nav-item"><a href="{{ route('nepal_emis.index') }}" class="nav-link {{ Route::is('nepal_emis.*') ? 'active' : '' }}">{{ __('IEMIS, Curriculum & SEE') }}</a></li>
                        </ul>
                    </li>
                @endif

                {{--Campus Operations--}}
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['transport.index', 'hostel_canteen.index', 'visitors.index', 'feedback.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="icon-bus"></i> <span>{{ __('Campus Operations') }}</span></a>
                    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Campus Operations') }}">
                        <li class="nav-item"><a href="{{ route('transport.index') }}" class="nav-link {{ Route::is('transport.*') ? 'active' : '' }}">{{ __('Transport & Bus') }}</a></li>
                        <li class="nav-item"><a href="{{ route('hostel_canteen.index') }}" class="nav-link {{ Route::is('hostel_canteen.*') ? 'active' : '' }}">{{ __('Hostel & Lunch') }}</a></li>
                        @if(Qs::userIsStaff())
                            <li class="nav-item"><a href="{{ route('visitors.index') }}" class="nav-link {{ Route::is('visitors.*') ? 'active' : '' }}">{{ __('Visitor Register') }}</a></li>
                        @endif
                        <li class="nav-item"><a href="{{ route('feedback.index') }}" class="nav-link {{ Route::is('feedback.*') ? 'active' : '' }}">{{ __('Feedback & Complaints') }}</a></li>
                    </ul>
                </li>

                {{--Communication & Surveys--}}
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['messages.index', 'surveys.index', 'communications.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="icon-bubbles4"></i> <span>{{ __('Communication') }}</span></a>
                    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Communication') }}">
                        <li class="nav-item"><a href="{{ route('messages.index') }}" class="nav-link {{ Route::is('messages.*') ? 'active' : '' }}">{{ __('Direct Messages') }}</a></li>
                        <li class="nav-item"><a href="{{ route('surveys.index') }}" class="nav-link {{ Route::is('surveys.*') ? 'active' : '' }}">{{ __('Surveys & Polls') }}</a></li>
                        @if(Qs::userIsTeamSA())
                            <li class="nav-item"><a href="{{ route('communications.index') }}" class="nav-link {{ Route::is('communications.*') ? 'active' : '' }}">{{ __('Notification Hub & Birthdays') }}</a></li>
                        @endif
                    </ul>
                </li>

                @if(Qs::userIsStaff() && !Qs::userIsTeamSA())
                    <li class="nav-item">
                        <a href="{{ route('payroll.index') }}" class="nav-link {{ Route::is('payroll.*') ? 'active' : '' }}">
                            <i class="icon-cash3"></i> <span>{{ __('My Payslips') }}</span>
                        </a>
                    </li>
                @endif

                @include('pages.'.Qs::getUserType().'.menu')

                {{--Manage Account--}}
                <li class="nav-item">
                    <a href="{{ route('my_account') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['my_account']) ? 'active' : '' }}"><i class="icon-user"></i> <span>{{ __('My Account') }}</span></a>
                </li>

                </ul>
            </div>
        </div>
</div>
