<?php

class TM_FireCheckout_Helper_Deliverydate extends Mage_Core_Helper_Abstract
{
    /**
     * Array of available time range strings
     * <pre>
     * 10:00 — 12:00
     * 15:15
     * 13:03 — 14:00
     * </pre>
     *
     * @var array
     */
    protected $_timeRangeStrings = null;

    protected $_excludedDateStrings = null;

    /**
     * Retreive the localized date format to be used for date display
     *
     * @return string
     */
    public function getCalendarDateFormat()
    {
        return Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }

    /**
     * Checks the enabled, use calendad and timerange options
     *
     * @param string $shippingMethod
     * @return boolean
     */
    public function canUseDeliveryDate($shippingMethod = null)
    {
        $enabled     = Mage::getStoreConfig('firecheckout/delivery_date/enabled');
        $useCalendar = Mage::getStoreConfig('firecheckout/delivery_date/use_calendar');
        $useRange    = Mage::getStoreConfig('firecheckout/delivery_date/use_time_range');
        $enabled     = $enabled && ($useCalendar || $useRange);

        if (!$enabled || !$shippingMethod) {
            return $enabled;
        }

        if (!Mage::getStoreConfig('firecheckout/delivery_date/filter_per_shipping_method')) {
            return $enabled;
        }

        $shippingMethods = explode(',', Mage::getStoreConfig('firecheckout/delivery_date/shipping_methods'));

        return in_array($shippingMethod, $shippingMethods);
    }

    /**
     * Retrieve an array of timerange strings
     * <pre>
     * 10:00 — 12:00
     * 15:15
     * 13:03 — 14:00
     * </pre>
     *
     * @return array
     */
    public function getTimeRangeStrings()
    {
        if (null === $this->_timeRangeStrings) {
            $ranges = unserialize(Mage::getStoreConfig('firecheckout/delivery_date/time_range'));
            $result = array();
            foreach ($ranges as $range) {
                $start = implode(':', $range['from']);
                $end   = implode(':', $range['to']);

                if ($start === $end) {
                    $result[] = $start;
                } else {
                    $result[] = $start . ' — ' . $end;
                }
            }
            $this->_timeRangeStrings = $result;
        }
        return $this->_timeRangeStrings;
    }

    /**
     * Validates timerange string (12:00 — 12:30)
     *
     * @param string $range
     * @return boolean
     */
    public function isValidTimeRange($range)
    {
        return in_array($range, $this->getTimeRangeStrings());
    }

    /**
     * Validates date string
     *
     * @param Zend_Date $date
     * @return boolean
     */
    public function isValidDate(Zend_Date $date)
    {
        // check for past date
        $offset = (int)Mage::getStoreConfig('firecheckout/delivery_date/date_offset');
        $minAllowedDate  = Mage::app()->getLocale()->date() // today + offset
            ->setTime('00:00:00')
            ->addDay($offset);

        // compare with end of delivery processing day
        $endTime = $this->getEndOfDeliveryProcessingDay();
        $endTime = $endTime->getTime();
        $nowTime = Mage::app()->getLocale()->date()->getTime();
        if ($nowTime->compare($endTime) > 0) { // delivery processing day is ended
            $minAllowedDate->addDay(1);
        }

        if (1 === $minAllowedDate->compare($date)) {
            return false;
        }

        $period = (int)Mage::getStoreConfig('firecheckout/delivery_date/date_period');
        $maxAllowedDate = $minAllowedDate->addDay($period)->setTime('23:59:59');
        if (-1 === $maxAllowedDate->compare($date)) {
            return false;
        }

        $dateString         = $date->toString('MM/dd/yyyy');
        $nonPeriodicalDates = $this->getExcludedNonPeriodicalDateStrings();
        $periodicalDates    = $this->getExcludedPeriodicalDateStrings();

        if (in_array($dateString, $nonPeriodicalDates)
            || in_array(substr($dateString, 0, 7), $periodicalDates)) {

            return false;
        }

        if (!Mage::getStoreConfig('firecheckout/delivery_date/exclude_weekend')) {
            return true;
        }

        $weekandDays = Mage::getStoreConfig('general/locale/weekend');
        $weekandDays = explode(',', $weekandDays);
        $weekDay     = $date->get(Zend_Date::WEEKDAY_DIGIT);

        return !in_array($weekDay, $weekandDays);
    }

    /**
     * Retrieve time of the delivery processing day end
     *
     * @return Zend_Date
     */
    public function getEndOfDeliveryProcessingDay()
    {
        $time = Mage::getStoreConfig('firecheckout/delivery_date/delivery_processing_end_time');
        $time = str_replace(',', ':', $time);
        return Mage::app()->getLocale()->date()->setTime($time);
    }

    /**
     * Retrieve an array of excluded dates
     * <pre>
     * 12/15/2011
     * 01/19/2012
     * 31/12/2
     * </pre>
     *
     * @return array
     */
    public function getExcludedDateStrings()
    {
        if (null === $this->_excludedDateStrings) {
            $dates  = unserialize(Mage::getStoreConfig('firecheckout/delivery_date/excluded_dates'));
            $result = array();
            foreach ($dates as $dateArray) {
                $result[] = implode('/', $dateArray['date']);
            }
            $this->_excludedDateStrings = $result;
        }
        return $this->_excludedDateStrings;
    }

    /**
     * Retrieve an array of excluded dates
     * <pre>
     * 31/12/2
     * 01/01/2
     * </pre>
     *
     * @return array
     */
    public function getExcludedPeriodicalDateStrings()
    {
        $excludedDates = $this->getExcludedDateStrings();
        return array_values(array_filter($excludedDates, array($this, '_filterPeriodicalDates')));
    }

    /**
     * Retrieve an array of excluded dates
     * <pre>
     * 31/12/2011
     * 01/01/2012
     * </pre>
     *
     * @return array
     */
    public function getExcludedNonPeriodicalDateStrings()
    {
        $excludedDates = $this->getExcludedDateStrings();
        return array_values(array_filter($excludedDates, array($this, '_filterNonPeriodicalDates')));
    }

    protected function _filterPeriodicalDates($var)
    {
        return strlen($var) === 7;
    }

    protected function _filterNonPeriodicalDates($var)
    {
        return strlen($var) === 10;
    }
}
