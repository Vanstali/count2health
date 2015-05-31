<?php

namespace Count2Health\AppBundle\FatSecret;

use Count2Health\UserBundle\Entity\User;

abstract class FatSecretEntries
{
    protected $fatSecret;

    public function getEntries(\DateTime $today, User $user,
        $numberToFetch = 14, $includeToday = false)
    {
        $month = clone $today;
        $month->modify('first day of this month');

        $entries = array();
        $emptyMonths = 0;

        while (count($entries) < $numberToFetch * 2) {
            $result = $this->getMonth($month, $user);

            if (isset($result->day) && !empty($result->day) && $emptyMonths < 2) {
                $emptyMonths = 0;
                foreach ($result->day as $day) {
                    $d = $this->fatSecret->dateIntToDateTime(
                                    $day->date_int, $user);

                    if ((true == $includeToday && $d <= $today)
                                        || (false == $includeToday && $d < $today)) {
                        $di = intval($day->date_int);

                        foreach ($entries as $e) {
                            if ($di == intval($e->date_int)) {
                                continue 2;
                            }
                        }
                        $entries[] = $day;
                    }
                }
            } else {
                if (count($entries) == 0) {
                    $emptyMonths++;
                } else {
                    break;
                }
            }

            if (count($entries) < $numberToFetch * 2) {
                $month->sub(new \DateInterval('P1M'));
            }

            if ($emptyMonths >= 2) {
                break;
            }
        }

        usort($entries, function ($a, $b) use ($user) {
                    if ((int) $a->date_int < (int) $b->date_int) {
                        return 1;
                    } elseif ((int) $a->date_int > (int) $b->date_int) {
                        return -1;
                    } else {
                        return 0;
                    }
                    });

        return array_slice($entries, 0, $numberToFetch);
    }

    abstract public function getMonth(\DateTime $date, User $user);
}
