/**
 * Nepali Bikram Sambat Date Picker
 * Lightweight, accessible, and standalone datepicker for BS calendar (2000-2090 BS).
 */
(function() {
    'use strict';

    // Verified BS Month Length Data (2000 BS to 2090 BS)
    var bsCalendarData = {
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

    var monthsNe = [
        'वैशाख', 'जेठ', 'असार', 'साउन', 'भदौ', 'असोज',
        'कार्तिक', 'मंसिर', 'पुस', 'माघ', 'फागुन', 'चैत'
    ];

    var monthsEn = [
        'Baishakh', 'Jestha', 'Ashadh', 'Shrawan', 'Bhadra', 'Ashwin',
        'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra'
    ];

    var devanagariDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

    function toDevanagari(num) {
        return (num + '').replace(/\d/g, function(d) {
            return devanagariDigits[parseInt(d)];
        });
    }

    // Convert BS date to day of week using total elapsed days from known anchor (2000-01-01 BS = Wednesday, dayOfWeek 3 in 0=Sun..6=Sat)
    function getBsFirstDayOfWeek(year, month) {
        var totalDays = 0;
        for (var y = 2000; y < year; y++) {
            var row = bsCalendarData[y];
            if (row) {
                for (var m = 0; m < 12; m++) {
                    totalDays += row[m];
                }
            }
        }
        var curYearRow = bsCalendarData[year];
        if (curYearRow) {
            for (var m = 0; m < month - 1; m++) {
                totalDays += curYearRow[m];
            }
        }
        // 2000-01-01 was Wednesday (index 3)
        return (3 + (totalDays % 7)) % 7;
    }

    function getTodayBs() {
        var now = new Date();
        var anchor = new Date(Date.UTC(1943, 3, 14)); // 1943-04-14 AD = 2000-01-01 BS
        var current = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
        var diffDays = Math.round((current - anchor) / (1000 * 60 * 60 * 24));
        if (diffDays < 0) return '2000-01-01';

        var y = 2000;
        while (y <= 2090 && bsCalendarData[y]) {
            var daysInYear = 0;
            for (var m = 0; m < 12; m++) {
                daysInYear += bsCalendarData[y][m];
            }
            if (diffDays < daysInYear) {
                break;
            }
            diffDays -= daysInYear;
            y++;
        }

        var m = 1;
        if (bsCalendarData[y]) {
            for (var idx = 0; idx < 12; idx++) {
                var dim = bsCalendarData[y][idx];
                if (diffDays < dim) {
                    m = idx + 1;
                    break;
                }
                diffDays -= dim;
            }
        }
        var d = diffDays + 1;
        var mm = m < 10 ? '0' + m : m;
        var dd = d < 10 ? '0' + d : d;
        return y + '-' + mm + '-' + dd;
    }

    var popup = null;
    var activeInput = null;

    function createPopup() {
        if (popup) return popup;
        popup = document.createElement('div');
        popup.className = 'nepali-datepicker-popup';
        popup.innerHTML = `
            <div class="nepali-datepicker-header">
                <select class="nepali-dp-year"></select>
                <select class="nepali-dp-month"></select>
            </div>
            <div class="nepali-datepicker-weekdays">
                <span>आइत</span><span>सोम</span><span>मंगल</span><span>बुध</span><span>बिही</span><span>शुक्र</span><span style="color:#dc3545">शनि</span>
            </div>
            <div class="nepali-datepicker-days"></div>
            <div class="nepali-datepicker-footer">
                <span class="nepali-datepicker-today-btn">आज (Today)</span>
                <span class="nepali-datepicker-close-btn" style="cursor:pointer;">बन्द (Close)</span>
            </div>
        `;
        document.body.appendChild(popup);

        // Populate year dropdown (2000-2090)
        var yearSelect = popup.querySelector('.nepali-dp-year');
        var isNe = document.documentElement.lang === 'ne' || true;
        for (var y = 2090; y >= 2000; y--) {
            var opt = document.createElement('option');
            opt.value = y;
            opt.textContent = isNe ? toDevanagari(y) + ' BS' : y + ' BS';
            yearSelect.appendChild(opt);
        }

        // Populate month dropdown
        var monthSelect = popup.querySelector('.nepali-dp-month');
        for (var m = 1; m <= 12; m++) {
            var opt = document.createElement('option');
            opt.value = m;
            opt.textContent = monthsNe[m - 1] + ' (' + monthsEn[m - 1] + ')';
            monthSelect.appendChild(opt);
        }

        yearSelect.addEventListener('change', renderCalendar);
        monthSelect.addEventListener('change', renderCalendar);

        popup.querySelector('.nepali-datepicker-today-btn').addEventListener('click', function() {
            if (activeInput) {
                activeInput.value = getTodayBs();
                triggerChange(activeInput);
                hidePopup();
            }
        });

        popup.querySelector('.nepali-datepicker-close-btn').addEventListener('click', hidePopup);

        document.addEventListener('click', function(e) {
            if (popup && popup.style.display === 'block') {
                if (!popup.contains(e.target) && e.target !== activeInput) {
                    hidePopup();
                }
            }
        });

        return popup;
    }

    function renderCalendar() {
        if (!popup) return;
        var year = parseInt(popup.querySelector('.nepali-dp-year').value);
        var month = parseInt(popup.querySelector('.nepali-dp-month').value);
        var daysContainer = popup.querySelector('.nepali-datepicker-days');
        daysContainer.innerHTML = '';

        var daysInMonth = (bsCalendarData[year] && bsCalendarData[year][month - 1]) ? bsCalendarData[year][month - 1] : 30;
        var startDay = getBsFirstDayOfWeek(year, month);

        // Empty cells before first day
        for (var i = 0; i < startDay; i++) {
            var emptyCell = document.createElement('div');
            emptyCell.className = 'nepali-datepicker-day empty';
            daysContainer.appendChild(emptyCell);
        }

        // Selected value check
        var curVal = activeInput ? activeInput.value : '';
        var selectedDay = -1;
        if (curVal) {
            var parts = curVal.split('-');
            if (parts.length === 3 && parseInt(parts[0]) === year && parseInt(parts[1]) === month) {
                selectedDay = parseInt(parts[2]);
            }
        }

        var todayBs = getTodayBs();
        var todayParts = todayBs.split('-');
        var todayYear = parseInt(todayParts[0]);
        var todayMonth = parseInt(todayParts[1]);
        var todayDay = parseInt(todayParts[2]);

        for (var d = 1; d <= daysInMonth; d++) {
            var dayCell = document.createElement('div');
            var dayOfWeek = (startDay + (d - 1)) % 7;
            var isSat = (dayOfWeek === 6);
            var isSel = (d === selectedDay);
            var isToday = (year === todayYear && month === todayMonth && d === todayDay);

            dayCell.className = 'nepali-datepicker-day' + (isSat ? ' saturday' : '') + (isSel ? ' selected' : '') + (isToday ? ' today' : '');
            dayCell.textContent = toDevanagari(d);
            dayCell.setAttribute('data-day', d);

            dayCell.addEventListener('click', function() {
                var chosenDay = parseInt(this.getAttribute('data-day'));
                var formattedMonth = month < 10 ? '0' + month : month;
                var formattedDay = chosenDay < 10 ? '0' + chosenDay : chosenDay;
                var dateStr = year + '-' + formattedMonth + '-' + formattedDay;
                if (activeInput) {
                    activeInput.value = dateStr;
                    triggerChange(activeInput);
                }
                hidePopup();
            });

            daysContainer.appendChild(dayCell);
        }
    }

    function triggerChange(element) {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('change', true, true);
        element.dispatchEvent(evt);
    }

    function showPopup(input) {
        createPopup();
        activeInput = input;

        var todayBs = getTodayBs();
        var defaultParts = todayBs.split('-');
        var defaultYear = parseInt(defaultParts[0]);
        var defaultMonth = parseInt(defaultParts[1]);

        if (input.value) {
            var p = input.value.split('-');
            if (p.length === 3 && bsCalendarData[parseInt(p[0])]) {
                defaultYear = parseInt(p[0]);
                defaultMonth = parseInt(p[1]);
            }
        }

        popup.querySelector('.nepali-dp-year').value = defaultYear;
        popup.querySelector('.nepali-dp-month').value = defaultMonth;
        renderCalendar();

        var rect = input.getBoundingClientRect();
        popup.style.top = (window.scrollY + rect.bottom + 4) + 'px';
        popup.style.left = (window.scrollX + rect.left) + 'px';
        popup.style.display = 'block';
    }

    function hidePopup() {
        if (popup) {
            popup.style.display = 'none';
            activeInput = null;
        }
    }

    function initNepaliDatePicker() {
        var inputs = document.querySelectorAll('.nepali-date, .nepali-datepicker, input[data-nepali-date]');
        inputs.forEach(function(input) {
            if (!input.dataset.nepaliInitialized) {
                input.dataset.nepaliInitialized = 'true';
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('placeholder', 'YYYY-MM-DD (BS)');
                input.addEventListener('focus', function() {
                    showPopup(this);
                });
                input.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showPopup(this);
                });
                input.addEventListener('input', function() {
                    var val = this.value;
                    var eng = val.replace(/[०-९]/g, function(d) {
                        return '०१२३४५६७८९'.indexOf(d);
                    });
                    if (val !== eng) {
                        this.value = eng;
                    }
                });
            }
        });
    }

    window.initNepaliDatePicker = initNepaliDatePicker;
    window.NepaliCalendarHelper = {
        bsCalendarData: bsCalendarData,
        monthsNe: monthsNe,
        monthsEn: monthsEn,
        devanagariDigits: devanagariDigits,
        toDevanagari: toDevanagari,
        getBsFirstDayOfWeek: getBsFirstDayOfWeek,
        getTodayBs: getTodayBs
    };

    document.addEventListener('DOMContentLoaded', function() {
        initNepaliDatePicker();
    });
})();

