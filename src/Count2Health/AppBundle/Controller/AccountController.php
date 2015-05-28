<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Doctrine\Common\Collections\Criteria;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Form\SettingType;
use Count2Health\AppBundle\Entity\Setting;
use Count2Health\AppBundle\Form\HealthPlanType;
use Count2Health\AppBundle\Entity\HealthPlan;
use Count2Health\AppBundle\Form\WeightDiaryEntryType;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;

/**
 * @Route("/account")
 */
class AccountController extends Controller
{
    /**
     * @Route(".html", name="account")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getEntityManager();

$date = new \DateTime('today', $user->getDateTimeZone());

        $steps = $this->getSteps();

        $vars = array(
                'user' => $user,
                'steps' => $steps,
            );

        if (null !== $user->getPersonalDetails()) {
        $vars['bmr'] = $this->get('user_stats')->getBMR($date, $user);
        $vars['tdee'] = $this->get('user_stats')->getEstimatedTDEE($date, $user);
        $vars['inferredTdee'] = $this->get('user_stats')->getInferredTDEE($date, $user);

        $weightLossPerWeek = $this->get('user_stats')
            ->getWeightLossPerWeek($user);

        if (null !== $weightLossPerWeek) {
            $vars['weightLossPerWeek'] = $weightLossPerWeek;
        }

        $lastWeekWeightLoss = $this->get('user_stats')
            ->getLastWeekWeightLoss($user);

        if (null !== $lastWeekWeightLoss) {
            $vars['weightLossLast7Days'] = $lastWeekWeightLoss;
        }

        $lastMonthWeightLoss = $this->get('user_stats')
            ->getLastMonthWeightLoss($user);

        if (null !== $lastMonthWeightLoss) {
            $vars['weightLossLast30Days'] = $lastMonthWeightLoss;
        }

        $caloriesConsumedPerDay = $this->get('user_stats')
            ->getCaloriesConsumedPerDay($date, $user);

        if (null !== $caloriesConsumedPerDay) {
            $vars['caloriesConsumedPerDay'] = $caloriesConsumedPerDay;
        }

        $dailyCalorieDeficit = $this->get('user_stats')
            ->getDailyCalorieDeficit($date, $user);

        if (null !== $dailyCalorieDeficit) {
            $vars['dailyCalorieDeficit'] = $dailyCalorieDeficit;
        }
        }

        $profile = $this->get('fatsecret.profile')->get($user);
        $vars['profile'] = $profile;

            $lastWeight = $this->get('fatsecret.weight')
                ->calculateTrend($date, $user);

        $goalWeight = $profile['goal_weight'];

        $vars['weightToLose'] = new Mass(
                $lastWeight->toUnit('kg') - $goalWeight->toUnit('kg'),
                'kg'
                );

        if (isset($vars['dailyCalorieDeficit'])) {
            $d = new \DateTime('today', $user->getDateTimeZone());

            $days = $vars['weightToLose']->toUnit('lb') * 3500
                / $vars['dailyCalorieDeficit'];

            if ($days > 0) {
            $d->add(new \DateInterval('P'.ceil($days).'D'));
            $vars['dateReached'] = $d;
            }
            else {
                $vars['weightToLose'] = 'indeterminate';
            }
        }

        // Has the user weighed in today?
        $today = new \DateTime('today', $user->getDateTimeZone());
        if ($profile['last_weight_date'] != $today) {
            $entry = new WeightDiaryEntry;
            $entry->setDate($today);
            $vars['weigh_in_form'] = $this->createForm(
                    new WeightDiaryEntryType($user), $entry, array(
                        'action' => $this->generateUrl('weight_diary_new'),
                        ))
                ->add('submit', 'submit', array(
                            'label' => 'Weigh In',
                            ))
                ->createView();
        }

        return $vars;
    }

    private function getSteps()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $steps = array();
        $allCompleted = true;

        // 1. Are settings set?p
        $step = array(
                'objective' => 'Modify account settings',
                'url' => $this->get('router')->generate('profile_personal_details'),
                );
        if (null === $user->getPersonalDetails()) {
            $step['completed'] = false;
            $allCompleted = false;
        }
        else {
            $step['completed'] = true;
        }

        $steps[] = $step;

        // 2. Has a health plan been created?
        $step = array(
                'objective' => 'Create Your Health Plan',
                'url' => $this->generateUrl('profile_health_plan_select'),
                );
        if (null === $user->getHealthPlan()) {
            $step['completed'] = false;
            $allCompleted = false;
        }
        else {
            $step['completed'] = true;
        }

        $steps[] = $step;

        // 3. Has a weight been logged?
        $step = array(
                'objective' => 'Add your starting weight',
                'url' => $this->generateUrl('weight_diary_new'),
                );
        
$numberOfDiaryEntries = $em->getRepository('Count2HealthAppBundle:WeightDiaryEntry')
    ->getNumberOfWeightDiaryEntries($user);

if ($numberOfDiaryEntries == 0) {
    $allCompleted = false;
    $step['completed'] = false;
}
else {
    $step['completed'] = true;
}

$steps[] = $step;
        
// If all steps are completed, don't show them
        if ($allCompleted == true) {
            $steps = array();
        }

        return $steps;
    }

}
