/**
 * Nepali Bikram Sambat (BS) Calendar Engine
 * Fully featured, accessible, and responsive BS month & agenda calendar.
 * Supports BS year & month navigation, Devanagari numerals, category badges, event modals, and AJAX feeds.
 */
(function(window, document, $) {
    'use strict';

    var BS_CALENDAR_DATA = (window.NepaliCalendarHelper && window.NepaliCalendarHelper.bsCalendarData) || {
        2000: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2001: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2002: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2003: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2004: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2005: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2006: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2007: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2008: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2009: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2010: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2011: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2012: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2013: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2014: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2015: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2016: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2017: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2018: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2019: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2020: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2021: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2022: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2023: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2024: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2025: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2026: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2027: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2028: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2029: [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2030: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2031: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2032: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2033: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2034: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2035: [30, 32, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2036: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2037: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2038: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2039: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2040: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2041: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2042: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2043: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2044: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2045: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2046: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2047: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2048: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2049: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2050: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2051: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2052: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2053: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2054: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2055: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2056: [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2057: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2058: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2059: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2060: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2061: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2062: [30, 32, 31, 32, 31, 31, 29, 30, 29, 30, 29, 31],
        2063: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2064: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2065: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2066: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2067: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2068: [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2069: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2070: [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2071: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2072: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2073: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2074: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2075: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2076: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2077: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2078: [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2079: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2080: [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2081: [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2082: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2083: [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2084: [31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30],
        2085: [31, 32, 31, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2086: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2087: [31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30],
        2088: [30, 31, 32, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2089: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2090: [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30]
    };

    var MONTHS_NE = ['वैशाख', 'जेठ', 'असार', 'साउन', 'भदौ', 'असोज', 'कार्तिक', 'मंसिर', 'पुस', 'माघ', 'फागुन', 'चैत'];
    var MONTHS_EN = ['Baishakh', 'Jestha', 'Ashadh', 'Shrawan', 'Bhadra', 'Ashwin', 'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra'];
    var WEEKDAYS_NE = ['आइतबार', 'सोमबार', 'मंगलबार', 'बुधबार', 'बिहीबार', 'शुक्रबार', 'शनिबार'];
    var WEEKDAYS_SHORT_NE = ['आइत', 'सोम', 'मंगल', 'बुध', 'बिही', 'शुक्र', 'शनि'];
    var WEEKDAYS_SHORT_EN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var DEVANAGARI_DIGITS = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

    function toDevanagari(num) {
        return (num + '').replace(/\d/g, function(d) {
            return DEVANAGARI_DIGITS[parseInt(d, 10)];
        });
    }

    function getTodayBs() {
        if (window.NepaliCalendarHelper && typeof window.NepaliCalendarHelper.getTodayBs === 'function') {
            return window.NepaliCalendarHelper.getTodayBs();
        }
        var now = new Date();
        var anchor = new Date(Date.UTC(1943, 3, 14));
        var current = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
        var diffDays = Math.round((current - anchor) / (1000 * 60 * 60 * 24));
        if (diffDays < 0) return '2000-01-01';

        var y = 2000;
        while (y <= 2090 && BS_CALENDAR_DATA[y]) {
            var daysInYear = 0;
            for (var m = 0; m < 12; m++) daysInYear += BS_CALENDAR_DATA[y][m];
            if (diffDays < daysInYear) break;
            diffDays -= daysInYear;
            y++;
        }

        var mIdx = 1;
        if (BS_CALENDAR_DATA[y]) {
            for (var idx = 0; idx < 12; idx++) {
                var dim = BS_CALENDAR_DATA[y][idx];
                if (diffDays < dim) {
                    mIdx = idx + 1;
                    break;
                }
                diffDays -= dim;
            }
        }
        var d = diffDays + 1;
        var mm = mIdx < 10 ? '0' + mIdx : mIdx;
        var dd = d < 10 ? '0' + d : d;
        return y + '-' + mm + '-' + dd;
    }

    function getBsFirstDayOfWeek(year, month) {
        var totalDays = 0;
        for (var y = 2000; y < year; y++) {
            var row = BS_CALENDAR_DATA[y];
            if (row) {
                for (var m = 0; m < 12; m++) totalDays += row[m];
            }
        }
        var curYearRow = BS_CALENDAR_DATA[year];
        if (curYearRow) {
            for (var m = 0; m < month - 1; m++) totalDays += curYearRow[m];
        }
        return (3 + (totalDays % 7)) % 7;
    }

    function NepaliBsCalendar(containerSelector, options) {
        this.container = document.querySelector(containerSelector);
        if (!this.container) return;

        this.options = Object.assign({
            feedUrl: '/calendar/events',
            createEventUrl: '/events/create',
            canCreateEvent: false,
            initialView: 'month', // 'month' or 'agenda'
            categoryFilter: '',
            sessionFilter: '',
            onEventClick: null,
            onDayClick: null
        }, options || {});

        var today = getTodayBs().split('-');
        this.currentYear = parseInt(today[0], 10);
        this.currentMonth = parseInt(today[1], 10);
        this.todayDateStr = getTodayBs();
        this.events = [];
        this.currentView = this.options.initialView;
        this.isLoading = false;

        this.init();
    }

    NepaliBsCalendar.prototype.init = function() {
        this.buildLayout();
        this.bindEvents();
        this.loadEvents();
    };

    NepaliBsCalendar.prototype.buildLayout = function() {
        this.container.innerHTML = `
            <div class="nepali-calendar-wrapper">
                <div class="nepali-calendar-header">
                    <div class="nepali-calendar-nav-left">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm nav-btn-prev" aria-label="Previous Month">
                                <i class="icon-arrow-left12"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm nav-btn-today">
                                आज (Today)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm nav-btn-next" aria-label="Next Month">
                                <i class="icon-arrow-right13"></i>
                            </button>
                        </div>
                        <div class="nepali-calendar-selectors d-inline-flex align-items-center">
                            <select class="form-control form-control-sm select-bs-month mr-1" aria-label="Select BS Month"></select>
                            <select class="form-control form-control-sm select-bs-year mr-2" aria-label="Select BS Year"></select>
                        </div>
                    </div>

                    <div class="nepali-calendar-title-center text-center">
                        <h3 class="nepali-calendar-heading mb-0 font-weight-bold"></h3>
                        <span class="nepali-calendar-subheading text-muted font-size-xs"></span>
                    </div>

                    <div class="nepali-calendar-nav-right d-flex align-items-center justify-content-end">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-outline-primary btn-sm view-btn-month active">
                                <i class="icon-grid mr-1"></i>महिना (Month)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm view-btn-agenda">
                                <i class="icon-list mr-1"></i>सूची (List)
                            </button>
                        </div>
                        <div class="filter-dropdown mr-2">
                            <select class="form-control form-control-sm select-bs-category" aria-label="Filter Category">
                                <option value="">सबै विधा (All Categories)</option>
                                <option value="school_event">School Event</option>
                                <option value="public_holiday">Public Holiday</option>
                                <option value="school_holiday">School Holiday</option>
                                <option value="examination">Examination</option>
                                <option value="assignment_deadline">Assignment Deadline</option>
                                <option value="parent_teacher_meeting">PTM</option>
                                <option value="admission_deadline">Admission</option>
                                <option value="sports_extracurricular">Sports</option>
                                <option value="staff_meeting">Staff Meeting</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="nepali-calendar-body">
                    <div class="nepali-calendar-month-view">
                        <div class="nepali-calendar-weekdays-grid"></div>
                        <div class="nepali-calendar-days-grid"></div>
                    </div>
                    <div class="nepali-calendar-agenda-view" style="display: none;"></div>
                </div>

                <div class="nepali-calendar-loading-overlay" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        `;

        this.populateDropdowns();
        this.renderWeekdayHeaders();
        this.updateHeaderTitle();
    };

    NepaliBsCalendar.prototype.populateDropdowns = function() {
        var monthSel = this.container.querySelector('.select-bs-month');
        monthSel.innerHTML = '';
        for (var m = 1; m <= 12; m++) {
            var opt = document.createElement('option');
            opt.value = m;
            opt.textContent = MONTHS_NE[m - 1] + ' (' + MONTHS_EN[m - 1] + ')';
            if (m === this.currentMonth) opt.selected = true;
            monthSel.appendChild(opt);
        }

        var yearSel = this.container.querySelector('.select-bs-year');
        yearSel.innerHTML = '';
        for (var y = 2090; y >= 2000; y--) {
            var optY = document.createElement('option');
            optY.value = y;
            optY.textContent = toDevanagari(y) + ' (' + y + ' BS)';
            if (y === this.currentYear) optY.selected = true;
            yearSel.appendChild(optY);
        }
    };

    var ALLOWED_HEX_PATTERN = /^#[0-9a-fA-F]{6}$/;
    function sanitizeHexColor(color, defaultColor) {
        if (typeof color === 'string' && ALLOWED_HEX_PATTERN.test(color)) {
            return color;
        }
        return defaultColor || '#111827';
    }

    function hexToRgba(hex, alpha) {
        var safeHex = sanitizeHexColor(hex, '#111827');
        var r = parseInt(safeHex.slice(1, 3), 16);
        var g = parseInt(safeHex.slice(3, 5), 16);
        var b = parseInt(safeHex.slice(5, 7), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    NepaliBsCalendar.prototype.renderWeekdayHeaders = function() {
        var weekdaysGrid = this.container.querySelector('.nepali-calendar-weekdays-grid');
        weekdaysGrid.innerHTML = '';
        for (var i = 0; i < 7; i++) {
            var col = document.createElement('div');
            col.className = 'nepali-calendar-weekday-col' + (i === 6 ? ' is-saturday' : '');

            var spanNe = document.createElement('span');
            spanNe.className = 'weekday-name-ne';
            spanNe.textContent = WEEKDAYS_SHORT_NE[i];

            var spanEn = document.createElement('span');
            spanEn.className = 'weekday-name-en text-muted font-size-xs';
            spanEn.textContent = WEEKDAYS_SHORT_EN[i];

            col.appendChild(spanNe);
            col.appendChild(spanEn);
            weekdaysGrid.appendChild(col);
        }
    };

    NepaliBsCalendar.prototype.updateHeaderTitle = function() {
        var heading = this.container.querySelector('.nepali-calendar-heading');
        var subheading = this.container.querySelector('.nepali-calendar-subheading');

        heading.textContent = MONTHS_NE[this.currentMonth - 1] + ' ' + toDevanagari(this.currentYear);
        subheading.textContent = MONTHS_EN[this.currentMonth - 1] + ' ' + this.currentYear + ' BS';
    };

    NepaliBsCalendar.prototype.bindEvents = function() {
        var self = this;

        // Navigation
        this.container.querySelector('.nav-btn-prev').addEventListener('click', function() {
            self.prevMonth();
        });
        this.container.querySelector('.nav-btn-next').addEventListener('click', function() {
            self.nextMonth();
        });
        this.container.querySelector('.nav-btn-today').addEventListener('click', function() {
            self.goToToday();
        });

        // Selectors
        this.container.querySelector('.select-bs-month').addEventListener('change', function() {
            self.currentMonth = parseInt(this.value, 10);
            self.updateHeaderTitle();
            self.loadEvents();
        });
        this.container.querySelector('.select-bs-year').addEventListener('change', function() {
            self.currentYear = parseInt(this.value, 10);
            self.updateHeaderTitle();
            self.loadEvents();
        });

        // Views
        var btnMonth = this.container.querySelector('.view-btn-month');
        var btnAgenda = this.container.querySelector('.view-btn-agenda');
        btnMonth.addEventListener('click', function() {
            btnMonth.classList.add('active');
            btnAgenda.classList.remove('active');
            self.currentView = 'month';
            self.container.querySelector('.nepali-calendar-month-view').style.display = 'block';
            self.container.querySelector('.nepali-calendar-agenda-view').style.display = 'none';
        });
        btnAgenda.addEventListener('click', function() {
            btnAgenda.classList.add('active');
            btnMonth.classList.remove('active');
            self.currentView = 'agenda';
            self.container.querySelector('.nepali-calendar-month-view').style.display = 'none';
            self.container.querySelector('.nepali-calendar-agenda-view').style.display = 'block';
            self.renderAgendaView();
        });

        // Category filter
        this.container.querySelector('.select-bs-category').addEventListener('change', function() {
            self.options.categoryFilter = this.value;
            self.render();
        });
    };

    NepaliBsCalendar.prototype.prevMonth = function() {
        if (this.currentMonth === 1) {
            if (this.currentYear > 2000) {
                this.currentYear--;
                this.currentMonth = 12;
            }
        } else {
            this.currentMonth--;
        }
        this.syncDropdowns();
        this.updateHeaderTitle();
        this.loadEvents();
    };

    NepaliBsCalendar.prototype.nextMonth = function() {
        if (this.currentMonth === 12) {
            if (this.currentYear < 2090) {
                this.currentYear++;
                this.currentMonth = 1;
            }
        } else {
            this.currentMonth++;
        }
        this.syncDropdowns();
        this.updateHeaderTitle();
        this.loadEvents();
    };

    NepaliBsCalendar.prototype.goToToday = function() {
        var today = getTodayBs().split('-');
        this.currentYear = parseInt(today[0], 10);
        this.currentMonth = parseInt(today[1], 10);
        this.syncDropdowns();
        this.updateHeaderTitle();
        this.loadEvents();
    };

    NepaliBsCalendar.prototype.syncDropdowns = function() {
        this.container.querySelector('.select-bs-month').value = this.currentMonth;
        this.container.querySelector('.select-bs-year').value = this.currentYear;
    };

    NepaliBsCalendar.prototype.loadEvents = function() {
        var self = this;
        var overlay = this.container.querySelector('.nepali-calendar-loading-overlay');
        if (overlay) overlay.style.display = 'flex';

        var url = this.options.feedUrl + '?year_bs=' + this.currentYear + '&month_bs=' + this.currentMonth;
        if (this.options.sessionFilter) {
            url += '&session=' + encodeURIComponent(this.options.sessionFilter);
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function(data) {
            self.events = Array.isArray(data) ? data : [];
            self.render();
        })
        .catch(function(err) {
            console.error('Failed to load Nepali calendar events:', err);
            self.events = [];
            self.render();
        })
        .finally(function() {
            if (overlay) overlay.style.display = 'none';
        });
    };

    NepaliBsCalendar.prototype.render = function() {
        this.renderMonthView();
        if (this.currentView === 'agenda') {
            this.renderAgendaView();
        }
    };

    NepaliBsCalendar.prototype.getFilteredEvents = function() {
        if (!this.options.categoryFilter) return this.events;
        var cat = this.options.categoryFilter;
        return this.events.filter(function(e) {
            return e.category === cat;
        });
    };

    NepaliBsCalendar.prototype.renderMonthView = function() {
        var daysGrid = this.container.querySelector('.nepali-calendar-days-grid');
        daysGrid.innerHTML = '';

        if (!BS_CALENDAR_DATA[this.currentYear] || !BS_CALENDAR_DATA[this.currentYear][this.currentMonth - 1]) {
            console.error('Unsupported BS year or month: ' + this.currentYear + '-' + this.currentMonth);
            return;
        }
        var daysInMonth = BS_CALENDAR_DATA[this.currentYear][this.currentMonth - 1];
        var startDay = getBsFirstDayOfWeek(this.currentYear, this.currentMonth);
        var filteredEvents = this.getFilteredEvents();

        // Empty cells before first day
        for (var i = 0; i < startDay; i++) {
            var emptyCell = document.createElement('div');
            emptyCell.className = 'nepali-calendar-day-cell is-empty';
            daysGrid.appendChild(emptyCell);
        }

        var curMonthPad = this.currentMonth < 10 ? '0' + this.currentMonth : this.currentMonth;

        for (var d = 1; d <= daysInMonth; d++) {
            var dayOfWeek = (startDay + (d - 1)) % 7;
            var isSat = (dayOfWeek === 6);
            var dPad = d < 10 ? '0' + d : d;
            var curDateStr = this.currentYear + '-' + curMonthPad + '-' + dPad;
            var isToday = (curDateStr === this.todayDateStr);

            var cell = document.createElement('div');
            cell.className = 'nepali-calendar-day-cell' + (isSat ? ' is-saturday' : '') + (isToday ? ' is-today' : '');
            cell.setAttribute('data-date-bs', curDateStr);
            cell.setAttribute('tabindex', '0');

            // Find events for this date
            var dayEvents = filteredEvents.filter(function(ev) {
                var s = ev.start_date_bs;
                var e = ev.end_date_bs || s;
                return curDateStr >= s && curDateStr <= e;
            });

            var hasHoliday = dayEvents.some(function(ev) { return ev.is_closure; });
            if (hasHoliday) cell.classList.add('has-holiday');

            var cellHeader = document.createElement('div');
            cellHeader.className = 'nepali-day-cell-header';

            var dayNumNe = document.createElement('span');
            dayNumNe.className = 'day-num-ne';
            dayNumNe.textContent = toDevanagari(d);

            var dayNumEn = document.createElement('span');
            dayNumEn.className = 'day-num-en text-muted font-size-xs';
            dayNumEn.textContent = String(d);

            cellHeader.appendChild(dayNumNe);
            cellHeader.appendChild(dayNumEn);
            cell.appendChild(cellHeader);

            var eventsList = document.createElement('div');
            eventsList.className = 'nepali-day-events-list';

            var maxPills = 3;
            var renderedCount = Math.min(dayEvents.length, maxPills);

            for (var idx = 0; idx < renderedCount; idx++) {
                var ev = dayEvents[idx];
                var safeColor = sanitizeHexColor(ev.color, '#1e88e5');

                var pill = document.createElement('div');
                pill.className = 'nepali-event-pill';
                pill.style.borderLeftColor = safeColor;
                pill.style.backgroundColor = hexToRgba(safeColor, 0.12);
                pill.setAttribute('data-event-id', String(ev.event_id || ev.id || ''));
                pill.setAttribute('title', String(ev.title || ''));

                if (!ev.is_all_day && ev.start_time) {
                    var timeEl = document.createElement('small');
                    timeEl.className = 'mr-1';
                    timeEl.textContent = String(ev.start_time);
                    pill.appendChild(timeEl);
                }

                var titleSpan = document.createElement('span');
                titleSpan.className = 'event-pill-title';
                titleSpan.style.color = '#111827';
                titleSpan.textContent = String(ev.title || '');
                pill.appendChild(titleSpan);

                eventsList.appendChild(pill);
            }

            if (dayEvents.length > maxPills) {
                var moreDiv = document.createElement('div');
                moreDiv.className = 'nepali-event-more text-muted font-size-xs font-weight-semibold';
                moreDiv.textContent = '+' + (dayEvents.length - maxPills) + ' थप (more)';
                eventsList.appendChild(moreDiv);
            }

            cell.appendChild(eventsList);

            this.bindDayCell(cell, curDateStr, dayEvents);
            daysGrid.appendChild(cell);
        }
    };

    NepaliBsCalendar.prototype.bindDayCell = function(cell, dateStr, events) {
        var self = this;

        // Day cell click: if admin and clicked on empty area, open create modal/link
        cell.addEventListener('click', function(e) {
            var pill = e.target.closest('.nepali-event-pill');
            if (pill) {
                var eventId = pill.getAttribute('data-event-id');
                var foundEvent = self.events.find(function(item) {
                    return (item.event_id && ('event_' + item.event_id === eventId || item.event_id == eventId)) || item.id === eventId;
                });
                if (foundEvent) {
                    self.showEventDetails(foundEvent);
                }
                return;
            }

            if (self.options.canCreateEvent) {
                var createUrl = self.options.createEventUrl + '?start_date_bs=' + dateStr;
                window.location.href = createUrl;
            }
        });
    };

    NepaliBsCalendar.prototype.renderAgendaView = function() {
        var agendaContainer = this.container.querySelector('.nepali-calendar-agenda-view');
        agendaContainer.innerHTML = '';

        var filteredEvents = this.getFilteredEvents();
        if (filteredEvents.length === 0) {
            var emptyDiv = document.createElement('div');
            emptyDiv.className = 'text-center py-5 text-muted';

            var icon = document.createElement('i');
            icon.className = 'icon-calendar2 icon-2x mb-2 d-block';
            emptyDiv.appendChild(icon);

            var emptyH6 = document.createElement('h6');
            emptyH6.textContent = 'यस महिनामा कुनै कार्यक्रम छैन (No events scheduled for this month)';
            emptyDiv.appendChild(emptyH6);

            agendaContainer.appendChild(emptyDiv);
            return;
        }

        // Group events by date
        var grouped = {};
        filteredEvents.forEach(function(ev) {
            var key = ev.start_date_bs || 'Unknown';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(ev);
        });

        var dates = Object.keys(grouped).sort();
        var agendaList = document.createElement('div');
        agendaList.className = 'nepali-agenda-list list-group list-group-flush';

        var self = this;

        dates.forEach(function(dateKey) {
            var dateGroup = document.createElement('div');
            dateGroup.className = 'nepali-agenda-date-group border-bottom py-3';

            var badgeRow = document.createElement('div');
            badgeRow.className = 'd-flex align-items-center mb-2';

            var dateBadge = document.createElement('span');
            dateBadge.className = 'badge badge-dark mr-2';
            dateBadge.style.background = '#111827';
            dateBadge.style.fontSize = '0.9rem';
            dateBadge.textContent = dateKey + ' BS';
            badgeRow.appendChild(dateBadge);
            dateGroup.appendChild(badgeRow);

            var itemsGroup = document.createElement('div');
            itemsGroup.className = 'list-group';

            grouped[dateKey].forEach(function(ev) {
                var safeColor = sanitizeHexColor(ev.color, '#1e88e5');
                var timeInfo = ev.is_all_day ? 'दिनभर (All Day)' : (ev.start_time ? ev.start_time + (ev.end_time ? ' - ' + ev.end_time : '') : '');

                var item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action border-0 mb-2 p-3 shadow-sm rounded d-flex justify-content-between align-items-center nepali-agenda-item';
                item.setAttribute('data-event-id', String(ev.event_id || ev.id || ''));
                item.style.borderLeft = '4px solid ' + safeColor;
                item.style.background = '#ffffff';

                var leftDiv = document.createElement('div');

                var topRow = document.createElement('div');
                topRow.className = 'd-flex align-items-center mb-1';

                var catBadge = document.createElement('span');
                catBadge.className = 'badge mr-2 text-white';
                catBadge.style.background = safeColor;
                catBadge.textContent = String(ev.category_name || ev.category || 'Event');

                var titleH6 = document.createElement('h6');
                titleH6.className = 'font-weight-bold mb-0 text-dark';
                titleH6.textContent = String(ev.title || '');

                topRow.appendChild(catBadge);
                topRow.appendChild(titleH6);
                leftDiv.appendChild(topRow);

                var metaRow = document.createElement('div');
                metaRow.className = 'text-muted font-size-xs d-flex align-items-center flex-wrap';

                if (timeInfo) {
                    var timeSpan = document.createElement('span');
                    timeSpan.className = 'mr-3';
                    var timeIcon = document.createElement('i');
                    timeIcon.className = 'icon-clock2 mr-1';
                    timeSpan.appendChild(timeIcon);
                    timeSpan.appendChild(document.createTextNode(timeInfo));
                    metaRow.appendChild(timeSpan);
                }

                if (ev.location) {
                    var locSpan = document.createElement('span');
                    locSpan.className = 'mr-3';
                    var locIcon = document.createElement('i');
                    locIcon.className = 'icon-location4 mr-1';
                    locSpan.appendChild(locIcon);
                    locSpan.appendChild(document.createTextNode(String(ev.location)));
                    metaRow.appendChild(locSpan);
                }

                if (ev.class_name) {
                    var clsSpan = document.createElement('span');
                    clsSpan.className = 'mr-3';
                    var clsIcon = document.createElement('i');
                    clsIcon.className = 'icon-graduation2 mr-1';
                    clsSpan.appendChild(clsIcon);
                    clsSpan.appendChild(document.createTextNode(String(ev.class_name)));
                    metaRow.appendChild(clsSpan);
                }

                leftDiv.appendChild(metaRow);
                item.appendChild(leftDiv);

                var rightDiv = document.createElement('div');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary btn-sm btn-view-event';
                btn.setAttribute('data-event-id', String(ev.event_id || ev.id || ''));
                btn.textContent = 'विवरण (Details)';

                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    self.showEventDetails(ev);
                });

                rightDiv.appendChild(btn);
                item.appendChild(rightDiv);

                item.addEventListener('click', function() {
                    self.showEventDetails(ev);
                });

                itemsGroup.appendChild(item);
            });

            dateGroup.appendChild(itemsGroup);
            agendaList.appendChild(dateGroup);
        });

        agendaContainer.appendChild(agendaList);
    };

    NepaliBsCalendar.prototype.showEventDetails = function(ev) {
        var modal = document.getElementById('eventDetailsModal');
        if (!modal) return;

        var titleEl = modal.querySelector('#eventModalTitle');
        var dateEl = modal.querySelector('#eventModalDate');
        var typeEl = modal.querySelector('#eventModalType');
        var descEl = modal.querySelector('#eventModalDesc');
        var linkEl = modal.querySelector('#eventModalLink');

        var bsDateRange = ev.start_date_bs;
        if (ev.end_date_bs && ev.end_date_bs !== ev.start_date_bs) {
            bsDateRange += ' देखि ' + ev.end_date_bs + ' सम्म';
        }
        if (!ev.is_all_day && ev.start_time) {
            bsDateRange += ' (' + ev.start_time + (ev.end_time ? ' - ' + ev.end_time : '') + ')';
        }

        if (titleEl) titleEl.textContent = String(ev.title || '');
        if (dateEl) dateEl.textContent = bsDateRange;
        if (typeEl) {
            typeEl.textContent = String(ev.category_name || (ev.type ? ev.type.toUpperCase() : 'EVENT'));
            typeEl.style.backgroundColor = sanitizeHexColor(ev.color, '#FF5700');
            typeEl.style.color = '#FFFFFF';
        }

        var fullContent = ev.description || 'कुनै विवरण प्रदान गरिएको छैन (No additional details provided).';
        if (ev.location) {
            fullContent += '\n\nस्थान (Location): ' + ev.location;
        }
        if (ev.instructions) {
            fullContent += '\n\nनिर्देशन (Instructions): ' + ev.instructions;
        }
        if (descEl) descEl.textContent = fullContent;

        if (linkEl) {
            var rawNoticeId = ev.notice_id ? parseInt(ev.notice_id, 10) : null;
            var rawEventId = ev.event_id ? parseInt(ev.event_id, 10) : null;

            if (rawNoticeId && rawNoticeId > 0) {
                linkEl.setAttribute('href', '/notices/' + rawNoticeId);
                linkEl.textContent = 'View Notice';
                linkEl.style.display = 'inline-block';
            } else if (rawEventId && rawEventId > 0 && ev.can_edit) {
                linkEl.setAttribute('href', '/events/' + rawEventId + '/edit');
                linkEl.textContent = 'Edit Event';
                linkEl.style.display = 'inline-block';
            } else {
                linkEl.removeAttribute('href');
                linkEl.style.display = 'none';
            }
        }

        if (window.$ && typeof $(modal).modal === 'function') {
            $(modal).modal('show');
        }
    };

    window.NepaliBsCalendar = NepaliBsCalendar;
})(window, document, window.jQuery);
