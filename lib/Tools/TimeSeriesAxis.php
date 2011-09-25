<?php
class Tools_TimeSeriesAxis
{
    /**
     * Returns information about allowed accounting periods.
     *
     * @return array
     */
    public static function getPeriodsMetadata()
    {
        $rows = array(
            array(
                "period" => "day",
                "avg_len" => 3600 * 24,
                "caption" => "Daily",
                "uniq" => "Y-m-d",
                "fmt" => "D\nM\nd"
            ),
            array(
                "period" => "week",
                "avg_len" => 3600 * 24 * 7,
                "caption" => "Weekly",
                "uniq" => __CLASS__ . "::getNextWeekendDate",
                "fmt" => "D\nM\nd"
            ),
            array(
                "period" => "month",
                "avg_len" => 3600 * 24 * 30,
                "caption" => "Monthly",
                "uniq" => "Y-m",
                "fmt" => "M\nY"
            ),
            array(
                "period" => "quarter",
                "avg_len" => 3600 * 24 * 30 * 3,
                "caption" => "Quarterly",
                "uniq" => __CLASS__ . "::getQuarterName",
                "fmt" => __CLASS__ . "::getQuarterName"
            ),
            array(
                "period" => "total",
                "avg_len" => 1e10,
                "caption" => "Total",
                "uniq" => "Y-m-d",
                "fmt" => "D\nM\nd"
            ),
        );
        $result = array();
        foreach ($rows as $row) {
            $result[$row['period']] = $row;
        }
        return $result;
    }
    

    /**
     * Returns period information based on period's name.
     *
     * @param string $period
     * @return array()
     */
    public static function getPeriodMetadata($period)
    {
        $metadatas = self::getPeriodsMetadata();
        if (!isset($metadatas[$period])) throw new Exception("No such period: $period");
        return $metadatas[$period];
    }


    /**
     * Returns array of periods names which could be used to create <SELECT>.
     *
     * @return array
     */
    public static function getPeriods()
    {
        $result = array();
        foreach (self::getPeriodsMetadata() as $period => $info) {
            $result[$period] = $info['caption'];
        }
        return $result;
    }


    /**
     * Returns array of time intervals started from $to.
     * First interval is always staredr from $to, other intervals are $period-aligned.
     *
     * @param int $to
     * @param int $back
     * @param string $period
     * @param int $minDate
     * @return array   array(array("to" =>, "from" =>, "caption" =>, "complete"=>[true|false]), ...)
     */
    public static function getAxis($to, $back, $period, $minDate = null)
    {
        $metadata = self::getPeriodsMetadata();
        $meta = self::getPeriodMetadata($period);
        if (!$minDate) $minDate = 0;
        // Find the minimum allowed grid step.
        $decrement = 100000000;
        foreach ($metadata as $v) {
            $decrement = min($decrement, $v['avg_len']);
        }
        // Generate series.
        $series = array();
        for ($time = $to, $i = 0; $i < $back; $i++) {
            if ($time < $minDate) break;
            $from = $time;
            $uniq = self::getUniqForTime($from, $meta);
            while (self::getUniqForTime($from - $decrement, $meta) == $uniq) {
                $from -= $decrement;
            }
            $from = self::trunkTime($from);
            $caption = is_callable($meta['fmt'])? call_user_func($meta['fmt'], $time) : date($meta['fmt'], $time);
            $series[] = array(
                "uniq"          => $uniq,
                "to"            => $time,
                "from"          => $period != "total"? $from : 0,
                "caption"       => $caption,
                "period"        => $period,
                "periodCaption" => $meta['caption'],
                "is_complete"   => self::getUniqForTime($time, $meta) != self::getUniqForTime($time + 1, $meta), // boundary of 2 intervals
                "is_holiday"    => preg_match('/SU|SA/i', $caption),
            );
            $time = $from - 1;
        }
        return $series;
    }


    /**
     * Truncate time to lower bound of minimum accounting interval (e.g. 1 day).
     *
     * @param int $time
     * @return int
     */
    public static function trunkTime($time)
    {
        return strtotime(date('Y-m-d', $time));
    }


    /**
     * Returns uniq key for an interval which includes $time value,
     *
     * @param int $time
     * @param array $meta
     * @return string
     */
    public static function getUniqForTime($time, $meta)
    {
        static $cache = array();
        $fmt = $meta['uniq'];
        if (isset($cache[$fmt][$time])) {
            return $cache[$fmt][$time];
        }
        if (is_callable($fmt)) {
            $u = call_user_func($fmt, $time);
        } else {
            $u = date($fmt, $time);
        }
        return $cache[$fmt][$time] = $u;
    }


    /**
     * Helper function: return a date of the next weekend.
     *
     * @param int $time
     * @return int
     */
    public static function getNextWeekendDate($time)
    {
        while (date("w", $time) != 0) $time += 3600 * 24;
        return date("Y-m-d", $time);
    }


    /**
     * Helper function: return the name of the quarter within which the time is.
     *
     * @param int $time
     * @return int
     */
    public static function getQuarterName($time)
    {
        $mon = intval(date("m", $time));
        $quart = intval(($mon - 1) / 3) + 1;
        return "Q" . $quart . "\n" . date("Y", $time);
    }
}
