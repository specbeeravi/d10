<?php

namespace Drupal\webform_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for Webform Booking.
 */
class WebformBookingController extends ControllerBase {

  /**
   * Get available days.
   */
  public function getAvailableDays($webform_id, $element_id, $date) {
    $webform = Webform::load($webform_id);
    $elements = $webform->getElementsDecodedAndFlattened();
    $config = $elements[$element_id];

    $startDate = isset($config['#start_date']) ? new \DateTime($config['#start_date']) : new \DateTime();
    $endDate = isset($config['#end_date']) ? new \DateTime($config['#end_date']) : (new \DateTime())->modify('+10 years');
    $exclusionPatterns = explode("\n", (string) ($config['#exclusion_dates'] ?? ''));
    $excludedWeekdays = $config['#excluded_weekdays'] ?? [];

    // Prepare the exclusion dates, including intervals.
    $exclusionDates = [];
    foreach ($exclusionPatterns as $pattern) {
      if (strpos($pattern, '|') !== FALSE) {
        [$start, $end] = explode('|', $pattern);
        $period = new \DatePeriod(
          new \DateTime($start),
          new \DateInterval('P1D'),
          (new \DateTime($end))->modify('+1 day')
        );
        foreach ($period as $d) {
          $exclusionDates[] = $d->format('Y-m-d');
        }
      }
      else {
        $exclusionDates[] = $pattern;
      }
    }

    // Parse the input date to get the start and end dates of its month.
    $inputDate = new \DateTime($date);
    $startOfMonth = clone $inputDate;
    $startOfMonth->modify('first day of this month')->setTime(0, 0, 0);
    $endOfMonth = clone $startOfMonth;
    $endOfMonth->modify('last day of this month')->setTime(23, 59, 59);

    // Ensure start and end dates are within the defined range.
    $actualStartDate = $startDate > $startOfMonth ? clone $startDate : clone $startOfMonth;
    $actualEndDate = $endDate < $endOfMonth ? clone $endDate : clone $endOfMonth;

    // Adjusting end date based on 'days_advance' if set.
    if (isset($config['#days_advance']) && is_numeric($config['#days_advance'])) {
      $advanceDate = (new \DateTime())->modify('+' . $config['#days_advance'] . ' days');
      if ($actualStartDate < $advanceDate) {
        $actualStartDate = clone $advanceDate;
      }
    }

    // Adjusting end date based on 'days_visible' if set.
    if (isset($config['#days_visible']) && is_numeric($config['#days_visible']) && $config['#days_visible'] > 0) {
      $visibleDate = (new \DateTime())->modify('+' . $config['#days_visible'] . ' days');
      if ($actualEndDate > $visibleDate) {
        $actualEndDate = clone $visibleDate;
      }
    }

    $interval = new \DateInterval('P1D');
    $dateRange = new \DatePeriod($actualStartDate, $interval, $actualEndDate);

    $availableDays = [];
    foreach ($dateRange as $day) {
      $formattedDay = $day->format('Y-m-d');
      $today = new \DateTime();
      $weekday = $day->format('D');
      $formattedToday = $today->format('Y-m-d');

      if ($formattedDay >= $formattedToday && !in_array($formattedDay, $exclusionDates) && (!isset($excludedWeekdays[$weekday]) || $excludedWeekdays[$weekday] === 0)) {
        // Get slots for this day.
        $slotsResponse = $this->getAvailableSlots($webform_id, $element_id, $formattedDay);
        $slotsData = json_decode($slotsResponse->getContent(), TRUE);

        // Check if there are any available slots.
        $hasAvailableSlots = FALSE;
        if (!empty($slotsData)) {
          foreach ($slotsData as $slot) {
            if ($slot['status'] === 'available') {
              $hasAvailableSlots = TRUE;
              break;
            }
          }
        }

        $availableDays[] = [
          'date' => $formattedDay,
          'hasSlots' => $hasAvailableSlots,
        ];
      }
    }

    return new JsonResponse($availableDays);
  }

