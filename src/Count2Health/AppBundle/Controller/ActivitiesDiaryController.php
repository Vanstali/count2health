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
        $tz = new \DateTimeZone($user->getSetting()->getTimeZone());

$session = $request->getSession();

        if (null === $date) {
if ($session->has('date')) {
$date = $session->get('date');
}
else {
            $date = new \DateTime('today', $tz);
}
        }
            else {
                $date = new \DateTime($date, $tz);
            }

$session->set('date', $date);

        $yesterday = clone $date;
        $yesterday->sub(new \DateInterval('P1D'));

        $tomorrow = clone $date;
        $tomorrow->add(new \DateInterval('P1D'));

        $entries = $this->get('fatsecret.exercise_entries')->get($date, $user);
$activities = array();

        $calories = 0;

        foreach ($entries as $entry)
        {
            $calories += intval($entry->calories);

$activity = array();
$activity['minutes'] = intval($entry->minutes);
$activity['calories'] = intval($entry->calories);
$activity['name'] = $this->get('activity_name_parser')
->parse("$entry->exercise_id", "$entry->exercise_name");
if ($activity['name'] instanceof Activity) {
$activity['link'] = true;
}
else {
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
                'calories' => $calories,
'fudgeFactor' => $fudgeFactor,
'adjustedCalories' => $adjustedCalories,
                'yesterday' => $yesterday,
                'tomorrow' => $tomorrow,
            );    }

        /**
         * @Route("/adjust.html", name="exercise_diary_adjust")
         * @Route("/adjust/{date}.html", name="exercise_diary_adjust_by_date")
         * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
         */
        public function adjustAction($date = null, Request $request)
        {
            $user = $this->getUser();
            $tz = new \DateTimeZone($user->getSetting()->getTimeZone());

            if (null === $date) {
                $date = new \DateTime('today', $tz);
            }
            else {
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

                foreach ($types->exercise as $type)
                {
                    if ((string)$type->exercise_name == $to) {
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

            foreach ($types->exercise as $exercise)
            {
                $typeArray[] = "$exercise->exercise_name";
            }

            return array(
                    'form' => $form->createView(),
                    'types' => json_encode($typeArray),
                    );
        }

}
