<?php
Route::group(['middleware' => 'no_store'], function () {
    Auth::routes(['register' => false]);
});

//Route::get('/test', 'TestController@index')->name('test');
Route::get('/privacy-policy', 'HomeController@privacy_policy')->name('privacy_policy');
Route::get('/terms-of-use', 'HomeController@terms_of_use')->name('terms_of_use');
Route::get('/locale/{lang}', 'HomeController@set_locale')->name('set_locale');

/* Progressive Web App (PWA) Routes */
Route::get('/manifest.json', 'PwaController@manifest')->name('pwa.manifest');
Route::get('/offline', 'PwaController@offline')->name('pwa.offline');
Route::get('/sw.js', 'PwaController@serviceWorker')->name('pwa.sw');

/* Public Institutional School Website & Admissions Desk */
Route::get('/', 'LandingController@index')->name('landing.home');
Route::get('/about', 'LandingController@about')->name('landing.about');
Route::get('/campus-events', 'LandingController@events')->name('landing.events');
Route::get('/contact', 'LandingController@contact')->name('landing.contact');
Route::post('/contact', 'LandingController@submit_inquiry')->name('landing.submit_inquiry')->middleware('throttle:6,1');

/* New Admission & Entrance Examination Result Subsystem */
Route::get('/admissions/results', 'LandingController@entrance_results')->name('admissions.results');
Route::post('/admissions/results/search', 'LandingController@entrance_result_search')->name('admissions.results.search')->middleware('throttle:10,1');

