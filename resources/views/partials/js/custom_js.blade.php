<script>

    function getLGA(state_id){
        var url = '{{ route('get_lga', [':id']) }}';
        url = url.replace(':id', state_id);
        var lga = $('#lga_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                //console.log(resp);
                lga.empty();
                $.each(resp, function (i, data) {
                    lga.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });

            }
        })
    }

    function getClassSections(class_id, destination){
        var url = '{{ route('get_class_sections', [':id']) }}';
        url = url.replace(':id', class_id);
        var section = destination ? $(destination) : $('#section_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                //console.log(resp);
                section.empty();
                $.each(resp, function (i, data) {
                    section.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });

            }
        })
    }

    function getClassSubjects(class_id){
        var url = '{{ route('get_class_subjects', [':id']) }}';
        url = url.replace(':id', class_id);
        var section = $('#section_id');
        var subject = $('#subject_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                console.log(resp);
                section.empty();
                subject.empty();
                $.each(resp.sections, function (i, data) {
                    section.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });
                $.each(resp.subjects, function (i, data) {
                    subject.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });

            }
        })
    }


    {{--Notifications--}}

    @if (session('pop_error'))
    pop({msg : '{{ session('pop_error') }}', type : 'error'});
    @endif

    @if (session('pop_warning'))
    pop({msg : '{{ session('pop_warning') }}', type : 'warning'});
    @endif

 @if (session('pop_success'))
    pop({msg : '{{ session('pop_success') }}', type : 'success', title: 'GREAT!!'});
    @endif

    @if (session('flash_info'))
      flash({msg : '{{ session('flash_info') }}', type : 'info'});
    @endif

    @if (session('flash_success'))
      flash({msg : '{{ session('flash_success') }}', type : 'success'});
    @endif

    @if (session('flash_warning'))
      flash({msg : '{{ session('flash_warning') }}', type : 'warning'});
    @endif

     @if (session('flash_error') || session('flash_danger'))
      flash({msg : '{{ session('flash_error') ?: session('flash_danger') }}', type : 'danger'});
    @endif

    {{--End Notifications--}}

    function pop(data){
        swal({
            title: data.title ? data.title : 'Oops...',
            text: data.msg,
            icon: data.type
        });
    }

    function flash(data){
        new PNotify({
            text: data.msg,
            type: data.type,
            hide : data.type !== "danger"
        });
    }

    function confirmDelete(id) {
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this item!",
            icon: "warning",
            buttons: true,
            dangerMode: true
        }).then(function(willDelete){
            if (willDelete) {
             $('form#item-delete-'+id).submit();
            }
        });
    }

    function confirmReset(id) {
        swal({
            title: "Are you sure?",
            text: "This will reset this item to default state",
            icon: "warning",
            buttons: true,
            dangerMode: true
        }).then(function(willDelete){
            if (willDelete) {
             $('form#item-reset-'+id).submit();
            }
        });
    }

    $('form#ajax-reg').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this), 'store');
    });

    $('form.ajax-pay').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this), 'store');

//        Retrieve IDS
        var form_id = $(this).attr('id');
        var td_amt = $('td#amt-'+form_id);
        var td_amt_paid = $('td#amt_paid-'+form_id);
        var td_bal = $('td#bal-'+form_id);
        var input = $('#val-'+form_id);

        // Get Values
        var amt = parseInt(td_amt.data('amount'));
        var amt_paid = parseInt(td_amt_paid.data('amount'));
        var amt_input = parseInt(input.val());

