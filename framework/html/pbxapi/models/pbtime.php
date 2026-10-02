<?php
/*
 * Time group range format, shared by the read projection and the plans.
 *
 * Issabel stores one row per range in timegroups_details.time as four
 * pipe-separated parts:
 *
 *     hours|weekdays|monthdays|months      e.g. 09:00-18:00|mon-fri|*|*
 *
 * Each part is either a single value or a "start-end" pair; the parser treats a
 * missing end as equal to the start. The format here reproduces exactly what
 * timegroups::constructDateString() writes, so a range built by a plan and a
 * range created from the GUI produce the same row.
 *
 * Validation throws InvalidArgumentException rather than emitting an HTTP
 * response, so the rules are testable without a request.
 */
class pbtime {

    public static function weekdays() {
        return array('*','mon','tue','wed','thu','fri','sat','sun');
    }

    public static function months() {
        return array('*','jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
    }

    public static function fields() {
        return array('start_hour','end_hour','start_weekday','end_weekday',
                     'start_monthday','end_monthday','start_month','end_month');
    }

    public static function defaults() {
        return array(
            'start_hour'=>'00:00','end_hour'=>'23:59',
            'start_weekday'=>'*','end_weekday'=>'*',
            'start_monthday'=>'*','end_monthday'=>'*',
            'start_month'=>'*','end_month'=>'*'
        );
    }

    // Returns the range with defaults applied, or throws with a message meant
    // for the caller's own error format.
    public static function normalizeRange($range) {
        if(!is_array($range)) { throw new InvalidArgumentException('Each range must be an object'); }
        $allowed = self::fields();
        foreach($range as $key=>$value) {
            if(!in_array($key, $allowed, true)) { throw new InvalidArgumentException('Range field is not allowed: '.$key); }
        }
        $normalized = array();
        foreach(self::defaults() as $key=>$fallback) {
            $normalized[$key] = array_key_exists($key, $range) ? $range[$key] : $fallback;
            if(!is_string($normalized[$key])) { throw new InvalidArgumentException($key.' must be a string'); }
        }
        foreach(array('start_hour','end_hour') as $key) {
            if(!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $normalized[$key])) {
                throw new InvalidArgumentException($key.' must use the HH:MM format');
            }
        }
        foreach(array('start_weekday','end_weekday') as $key) {
            if(!in_array($normalized[$key], self::weekdays(), true)) {
                throw new InvalidArgumentException($key.' must be one of * '.implode(' ', array_slice(self::weekdays(), 1)));
            }
        }
        foreach(array('start_monthday','end_monthday') as $key) {
            if($normalized[$key] !== '*' && (!ctype_digit($normalized[$key]) || intval($normalized[$key]) < 1 || intval($normalized[$key]) > 31)) {
                throw new InvalidArgumentException($key.' must be a day between 1 and 31, or *');
            }
        }
        foreach(array('start_month','end_month') as $key) {
            if(!in_array($normalized[$key], self::months(), true)) {
                throw new InvalidArgumentException($key.' must be one of * '.implode(' ', array_slice(self::months(), 1)));
            }
        }
        return $normalized;
    }

    // '09:00-18:00|mon-fri|*|*'. A pair with equal ends collapses to a single
    // value, which is what the GUI writes and what parse() reads back.
    public static function format($range) {
        $parts = array();
        $parts[] = $range['start_hour'].'-'.$range['end_hour'];
        $parts[] = self::pair($range['start_weekday'], $range['end_weekday']);
        $parts[] = self::pair($range['start_monthday'], $range['end_monthday']);
        $parts[] = self::pair($range['start_month'], $range['end_month']);
        return implode('|', $parts);
    }

    protected static function pair($start, $end) {
        return ($start === $end) ? (string)$start : $start.'-'.$end;
    }

    // Tolerant: unparseable input yields the defaults rather than an error, so a
    // read projection never fails because of one odd row.
    public static function parse($time) {
        $defaults = self::defaults();
        $parts = explode('|', (string)$time);
        $hours = self::split(isset($parts[0]) ? $parts[0] : '');
        $weekdays = self::split(isset($parts[1]) ? $parts[1] : '');
        $monthdays = self::split(isset($parts[2]) ? $parts[2] : '');
        $months = self::split(isset($parts[3]) ? $parts[3] : '');
        // A missing end always mirrors the resolved start, never the raw value,
        // so an empty part falls back to the documented default on both sides.
        $startWeekday = $weekdays[0] !== '' ? $weekdays[0] : $defaults['start_weekday'];
        $startMonthday = $monthdays[0] !== '' ? $monthdays[0] : $defaults['start_monthday'];
        $startMonth = $months[0] !== '' ? $months[0] : $defaults['start_month'];
        return array(
            'start_hour'=>$hours[0] !== '' ? $hours[0] : $defaults['start_hour'],
            'end_hour'=>$hours[1] !== '' ? $hours[1] : $defaults['end_hour'],
            'start_weekday'=>$startWeekday,
            'end_weekday'=>$weekdays[1] !== '' ? $weekdays[1] : $startWeekday,
            'start_monthday'=>$startMonthday,
            'end_monthday'=>$monthdays[1] !== '' ? $monthdays[1] : $startMonthday,
            'start_month'=>$startMonth,
            'end_month'=>$months[1] !== '' ? $months[1] : $startMonth
        );
    }

    // Returns array(start, end); a single value repeats as its own end.
    protected static function split($part) {
        $values = explode('-', trim((string)$part), 2);
        $start = trim($values[0]);
        $end = isset($values[1]) ? trim($values[1]) : $start;
        return array($start, $end);
    }
}