  /**
   * Get available slots.
   */
  public function getAvailableSlots($webform_id, $element_id, $date) {
    $webform = Webform::load($webform_id);
    $elements = $webform->getElementsDecodedAndFlattened();
    $config = $elements[$element_id];
    $timeIntervals = explode("\n", $config['#time_interval'] ?? '9:00|16:30');
    $slotDuration = $config['#slot_duration'] ?? 60;
    $totalSeats = $config['#seats_slot'] ?? 1;
    $maxSeatsPerBooking = $config['#max_seats_per_booking'] ?? 1;
    $excludedTimePeriods = explode("\n", $config['#excluded_time_periods'] ?? '');
    $daysAdvance = isset($config['#days_advance']) ? (int) $config['#days_advance'] : 0;

    $availableSlots = [];
    $dateObj = new \DateTime($date);
    $dayOfWeek = $dateObj->format('l');

    // Check if the selected date respects the days_advance setting.
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    $minDate = clone $today;
    if ($daysAdvance > 0) {
      $minDate->modify('+' . $daysAdvance . ' days');
    }

    if ($dateObj < $minDate) {
      return new JsonResponse($availableSlots);
    }

    // For today, we need to check current time.
    $now = new \DateTime();
    $isToday = $dateObj->format('Y-m-d') === $now->format('Y-m-d');

    // Separate intervals into specific days and regular.
    $specificDayIntervals = [];
    $regularIntervals = [];
    foreach ($timeIntervals as $interval) {
      if (strpos($interval, '(') !== FALSE) {
        [$timeInterval, $day] = explode('(', $interval);
        $day = rtrim($day, ')');
        $specificDayIntervals[$day][] = $timeInterval;
      }
      else {
        $regularIntervals[] = $interval;
      }
    }

    // Function to add slots.
    $addSlots = function ($intervals) use (
      &$availableSlots,
      $dateObj,
      $slotDuration,
      $webform_id,
      $element_id,
      $totalSeats,
      $maxSeatsPerBooking,
      $excludedTimePeriods,
      $dayOfWeek,
      $isToday,
      $now
    ) {
      foreach ($intervals as $interval) {
        [$startTimeStr, $endTimeStr] = explode('|', $interval);
        $startTime = new \DateTime($startTimeStr);
        $endTime = new \DateTime($endTimeStr);

        $currentTime = clone $startTime;

        while ($currentTime < $endTime) {
          $endSlotTime = clone $currentTime;
          $endSlotTime->add(new \DateInterval('PT' . $slotDuration . 'M'));
          if ($endSlotTime > $endTime) {
            break;
          }

          // Skip slots that are in the past for today.
          if ($isToday) {
            $slotDateTime = clone $dateObj;
            $slotDateTime->setTime($currentTime->format('H'), $currentTime->format('i'));
            if ($slotDateTime <= $now) {
              $currentTime = clone $endSlotTime;
              continue;
            }
          }

          $timeRange = $currentTime->format('H:i') . '-' . $endSlotTime->format('H:i');

          $isExcluded = FALSE;
          foreach ($excludedTimePeriods as $excludedPeriod) {
            $excludedPeriod = trim($excludedPeriod);
            if (empty($excludedPeriod)) {
              continue;
            }
            [$excludedStart, $excludedEnd] = explode('|', $excludedPeriod);
            $excludedDay = NULL;
            $excludedDate = NULL;
            if (strpos($excludedEnd, '(') !== FALSE) {
              [$excludedEnd, $excludedDayOrDate] = explode('(', $excludedEnd);
              $excludedDayOrDate = rtrim($excludedDayOrDate, ')');
              if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $excludedDayOrDate)) {
                $excludedDate = $excludedDayOrDate;
              }
              else {
                $excludedDay = $excludedDayOrDate;
              }
            }
            $excludedStart = new \DateTime($excludedStart);
            $excludedEnd = new \DateTime($excludedEnd);

            if (($excludedDay === NULL && $excludedDate === NULL) ||
                $excludedDay === $dayOfWeek ||
                $excludedDate === $dateObj->format('Y-m-d')) {
              if ($currentTime >= $excludedStart && $currentTime < $excludedEnd) {
                $isExcluded = TRUE;
                break;
              }
            }
          }

          if (!$isExcluded) {
            $slot = $dateObj->format('Y-m-d') . ' ' . $currentTime->format('H:i');
            $bookedSeats = $this->getBookedSeats($webform_id, $element_id, $slot);
            $availableSeats = $totalSeats - $bookedSeats;
            $slotStatus = $availableSeats > 0 ? 'available' : 'unavailable';
            $availableSlots[] = [
              'time' => $timeRange,
              'status' => $slotStatus,
              'availableSeats' => min($availableSeats, $maxSeatsPerBooking),
            ];
          }
          $currentTime = clone $endSlotTime;
        }
      }
    };

    // Process specific day intervals.
    if (array_key_exists($dayOfWeek, $specificDayIntervals)) {
      $addSlots($specificDayIntervals[$dayOfWeek]);
    }
    else {
      // If no specific intervals for this day, use regular intervals.
      $addSlots($regularIntervals);
    }

    return new JsonResponse($availableSlots);
  }

  /**
   * Get the number of booked seats for a slot.
   */
  protected function getBookedSeats($webform_id, $element_id, $slot) {
    $query = \Drupal::service('database')
      ->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['value'])
      ->condition('wsd.webform_id', $webform_id, '=')
      ->condition('wsd.name', $element_id, '=')
      ->condition('wsd.value', $slot . '%', 'LIKE')
      ->execute();
    $results = $query->fetchAll(\PDO::FETCH_COLUMN);

    $totalBookedSeats = 0;
    foreach ($results as $result) {
      if (strpos($result, '|') !== FALSE) {
        $parts = explode('|', $result);
        $totalBookedSeats += isset($parts[1]) ? intval($parts[1]) : 1;
      }
      else {
        $totalBookedSeats += 1;
      }
    }
    return $totalBookedSeats;
  }

}
