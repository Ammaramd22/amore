/**
 * Business/system clock — uses configured IANA timezone + optional server epoch sync.
 * Does not use the browser's local timezone for display.
 */
(function (global) {
    'use strict';

    var cfg = global.__BUSINESS_CLOCK__ || {};
    var tz = cfg.timezone || 'Asia/Colombo';
    var offsetMs = typeof cfg.server_ms === 'number' ? (cfg.server_ms - Date.now()) : 0;

    function nowDate() {
        return new Date(Date.now() + offsetMs);
    }

    function syncFromServerMs(serverMs) {
        if (typeof serverMs === 'number' && isFinite(serverMs)) {
            offsetMs = serverMs - Date.now();
        }
    }

    function setTimezone(iana) {
        if (iana && typeof iana === 'string') {
            tz = iana;
        }
    }

    function format(opts) {
        opts = opts || {};
        var d = nowDate();
        var locale = opts.locale || 'en-US';
        var base = { timeZone: tz };
        try {
            if (opts.dateStyle || opts.timeStyle) {
                return d.toLocaleString(locale, Object.assign(base, {
                    dateStyle: opts.dateStyle,
                    timeStyle: opts.timeStyle
                }));
            }
            return d.toLocaleString(locale, Object.assign(base, {
                weekday: opts.weekday,
                year: opts.year,
                month: opts.month,
                day: opts.day,
                hour: opts.hour || '2-digit',
                minute: opts.minute || '2-digit',
                second: opts.second,
                hour12: opts.hour12 !== false
            }));
        } catch (e) {
            return d.toLocaleString(locale, opts);
        }
    }

    function formatDate() {
        var d = nowDate();
        try {
            return d.toLocaleDateString('en-US', {
                timeZone: tz,
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (e) {
            return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    }

    function formatTime(withSeconds) {
        var d = nowDate();
        var opts = {
            timeZone: tz,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        if (withSeconds) opts.second = '2-digit';
        try {
            return d.toLocaleTimeString('en-US', opts);
        } catch (e) {
            delete opts.timeZone;
            return d.toLocaleTimeString('en-US', opts);
        }
    }

    function formatDateTime() {
        return format({
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }

    /**
     * Bind a live clock to one or more elements.
     * @param {string|Element|NodeList} targets
     * @param {object} options { mode: 'time'|'datetime'|'date'|'custom', seconds, showTz, onTick }
     */
    function bind(targets, options) {
        options = options || {};
        var els = [];
        if (typeof targets === 'string') {
            els = Array.prototype.slice.call(document.querySelectorAll(targets));
        } else if (targets && targets.nodeType) {
            els = [targets];
        } else if (targets && targets.length) {
            els = Array.prototype.slice.call(targets);
        }
        if (!els.length) return function () {};

        function tick() {
            var text;
            if (typeof options.onTick === 'function') {
                text = options.onTick(nowDate(), tz);
            } else if (options.mode === 'datetime') {
                text = formatDateTime();
            } else if (options.mode === 'date') {
                text = formatDate();
            } else if (options.mode === 'split') {
                // no single text — handled below
                text = null;
            } else {
                text = formatTime(!!options.seconds);
            }
            if (options.showTz && text) {
                text = text + (options.tzSeparator || ' · ') + tz;
            }
            els.forEach(function (el) {
                if (!el) return;
                if (options.mode === 'split') {
                    var dateEl = el.querySelector('[data-clock-date]');
                    var timeEl = el.querySelector('[data-clock-time]');
                    var tzEl = el.querySelector('[data-clock-tz]');
                    if (dateEl) dateEl.textContent = formatDate();
                    if (timeEl) timeEl.textContent = formatTime(!!options.seconds);
                    if (tzEl) tzEl.textContent = tz;
                } else if (text != null) {
                    el.textContent = text;
                }
            });
        }

        tick();
        var id = setInterval(tick, options.intervalMs || 1000);
        return function stop() { clearInterval(id); };
    }

    /** Format an absolute instant (ISO/ms/Date) in the business timezone. */
    function formatInstant(value, opts) {
        opts = opts || {};
        var d;
        if (value instanceof Date) d = value;
        else if (typeof value === 'number') d = new Date(value);
        else d = new Date(value);
        if (isNaN(d.getTime())) return '';
        try {
            return d.toLocaleString(opts.locale || 'en-US', Object.assign({
                timeZone: tz,
                hour: opts.hour || '2-digit',
                minute: opts.minute || '2-digit',
                second: opts.second,
                hour12: opts.hour12 !== false,
                year: opts.year,
                month: opts.month,
                day: opts.day
            }, opts.extra || {}));
        } catch (e) {
            return d.toLocaleString();
        }
    }

    global.BusinessClock = {
        now: nowDate,
        timezone: function () { return tz; },
        syncFromServerMs: syncFromServerMs,
        setTimezone: setTimezone,
        format: format,
        formatTime: formatTime,
        formatDate: formatDate,
        formatDateTime: formatDateTime,
        formatInstant: formatInstant,
        bind: bind
    };
})(typeof window !== 'undefined' ? window : this);