Route::group(['middleware' => ['auth', 'no_store']], function () {
    /* PWA Push Subscription Endpoints */
    Route::post('/pwa/push/subscribe', 'PwaController@subscribe')->name('pwa.push.subscribe')->middleware('throttle:30,1');
    Route::post('/pwa/push/unsubscribe', 'PwaController@unsubscribe')->name('pwa.push.unsubscribe')->middleware('throttle:30,1');
    Route::get('/pwa/push/vapid-key', 'PwaController@vapidPublicKey')->name('pwa.push.vapid');
    Route::post('/pwa/push/test', 'PwaController@sendTestPush')->name('pwa.push.test')->middleware('throttle:6,1');

    Route::get('/home', 'HomeController@dashboard')->name('home');
    Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');

    Route::group(['prefix' => 'my_account'], function() {
        Route::get('/', 'MyAccountController@edit_profile')->name('my_account');
        Route::put('/', 'MyAccountController@update_profile')->name('my_account.update');
        Route::put('/change_password', 'MyAccountController@change_pass')->name('my_account.change_pass');
    });

    /*************** Support Team *****************/
    Route::group(['namespace' => 'SupportTeam',], function(){

        /*************** Students *****************/
        Route::group(['prefix' => 'students'], function(){
            Route::post('reset_pass/{st_id}', 'StudentRecordController@reset_pass')->name('st.reset_pass')->middleware('teamSA');
            Route::get('graduated', 'StudentRecordController@graduated')->name('students.graduated')->middleware('teamSA');
            Route::put('not_graduated/{id}', 'StudentRecordController@not_graduated')->name('st.not_graduated')->middleware('teamSA');
            Route::get('list/{class_id}', 'StudentRecordController@listByClass')->name('students.list')->middleware('teamSAT');

            /* Promotions */
            Route::post('promote_selector', 'PromotionController@selector')->name('students.promote_selector');
            Route::get('promotion/manage', 'PromotionController@manage')->name('students.promotion_manage');
            Route::delete('promotion/reset/{pid}', 'PromotionController@reset')->name('students.promotion_reset');
            Route::delete('promotion/reset_all', 'PromotionController@reset_all')->name('students.promotion_reset_all');
            Route::get('promotion/{fc?}/{fs?}/{tc?}/{ts?}', 'PromotionController@promotion')->name('students.promotion');
            Route::post('promote/{fc}/{fs}/{tc}/{ts}', 'PromotionController@promote')->name('students.promote');

        });

        /*************** Users *****************/
        Route::group(['prefix' => 'users'], function(){
            Route::post('reset_pass/{id}', 'UserController@reset_pass')->name('users.reset_pass')->middleware('super_admin');
        });

        /*************** TimeTables *****************/
        Route::group(['prefix' => 'timetables'], function(){
            Route::get('/', 'TimeTableController@index')->name('tt.index');
            Route::get('my_timetable', 'TimeTableController@my_timetable')->name('tt.my_timetable');
            Route::get('student/{student_id}', 'TimeTableController@student_timetable')->name('tt.student_timetable');

            Route::group(['middleware' => 'teamSAT'], function() {
                Route::post('/', 'TimeTableController@store')->name('tt.store');
                Route::put('/{tt}', 'TimeTableController@update')->name('tt.update');
            });
            Route::delete('/{tt}', 'TimeTableController@delete')->name('tt.delete')->middleware('super_admin');

            /*************** TimeTable Records *****************/
            Route::group(['prefix' => 'records'], function(){

                Route::group(['middleware' => 'teamSAT'], function(){
                    Route::get('manage/{ttr}', 'TimeTableController@manage')->name('ttr.manage');
                });
                Route::group(['middleware' => 'teamSA'], function(){
                    Route::post('/', 'TimeTableController@store_record')->name('ttr.store');
                    Route::get('edit/{ttr}', 'TimeTableController@edit_record')->name('ttr.edit');
                    Route::put('/{ttr}', 'TimeTableController@update_record')->name('ttr.update');
                });

                Route::get('show/{ttr}', 'TimeTableController@show_record')->name('ttr.show');
                Route::get('print/{ttr}', 'TimeTableController@print_record')->name('ttr.print');
                Route::delete('/{ttr}', 'TimeTableController@delete_record')->name('ttr.destroy')->middleware('super_admin');

            });

            /*************** Time Slots *****************/
            Route::group(['prefix' => 'time_slots', 'middleware' => 'teamSA'], function(){
                Route::post('/', 'TimeTableController@store_time_slot')->name('ts.store');
                Route::post('/use/{ttr}', 'TimeTableController@use_time_slot')->name('ts.use');
                Route::get('edit/{ts}', 'TimeTableController@edit_time_slot')->name('ts.edit');
                Route::delete('/{ts}', 'TimeTableController@delete_time_slot')->name('ts.destroy');
                Route::put('/{ts}', 'TimeTableController@update_time_slot')->name('ts.update');
            });

        });

        /*************** Payments *****************/
        Route::group(['prefix' => 'payments'], function(){

            Route::get('monthly_billing', 'PaymentController@monthly_billing')->name('payments.monthly_billing');
            Route::post('generate_monthly_invoices', 'PaymentController@generate_monthly_invoices')->name('payments.generate_monthly_invoices');
            Route::get('reports', 'PaymentController@reports')->name('payments.reports');
            Route::get('manage/{class_id?}', 'PaymentController@manage')->name('payments.manage');
            Route::get('invoice/{id}/{year?}', 'PaymentController@invoice')->name('payments.invoice')->where('year', '.*');
            Route::get('receipts/{id}', 'PaymentController@receipts')->name('payments.receipts');
            Route::get('pdf_receipts/{id}', 'PaymentController@pdf_receipts')->name('payments.pdf_receipts');
            Route::post('select_year', 'PaymentController@select_year')->name('payments.select_year');
            Route::post('select_class', 'PaymentController@select_class')->name('payments.select_class');
            Route::delete('reset_record/{id}', 'PaymentController@reset_record')->name('payments.reset_record');
            Route::post('pay_now/{id}', 'PaymentController@pay_now')->name('payments.pay_now');
        });

        /*************** Pins *****************/
        Route::group(['prefix' => 'pins'], function(){
            Route::get('create', 'PinController@create')->name('pins.create');
            Route::get('/', 'PinController@index')->name('pins.index');
            Route::post('/', 'PinController@store')->name('pins.store');
            Route::get('enter/{id}', 'PinController@enter_pin')->name('pins.enter');
            Route::post('verify/{id}', 'PinController@verify')->name('pins.verify');
            Route::delete('/', 'PinController@destroy')->name('pins.destroy');
        });

        /*************** Marks *****************/
        Route::group(['prefix' => 'marks'], function(){

           // FOR teamSA
            Route::group(['middleware' => 'teamSA'], function(){
                Route::get('batch_fix', 'MarkController@batch_fix')->name('marks.batch_fix');
                Route::put('batch_update', 'MarkController@batch_update')->name('marks.batch_update');
                Route::get('tabulation/{exam?}/{class?}/{sec_id?}', 'MarkController@tabulation')->name('marks.tabulation');
                Route::post('tabulation', 'MarkController@tabulation_select')->name('marks.tabulation_select');
                Route::get('tabulation/print/{exam}/{class}/{sec_id}', 'MarkController@print_tabulation')->name('marks.print_tabulation');
            });

            // FOR teamSAT
            Route::group(['middleware' => 'teamSAT'], function(){
                Route::get('/', 'MarkController@index')->name('marks.index');
                Route::get('manage/{exam}/{class}/{section}/{subject}', 'MarkController@manage')->name('marks.manage');
                Route::put('update/{exam}/{class}/{section}/{subject}', 'MarkController@update')->name('marks.update');
                Route::put('comment_update/{exr_id}', 'MarkController@comment_update')->name('marks.comment_update');
                Route::put('skills_update/{skill}/{exr_id}', 'MarkController@skills_update')->name('marks.skills_update');
                Route::post('selector', 'MarkController@selector')->name('marks.selector');
                Route::get('bulk/{class?}/{section?}', 'MarkController@bulk')->name('marks.bulk');
                Route::post('bulk', 'MarkController@bulk_select')->name('marks.bulk_select');
            });

            Route::get('select_year/{id}', 'MarkController@year_selector')->name('marks.year_selector');
            Route::post('select_year/{id}', 'MarkController@year_selected')->name('marks.year_select');
            Route::get('show/{id}/{year}', 'MarkController@show')->name('marks.show')->where('year', '.*');
            Route::get('print/{id}/{exam_id}/{year}', 'MarkController@print_view')->name('marks.print')->where('year', '.*');
            Route::get('pdf/{id}/{exam_id}/{year}', 'MarkController@pdf_download')->name('marks.pdf')->where('year', '.*');

        });

        Route::resource('students', 'StudentRecordController');
        Route::resource('users', 'UserController');
        Route::resource('classes', 'MyClassController', ['except' => ['create', 'show']]);
        Route::resource('sections', 'SectionController', ['except' => ['create', 'show']]);
        Route::resource('subjects', 'SubjectController', ['except' => ['create', 'show']]);
        Route::resource('grades', 'GradeController', ['except' => ['create', 'show']]);
        Route::resource('exams', 'ExamController', ['except' => ['create', 'show']]);
        Route::put('exams/{exam}/publish', 'ExamController@publish')->name('exams.publish');
        Route::put('exams/{exam}/unpublish', 'ExamController@unpublish')->name('exams.unpublish');
        Route::resource('dorms', 'DormController', ['except' => ['create', 'show']]);
        Route::resource('payments', 'PaymentController', ['except' => ['show']]);
        Route::get('payments/{year}', 'PaymentController@show')->name('payments.show')->where('year', '.*');

        /*************** Notices & Study Materials *****************/
        Route::resource('notices', 'NoticeController');
        Route::resource('study_materials', 'StudyMaterialController', ['except' => ['show', 'edit', 'update', 'create']]);
        Route::get('study_materials/download/{id}', 'StudyMaterialController@download')->name('study_materials.download');

        /*************** Academic Years & Holidays (Nepal BS) *****************/
        Route::resource('academic_years', 'AcademicYearController', ['except' => ['create', 'show']]);
        Route::put('academic_years/activate/{academic_year}', 'AcademicYearController@activate')->name('academic_years.activate');
        Route::put('academic_years/close/{academic_year}', 'AcademicYearController@close')->name('academic_years.close');
        Route::put('academic_years/reopen/{academic_year}', 'AcademicYearController@reopen')->name('academic_years.reopen');

        /*************** Events & Calendar (Nepal BS) *****************/
        Route::resource('events', 'EventController');
        Route::post('events/{id}/publish', 'EventController@publish')->name('events.publish');
        Route::post('events/{id}/unpublish', 'EventController@unpublish')->name('events.unpublish');
        Route::get('calendar/events', 'EventController@calendarEvents')->name('calendar.events');

        Route::resource('holidays', 'HolidayController', ['except' => ['create', 'show', 'edit', 'update']]);

        /*************** Nepal Attendance Management (BS) *****************/
        Route::group(['middleware' => 'teamSAT'], function(){
            Route::get('attendance', 'AttendanceController@index')->name('attendance.index');
            Route::post('attendance', 'AttendanceController@store')->name('attendance.store');
            Route::post('attendance/sync', 'AttendanceController@sync')->name('attendance.sync');
            Route::get('attendance/monthly', 'AttendanceController@monthly')->name('attendance.monthly');
            Route::get('attendance/class-report', 'AttendanceController@classReport')->name('attendance.class_report');
            Route::get('attendance/sections/{class_id}', 'AttendanceController@getSections')->name('attendance.get_sections');
        });
        Route::get('attendance/student/{student_id}', 'AttendanceController@studentHistory')->name('attendance.student_history');

        Route::group(['middleware' => 'teamSA'], function(){
            Route::get('staff-attendance', 'StaffAttendanceController@index')->name('staff_attendance.index');
            Route::post('staff-attendance', 'StaffAttendanceController@store')->name('staff_attendance.store');
            Route::get('staff-attendance/monthly', 'StaffAttendanceController@monthly')->name('staff_attendance.monthly');
        });

        /*************** Library & Books Management *****************/
        Route::get('books/catalogue', 'BookController@catalogue')->name('books.catalogue');
        Route::get('books/categories', 'BookController@categories')->name('books.categories');
        Route::post('books/categories', 'BookController@storeCategory')->name('books.categories.store');
        Route::put('books/categories/{id}', 'BookController@updateCategory')->name('books.categories.update');
        Route::delete('books/categories/{id}', 'BookController@destroyCategory')->name('books.categories.destroy');

        Route::get('books/circulations', 'BookController@circulations')->name('books.circulations');
        Route::post('books/issue', 'BookController@issue')->name('books.issue');
        Route::post('books/circulations/issue', 'BookController@issue')->name('books.issue_book');
        Route::match(['post', 'put'], 'books/return/{id}', 'BookController@returnBook')->name('books.return');
        Route::match(['post', 'put'], 'books/circulations/{id}/return', 'BookController@returnBook')->name('books.return_book');

        Route::resource('books', 'BookController');

        Route::get('book_requests/my', 'BookRequestController@myRequests')->name('book_requests.my_requests');
        Route::get('book_requests', 'BookRequestController@index')->name('book_requests.index');
        Route::post('book_requests', 'BookRequestController@store')->name('book_requests.store');
        Route::match(['post', 'put'], 'book_requests/{id}/approve', 'BookRequestController@approve')->name('book_requests.approve');
        Route::match(['post', 'put'], 'book_requests/{id}/reject', 'BookRequestController@reject')->name('book_requests.reject');
        Route::match(['post', 'put'], 'book_requests/{id}/cancel', 'BookRequestController@cancel')->name('book_requests.cancel');
        Route::match(['post', 'put'], 'book_requests/{id}/issue', 'BookRequestController@issueFromRequest')->name('book_requests.issue_from_request');
        Route::match(['post', 'put'], 'book_requests/{id}/issue_from_request', 'BookRequestController@issueFromRequest');
        Route::match(['post', 'put'], 'book_requests/{id}/issue_request', 'BookRequestController@issueFromRequest')->name('book_requests.issue');

    });

    /************************ AJAX ****************************/
    Route::group(['prefix' => 'ajax'], function() {
        Route::get('get_lga/{state_id}', 'AjaxController@get_lga')->name('get_lga');
        Route::get('get_class_sections/{class_id}', 'AjaxController@get_class_sections')->name('get_class_sections');
        Route::get('get_class_subjects/{class_id}', 'AjaxController@get_class_subjects')->name('get_class_subjects');
    });

});

