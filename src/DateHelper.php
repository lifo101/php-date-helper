<?php

namespace Lifo\PhpDateHelper;

use DateInterval;
use DateTime;
use DateTimeZone;

class DateHelper
{
    /**
     * Create a DateTime object based on the time given. If it's invalid a default DateTime object of today's
     * date will be returned instead.
     *
     * @param string|DateTime   $when
     * @param DateTimeZone|null $tz
     *
     * @return DateTime
     */
    public static function create($when = null, DateTimeZone $tz = null): DateTime
    {
        if ($when instanceof DateTime) {
            // todo; should this be cloned?
            return $when;
        }

        if (is_numeric($when)) {
            $when = '@' . $when;
        }

        $dt = date_create($when, $tz);
        if (!$dt) {
            $dt = new Datetime;
        }

        // must explicitly set timezone if a unix timestamp was given
        if ($tz && substr($when, 0, 1) == '@') {
            $dt->setTimezone($tz);
        }

        return $dt;
    }

    /**
     * Return the Sunday of the date given.
     *
     * @param string|DateTime   $when
     * @param DateTimeZone|null $tz
     *
     * @return DateTime
     */
    public static function sunday($when = null, DateTimeZone $tz = null): DateTime
    {
        $dt = self::create($when, $tz)->setTime(0, 0);
        // if you 'last sunday' when we're already on sunday it'll skip back 1 more week
        if ($dt->format('w') != 0) {
            $dt->modify('last sunday');
        }
        return $dt;
    }

    /**
     * Return the Saturday of the date given.
     *
     * @param string|DateTime   $when
     * @param DateTimeZone|null $tz
     *
     * @return DateTime
     */
    public static function saturday($when = null, DateTimeZone $tz = null): DateTime
    {
        return self::sunday($when, $tz)->modify('6 days');
    }

    /**
     * Return a cloned instance of the date provided. Helper so user code doesn't have to clone and use the object in
     * two separate statements.
     *
     * @param DateTime|string $dt
     *
     * @return DateTime
     */
    public static function copy($dt): DateTime
    {
        return clone self::create($dt);
    }

    /**
     * Return a date string based on the date given. If the date given is null|false, null is returned.
     *
     * @param string|DateTime   $when
     * @param DateTimeZone|null $tz
     * @param string            $format
     *
     * @return null|string
     */
    public static function toDateString($when = null, DateTimeZone $tz = null, string $format = DATE_RFC3339): ?string
    {
        return $when ? self::create($when, $tz)->format($format) : null;
    }

    /**
     * Returns true if the date value given looks like a date string. Does a very minimal check on the string format.
     * If a DateTime object is given always returns true
     *
     * @param string|DateTime $when
     *
     * @return bool
     */
    public static function isDateLike($when): bool
    {
        if (!$when) return false;
        return $when instanceof DateTime || preg_match('/^\d\d\d\d-\d\d-\d\d(T?\d\d:\d\d:\d\d)?/', $when);
    }

    /**
     * Returns a human readable expression of the date interval given up to the total $parts specified.
     * Seconds are NOT included if years,mon or days are present.
     *
     * @param DateInterval|DateTime|string $interval The date or interval to convert
     * @param int                          $parts    Number of interval parts to include.
     *
     * @return string
     */
    public static function timeAgo($interval, int $parts = 3): string
    {
        if (!$interval instanceof DateInterval) {
            if (!$interval instanceof DateTime) {
                $interval = self::create($interval);
            }
            $interval = $interval->diff(new DateTime('now'));
        }

        $format = [];
        if ($interval->y) $format[] = '%y year' . ($interval->y != 1 ? 's' : '');
        if ($interval->m) $format[] = '%m month' . ($interval->m != 1 ? 's' : '');
        if ($interval->d) {
            if ($interval->d > 6) {
                $weeks = floor($interval->d / 7);
                $days = ceil($interval->d % 7);
                $format[] = $weeks . ' week' . ($weeks != 1 ? 's' : '');
                if ($days) {
                    $format[] = $days . ' day' . ($days != 1 ? 's' : '');
                }
            } else {
                $format[] = '%d day' . ($interval->d != 1 ? 's' : '');
            }
        }
        if ($interval->h) $format[] = '%h hour' . ($interval->h != 1 ? 's' : '');
        if ($interval->i) $format[] = '%i minute' . ($interval->i != 1 ? 's' : '');
        if ($interval->s and !($interval->y or $interval->m or $interval->d)) $format[] = '%s second' . ($interval->s != 1 ? 's' : '');
        $fmt = implode(', ', array_slice($format, 0, $parts));
        return $interval->format($fmt);
    }

    /**
     * Returns a short time value depending on how long ago it was to the current time
     *
     * @param DateTime|string $time   The time to convert
     * @param string|null     $now    If null, the current time is used
     * @param array|null      $format An array of formats to use for each time period.
     *                                The keys are the time periods, and the values are the formats.
     *                                The default is:
     *                                [
     *                                  'today' => 'g:i a',
     *                                  'year'  => 'M j',
     *                                  'other' => 'm/d/Y',
     *                                ]
     *
     * @return string
     */
    public static function timeWhen($time, ?string $now = null, ?array $format = null): string
    {
        if (!($time instanceof DateTime)) {
            $time = date_create((string)$time) ?: date_create();
        }
        $now = date_create($now ?? 'now');

        $format = array_replace([
            'today' => 'g:i a',
            'year'  => 'M j',
            'other' => 'm/d/Y',
        ], $format ?? []);
        switch (true) {
            // TODAY: "3:00pm"
            case ($time->format('Y-m-d') == $now->format('Y-m-d')):
                $fmt = $format['today'];
                break;
            // THIS YEAR: "Jan 1"
            case ($time->format('Y') == $now->format('Y')):
                $fmt = $format['year'];
                break;
            // ANOTHER YEAR: "1/1/2022"
            default:
                $fmt = $format['other'];
        }
        return $time->format($fmt);
    }
}