//        Update Values
        amt_paid = amt_paid + amt_input;
        var bal = amt - amt_paid;

        td_bal.text(''+bal);
        td_amt_paid.text(''+amt_paid).data('amount', ''+amt_paid);
        input.attr('max', bal);
        bal < 1 ? $('#'+form_id).fadeOut('slow').remove() : '';
    });

    $('form.ajax-store').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this), 'store');
        var div = $(this).data('reload');
        div ? reloadDiv(div) : '';
    });

    $('form.ajax-update').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this));
        var div = $(this).data('reload');
        div ? reloadDiv(div) : '';
    });

    $('.download-receipt').on('click', function(ev){
        ev.preventDefault();
        $.get($(this).attr('href'));
        flash({msg : '{{ 'Download in Progress' }}', type : 'info'});
    });

    function reloadDiv(div){
        var url = window.location.href;
        url = url + ' '+ div;
        $(div).load( url );
    }

    function submitForm(form, formType){
        var btn = form.find('button[type=submit]');
        disableBtn(btn);
        var ajaxOptions = {
            url:form.attr('action'),
            type:'POST',
            cache:false,
            processData:false,
            dataType:'json',
            contentType:false,
            data:new FormData(form[0])
        };
        var req = $.ajax(ajaxOptions);
        req.done(function(resp){
            resp.ok && resp.msg
               ? flash({msg:resp.msg, type:'success'})
               : flash({msg:resp.msg, type:'danger'});
            hideAjaxAlert();
            enableBtn(btn);
            formType == 'store' ? clearForm(form) : '';
            scrollTo('body');
            return resp;
        });
        req.fail(function(e){
            if (e.status == 422){
                var errors = e.responseJSON.errors;
                displayAjaxErr(errors);
            }
           if(e.status == 500){
               displayAjaxErr([e.status + ' ' + e.statusText + ' Please Check for Duplicate entry or Contact School Administrator/IT Personnel'])
           }
            if(e.status == 404){
               displayAjaxErr([e.status + ' ' + e.statusText + ' - Requested Resource or Record Not Found'])
           }
            enableBtn(btn);
            return e.status;
        });
    }

    function disableBtn(btn){
        var btnText = btn.data('text') ? btn.data('text') : 'Submitting';
        btn.prop('disabled', true).html('<i class="icon-spinner mr-2 spinner"></i>' + btnText);
    }

    function enableBtn(btn){
        var btnText = btn.data('text') ? btn.data('text') : 'Submit Form';
        btn.prop('disabled', false).html(btnText + '<i class="icon-paperplane ml-2"></i>');
    }

    function displayAjaxErr(errors){
        $('#ajax-alert').show().html(' <div class="alert alert-danger border-0 alert-dismissible" id="ajax-msg"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
        $.each(errors, function(k, v){
            $('#ajax-msg').append('<span><i class="icon-arrow-right5"></i> '+ v +'</span><br/>');
        });
        scrollTo('body');
    }

    function scrollTo(el){
        $('html, body').animate({
            scrollTop:$(el).offset().top
        }, 350);
    }

    function hideAjaxAlert(){
        $('#ajax-alert').hide();
    }

    function clearForm(form){
        form.find('.select, .select-search').val([]).select2({ placeholder: 'Select...'});
        form[0].reset();
    }

    $(document).ready(function() {
        // Native iOS Dismissal: Close mobile navigation drawer when tapping outside or touching scrim
        $(document).on('click touchstart', function(e) {
            if ($('body').hasClass('sidebar-mobile-main')) {
                if (!$(e.target).closest('.sidebar-main, .sidebar-mobile-main-toggle, .sms-mobile-dock, .apple-mobile-dock').length) {
                    $('body').removeClass('sidebar-mobile-main');
                }
            }
        });

        // Uiverse-inspired Tab Select Label & State Sync
        $(document).on('click', '.nav-tabs .dropdown-menu .dropdown-item', function() {
            var selectedText = $(this).text().trim();
            var $dropdown = $(this).closest('.nav-item.dropdown');
            var $toggle = $dropdown.find('.dropdown-toggle');
            if (!$toggle.data('default-text')) {
                var def = $toggle.attr('data-default') || $toggle.text().trim();
                $toggle.data('default-text', def);
            }
            var $textSpan = $toggle.find('.selected-text');
            if ($textSpan.length) {
                $textSpan.text(selectedText);
            } else {
                $toggle.html('<span class="selected-text">' + selectedText + '</span>');
            }
            $toggle.addClass('active');
            $dropdown.find('.dropdown-item').removeClass('active');
            $(this).addClass('active');

            // On mobile, close dropdown smoothly after selection
            if (window.innerWidth < 768) {
                $dropdown.removeClass('show');
                $dropdown.find('.dropdown-menu').removeClass('show');
            }
        });

        // When a non-dropdown tab is clicked, reset the dropdown toggle state to default
        $(document).on('click', '.nav-tabs > .nav-item:not(.dropdown) > .nav-link', function() {
            var $dropdown = $(this).closest('.nav-tabs').find('.nav-item.dropdown');
            var $toggle = $dropdown.find('.dropdown-toggle');
            var defaultText = $toggle.data('default-text') || $toggle.attr('data-default');
            if (defaultText) {
                var $textSpan = $toggle.find('.selected-text');
                if ($textSpan.length) {
                    $textSpan.text(defaultText);
                } else {
                    $toggle.html('<span class="selected-text">' + defaultText + '</span>');
                }
            }
            $toggle.removeClass('active');
            $dropdown.find('.dropdown-item').removeClass('active');
        });

        // Dynamic Liquid Glass Mobile Dock: compress to icons only on interaction/scroll across all pages
        var dockShrinkTimeout;
        var $mobileDock = $('.sms-mobile-dock, .apple-mobile-dock');

        function triggerDockInteraction() {
            if ($mobileDock.length && window.innerWidth < 992) {
                $mobileDock.addClass('dock-shrunk');
                clearTimeout(dockShrinkTimeout);
                dockShrinkTimeout = setTimeout(function() {
                    $mobileDock.removeClass('dock-shrunk');
                }, 1400);
            }
        }

        $(window).on('scroll', triggerDockInteraction);
        $(document).on('scroll touchmove wheel', '.content-wrapper, .content, body', triggerDockInteraction);

        $mobileDock.on('mouseenter touchstart focusin', function() {
            clearTimeout(dockShrinkTimeout);
            $mobileDock.removeClass('dock-shrunk');
        });

        // Nepal Cascading Address Selector (Province -> District -> Local Government -> Ward)
        function resetSelect($el, placeholder) {
            $el.empty().append($('<option>', { value: '', text: placeholder || '' }));
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.trigger('change.select2');
            }
        }

        $(document).on('change', '.sms-nepal-province', function() {
            var $group = $(this).closest('.sms-nepal-address-group');
            var $district = $group.find('.sms-nepal-district');
            var $local = $group.find('.sms-nepal-local');
            var provinceId = $(this).val();

            resetSelect($district, $district.data('placeholder') || 'Select District');
            resetSelect($local, $local.data('placeholder') || 'Select Local Government');

            if (!provinceId) {
                return;
            }

            $.getJSON('{{ route('nepal_locations.districts') }}', { province_id: provinceId }, function(res) {
                var selectedId = String($district.attr('data-selected') || '');
                $.each(res.data || [], function(_, item) {
                    var label = item.name_ne ? (item.name + ' / ' + item.name_ne) : item.name;
                    var $opt = $('<option>', { value: item.id, text: label });
                    if (selectedId && String(item.id) === selectedId) {
                        $opt.prop('selected', true);
                    }
                    $district.append($opt);
                });
                $district.removeAttr('data-selected');
                if ($district.hasClass('select2-hidden-accessible')) {
                    $district.trigger('change.select2');
                }
                if ($district.val()) {
                    $district.trigger('change');
                }
            });
        });

        $(document).on('change', '.sms-nepal-district', function() {
            var $group = $(this).closest('.sms-nepal-address-group');
            var $local = $group.find('.sms-nepal-local');
            var districtId = $(this).val();

            resetSelect($local, $local.data('placeholder') || 'Select Local Government');

            if (!districtId) {
                return;
            }

            $.getJSON('{{ route('nepal_locations.local_levels') }}', { district_id: districtId }, function(res) {
                var selectedId = String($local.attr('data-selected') || '');
                $.each(res.data || [], function(_, item) {
                    var label = item.name_ne ? (item.name + ' / ' + item.name_ne) : item.name;
                    var $opt = $('<option>', { value: item.id, text: label });
                    if (selectedId && String(item.id) === selectedId) {
                        $opt.prop('selected', true);
                    }
                    $local.append($opt);
                });
                $local.removeAttr('data-selected');
                if ($local.hasClass('select2-hidden-accessible')) {
                    $local.trigger('change.select2');
                }
                if ($local.val()) {
                    $local.trigger('change');
                }
            });
        });

        $(document).on('change', '.sms-nepal-local', function() {
            var $group = $(this).closest('.sms-nepal-address-group');
            var $ward = $group.find('.sms-nepal-ward');
            var localLevelId = $(this).val();

            if (!localLevelId) {
                return;
            }

            $.getJSON('{{ route('nepal_locations.wards') }}', { local_level_id: localLevelId }, function(res) {
                var items = res.data || [];
                if (!items.length) {
                    return;
                }
                var selectedWard = String($ward.attr('data-selected') || $ward.val() || '');
                resetSelect($ward, $ward.data('placeholder') || 'Select Ward');
                $.each(items, function(_, item) {
                    var wNum = item.ward_number || item.id;
                    var label = item.name_ne ? (item.name + ' / ' + item.name_ne) : (item.name || ('Ward ' + wNum));
                    var $opt = $('<option>', { value: wNum, text: label });
                    if (selectedWard && String(wNum) === selectedWard) {
                        $opt.prop('selected', true);
                    }
                    $ward.append($opt);
                });
                $ward.removeAttr('data-selected');
                if ($ward.hasClass('select2-hidden-accessible')) {
                    $ward.trigger('change.select2');
                }
            });
        });

        // Trigger initial cascade if province is preselected (e.g. old() input after validation)
        $('.sms-nepal-province').each(function() {
            if ($(this).val()) {
                $(this).trigger('change');
            }
        });
    });

</script>