/************************ SUPER ADMIN ****************************/
Route::group(['namespace' => 'SuperAdmin','middleware' => 'super_admin', 'prefix' => 'super_admin'], function(){

    Route::get('/settings', 'SettingController@index')->name('settings');
    Route::put('/settings', 'SettingController@update')->name('settings.update');

    // Activity & Audit Logs Dashboard, Live Feed & Export
    Route::group(['prefix' => 'audit_logs'], function(){
        Route::get('/', 'AuditLogController@index')->name('audit_logs.index');
        Route::get('/feed', 'AuditLogController@feed')->name('audit_logs.feed');
        Route::get('/export', 'AuditLogController@export')->name('audit_logs.export');
        Route::get('/{id}', 'AuditLogController@show')->name('audit_logs.show');
    });

});

/************************ LANDING PAGE & ADMISSIONS (SUPER ADMIN) ****************************/
Route::group(['namespace' => 'SupportTeam', 'middleware' => ['auth', 'super_admin'], 'prefix' => 'landing-settings'], function(){
    Route::get('/', 'LandingSettingsController@index')->name('landing_settings.index');
    Route::match(['post', 'put'], '/update', 'LandingSettingsController@update')->name('landing_settings.update');
    Route::get('/inquiries', 'LandingSettingsController@inquiries')->name('landing_settings.inquiries');
    Route::post('/inquiries/{id}/status', 'LandingSettingsController@update_inquiry_status')->name('landing_settings.inquiries.status');
    Route::delete('/inquiries/{id}', 'LandingSettingsController@destroy_inquiry')->name('landing_settings.inquiries.delete');
    Route::get('/entrance-results', 'LandingSettingsController@manage_entrance_results')->name('landing_settings.entrance_results');
    Route::post('/entrance-results/upload', 'LandingSettingsController@upload_entrance_results')->name('landing_settings.entrance_results.upload');
    Route::delete('/entrance-results/{id}', 'LandingSettingsController@destroy_entrance_result')->name('landing_settings.entrance_results.delete');
});

/************************ PARENT ****************************/
Route::group(['namespace' => 'MyParent','middleware' => 'my_parent',], function(){

    Route::get('/my_children', 'MyController@children')->name('my_children');

});
