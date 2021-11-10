<?php

namespace Lifo\PhpDateHelper;

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
}