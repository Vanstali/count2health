<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Count2Health\AppBundle\Entity\Activity;

/**
 * @Route("/diary/activities")
 */
class ActivitiesDiaryController extends Controller
{
    /**
     * @Route(".html", name="activities_diary")
     * @Route("/date/{date}.html", name="activities_diary_by_date")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function indexAction(Request $request, $date = null)
    {
        $user = $this->getUser();
        $tz = $user->getDateTimeZone();

        $session = $request->getSession();

        if (null === $date) {
            if ($session->has('date')) {
                $date = $session->get('date');
            } else {
                $date = new \DateTime('today', $tz);
            }
        } else {
            $date = new \DateTime($date, $tz);
        }

        $session->set('date', $date);

        $yesterday = clone $date;
        $yesterday->sub(new \DateInterval('P1D'));

        $tomorrow = clone $date;
        $tomorrow->add(new \DateInterval('P1D'));

        $entries = $this->get('fatsecret.exercise_entries')->get($date, $user);

        $canCommit = $this->get('fatsecret.exercise_entries')
        ->isTemplate($entries);
        $activities = array();

        $calories = 0;

        foreach ($entries as $entry) {
            $calories += intval($entry->calories);

            $activity = array();
            $activity['minutes'] = intval($entry->minutes);
            $activity['calories'] = intval($entry->calories);
            $activity['name'] = $this->get('activity_name_parser')
->parse("$entry->exercise_id", "$entry->exercise_name");
            if ($activity['name'] instanceof Activity) {
                $activity['link'] = true;
            } else {
                $activity['link'] = false;
            }

            $activities[] = $activity;
        }

        $fudgeFactor = $this->get('user_stats')
->getFudgeFactor($date, $user);

        $adjustedCalories = round($calories * $fudgeFactor);

        return array(
                'entries' => $activities,
                'date' => $date,
                'canCommit' => $canCommit,
                'calories' => $calories,
'fudgeFactor' => $fudgeFactor,
'adjustedCalories' => $adjustedCalories,
                'yesterday' => $yesterday,
                'tomorrow' => $tomorrow,
            );
    }

        /**
         * @Route("/adjust.html", name="exercise_diary_adjust")
         * @Route("/adjust/{date}.html", name="exercise_diary_adjust_by_date")
         * @Template()
         * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
         */
        public function adjustAction($date = null, Request $request)
        {
            $user = $this->getUser();
            $tz = $user->getDateTimeZone();

            if (null === $date) {
                $date = new \DateTime('today', $tz);
            } else {
                $date = new \DateTime($date, $tz);
            }

            $exercises = $this->get('fatsecret.exercise_entries')->get($date, $user);

            $form = $this->createForm(new ExerciseType($exercises))
                ->add('submit', 'submit', array(
                            'label' => 'Adjust',
                            ))
                ;
            $form->handleRequest($request);

            $types = $this->get('fatsecret.exercises')->get();

            if ($form->isValid()) {
                $data = $form->getData();
                $minutes = $data['time']['hour'] * 60 + $data['time']['minute'];
                $to = $data['to'];
                $from = $data['from'];

                foreach ($types->exercise as $type) {
                    if ((string) $type->exercise_name == $to) {
                        $to = intval($type->exercise_id);
                        break;
                    }
                }

                $this->get('fatsecret.exercise_entry')->edit(
                        $date,
                        $to,
                        $from,
                        $minutes,
                        $user);

                $this->get('memcache')->invalidateNamespace('exercise', $user);

                return $this->redirectToRoute('exercise_diary_by_date',
                        array('date' => $date->format('Y-m-d')));
            }

            $typeArray = array();

            foreach ($types->exercise as $exercise) {
                $typeArray[] = "$exercise->exercise_name";
            }

            return array(
                    'form' => $form->createView(),
                    'types' => json_encode($typeArray),
                    );
        }

/**
 * @Route("/commit/{date}.html", name="activities_diary_commit")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function commitAction($date)
{
    $user = $this->getUser();
    $date = new \DateTime($date, $user->getDateTimeZone());

    $this->get('fatsecret.exercise_entries')->commitDay($date, $user);

    $this->get('memcache')->invalidateNamespace('exercise', $user);

    return $this->redirectToRoute('activities_diary_by_date', array(
            'date' => $date->format('Y-m-d'),
            ));
}

/**
 * @Route("/month.html", name="activities_diary_monthly_log")
 * @Route("/month/{year}/{month}.html",
 *     name="activities_diary_monthly_log_by_date")
 * @Template()
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function getMonthlyLogAction(Request $request, $year = null, $month = null)
{
    $user = $this->getUser();

    $session = $request->getSession();

    if (!$year || !$month) {
        if ($session->has('date')) {
            $d = $session->get('date');
        } else {
            $d = new \DateTime('today', $user->getDateTimeZone());
        }

        $year = $d->format('Y');
        $month = $d->format('m');
    }

    $date = new \DateTime(
        sprintf('%04d-%02d-01', $year, $month),
        $user->getDateTimeZone());

    $lastMonth = clone $date;
    $lastMonth->sub(new \DateInterval('P1M'));

    $nextMonth = clone $date;
    $nextMonth->add(new \DateInterval('P1M'));

    $entries = $this->get('fatsecret.exercise_entries')
    ->getMonth($date, $user);
    $numEntries = count($entries->day);

    $endDate = $this->get('fatsecret')
    ->dateIntToDateTime($entries->to_date_int, $user);

    $fudgeFactor = $this->get('user_stats')
    ->getFudgeFactor($endDate, $user);

    $bmr = $this->get('user_stats')
    ->getBMR($endDate, $user);

    $calories = 0;
    $days = array();

    foreach ($entries->day as $entry) {
        $day = array();
        $day['date'] = $this->get('fatsecret')
            ->dateIntToDateTime($entry->date_int, $user);
        $day['calories'] = intval($entry->calories * $fudgeFactor);
        $calories += $day['calories'];
        array_unshift($days, $day);
    }

    $averageCaloriesPerDay = intval(round($calories / floatval($numEntries)));
    $activityLevel = $averageCaloriesPerDay / floatval($bmr);

    return array(
            'days' => $days,
            'date' => $date,
            'calories' => $calories,
            'fudgeFactor' => $fudgeFactor,
            'bmr' => $bmr,
            'averageCaloriesPerDay' => $averageCaloriesPerDay,
            'activityLevel' => $activityLevel,
            'lastMonth' => $lastMonth,
            'nextMonth' => $nextMonth,
            );
}
}
