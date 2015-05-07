<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;
use Count2Health\AppBundle\Form\WeightDiaryEntryType;

/**
 * @Route("/diary/weight")
 */
class WeightDiaryController extends Controller
{
    /**
     * @Route(".html", name="weight_diary")
     * @Route("/date/{month}.html", name="weight_diary_by_date")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function indexAction(Request $request, $month = null)
    {
        $user = $this->getUser();
$tz = new \DateTimeZone($user->getSetting()->getTimeZone());

$session = $request->getSession();

        // Get all entries for this month
if (null == $month) {
if ($session->has('date')) {
$month = clone $session->get('date');
$month->modify('first day of this month');
}
else {
        $month = new \DateTime('first day of this month', $tz);
}
}
else {
$month = new \DateTime($month, $tz);
}

$lastMonth = clone $month;
$lastMonth->sub(new \DateInterval('P1M'));

$nextMonth = clone $month;
$nextMonth->add(new \DateInterval('P1M'));

        $weights = $this->get('fatsecret.weight')->getMonth($month, $user);

        // Format entries
        $entries = array();

        foreach ($weights->day as $weight)
        {
            $d = $this->get('fatsecret')
                ->dateIntToDateTime($weight->date_int, $user);
            foreach ($entries as $e)
            {
                if ($d == $e['date']) {
                    continue 2;
                }
            }

            $entry = array();

            $entry['date'] = $this->get('fatsecret')
                ->dateIntToDateTime($weight->date_int, $user);
            $entry['weight'] = new Mass("$weight->weight_kg", 'kg');
            $entry['trend'] = $this->get('fatsecret.weight')
                ->calculateTrend($entry['date'], $user);
            $entry['comment'] = "$weight->weight_comment";
            $entry['BMI'] = $entry['trend']->toUnit('kg')
                / pow($user->getSetting()->getHeight()->toUnit('m'), 2);

            $entries[] = $entry;
        }

        usort($entries, function ($a, $b)
                {
                if ($a['date'] < $b['date']) {
                return 1;
                }
                elseif ($a['date'] > $b['date']) {
                return -1;
                }
                else {
                return 0;
                }
                });

$dateData = array();
$trendData = array();
$weightData = array();

foreach ($entries as $i => $entry)
{
    $unit = $user->getSetting()->getWeightUnits();
    $trendData[] = round($entry['trend']->toUnit($unit), 1);
    $weightData[] = round($entry['weight']->toUnit($unit), 1);
    $dateData[] = $entry['date']->format('M j');
}

$minDate = $entries[count($entries)-1]['date']->getTimestamp() * 1000;
$maxDate = $entries[0]['date']->getTimestamp() * 1000;

// Get information for progress bar
$start = $user->getSetting()->getStartWeight();
$goal = $user->getHealthPlan()->getGoalWeight();

if (empty($entries)) {
    $trend = $start;
}
else {
    $trend = $entries[0]['trend'];
}

$totalWeightToLose = new Mass(
        $start->toUnit('kg') - $goal->toUnit('kg'),
        'kg');
$weightLost = new Mass(
        $start->toUnit('kg') - $trend->toUnit('kg'),
        'kg');
$weightToLose = new Mass(
        $trend->toUnit('kg') - $goal->toUnit('kg'),
        'kg');

        return array(
                'entries' => $entries,
                'month' => $month,
'lastMonth' => $lastMonth,
'nextMonth' => $nextMonth,
                'dateJson' => json_encode(array_reverse($dateData)),
                'trendJson' => json_encode(array_reverse($trendData)),
                'weightJson' => json_encode(array_reverse($weightData)),
                'minDate' => $minDate,
                'maxDate' => $maxDate,
                'totalWeightToLose' => $totalWeightToLose,
                'weightLost' => $weightLost,
                'weightToLose' => $weightToLose,
            );    }

    /**
     * @Route("/new.html", name="weight_diary_new")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function newAction(Request $request)
    {
        $user = $this->getUser();

        if (null === $user->getSetting()) {
            $request->getSession()->getFlashBag()->add('error',
                    'Please modify your account settings before adding ' .
                    'your first weight.');
            return $this->redirectToRoute('account_settings');
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(
                'Count2HealthAppBundle:WeightDiaryEntry');

        $entry = new WeightDiaryEntry;
        $entry->setUser($user);
        $entry->setDate(new \DateTime());

        if (0 == $repository->getNumberOfWeightDiaryEntries($user)) {
            $entry->setWeight($user->getSetting()->getStartWeight());
        }

        $form = $this->createForm(new WeightDiaryEntryType($user), $entry)
            ->add('submit', 'submit', array(
                        'label' => 'Weigh In',
                        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entry);
            $em->flush();

            $this->get('memcache')->invalidateNamespace('weight', $user);

            $request->getSession()->getFlashBag()
                ->add('success', 'Your weight has been added.');
            return $this->redirectToRoute('weight_diary');
        }

        return array(
                'form' => $form->createView(),
            );    }

}
