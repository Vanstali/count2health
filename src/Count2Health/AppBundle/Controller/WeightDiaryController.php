<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;
use Count2Health\AppBundle\Form\WeightDiaryEntryType;
use Count2Health\AppBundle\Form\WeightPredictionType;

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
        $tz = $user->getDateTimeZone();

        $session = $request->getSession();

        // Get all entries for this month
if (null == $month) {
    if ($session->has('date')) {
        $month = clone $session->get('date');
        $month->modify('first day of this month');
    } else {
        $month = new \DateTime('first day of this month', $tz);
    }
} else {
    $month = new \DateTime($month, $tz);
}

        $lastMonth = clone $month;
        $lastMonth->sub(new \DateInterval('P1M'));

        $nextMonth = clone $month;
        $nextMonth->add(new \DateInterval('P1M'));

        $weights = $this->get('fatsecret.weight')->getMonth($month, $user);

        // Format entries
        $entries = array();

        foreach ($weights->day as $weight) {
            $d = $this->get('fatsecret')
                ->dateIntToDateTime($weight->date_int, $user);
            foreach ($entries as $e) {
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
                / pow($user->getPersonalDetails()->getHeight()->toUnit('m'), 2);

            $entries[] = $entry;
        }

        usort($entries, function ($a, $b) {
                if ($a['date'] < $b['date']) {
                    return 1;
                } elseif ($a['date'] > $b['date']) {
                    return -1;
                } else {
                    return 0;
                }
                });

        $dateData = array();
        $trendData = array();
        $weightData = array();

        foreach ($entries as $i => $entry) {
            $unit = $user->getPersonalDetails()->getWeightUnits();
            $trendData[] = round($entry['trend']->toUnit($unit), 1);
            $weightData[] = round($entry['weight']->toUnit($unit), 1);
            $dateData[] = $entry['date']->format('M j');
        }

        $minDate = $entries[count($entries) - 1]['date']->getTimestamp() * 1000;
        $maxDate = $entries[0]['date']->getTimestamp() * 1000;

// Get information for progress bar
$start = $user->getPersonalDetails()->getStartWeight();
        $goal = $user->getHealthPlan()->getGoalWeight();

        if (empty($entries)) {
            $trend = $start;
        } else {
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
            );
    }

    /**
     * @Route("/new.html", name="weight_diary_new")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function newAction(Request $request)
    {
        $user = $this->getUser();

        if (null === $user->getPersonalDetails()) {
            $request->getSession()->getFlashBag()->add('error',
                    'Please modify your account settings before adding '.
                    'your first weight.');

            return $this->redirectToRoute('profile_personal_details');
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(
                'Count2HealthAppBundle:WeightDiaryEntry');

        $entry = new WeightDiaryEntry();
        $entry->setUser($user);
        $entry->setDate(new \DateTime());

        if (0 == $repository->getNumberOfWeightDiaryEntries($user)) {
            $entry->setWeight($user->getPersonalDetails()->getStartWeight());
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
            );
    }

/**
 * @Route("/predict.html", name="weight_diary_predict")
 * @Template
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function predictAction()
{
    $user = $this->getUser();

    $form = $this->createForm(new WeightPredictionType($user))
        ->add('submit', 'submit', array(
                    'label' => 'Predict',
                    ));

    return array(
            'form' => $form->createView(),
            );
}

/**
 * @Route("/predict/ajax.json", name="weight_diary_predict_ajax",
 *     options={"expose": true})
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function predictAjaxAction(Request $request)
{
    $user = $this->getUser();
    $date = new \DateTime('today', $user->getDateTimeZone());

    $form = $this->createForm(new WeightPredictionType($user))
        ->add('submit', 'submit', array(
                    'label' => 'Predict',
                    ));
    $form->handleRequest($request);

    $response = array();

    $data = $form->getData();

    if (!isset($data['date']) && !isset($data['weight']) && !isset($data['bmi'])) {
        $response['status'] = 'error';
        $response['message'] = 'Either date, weight, or BMI must be '.
            'filled in.';
    } else {
        try {
            if ($data['weight']) {
                $weight = $data['weight'];
                $goalDate = $this->get('weight_predictor')
                    ->predictDate($weight, $user);
                $weight = $this->get('weight_predictor')
                    ->predictWeight($goalDate, $user);
                $bmi = $this->get('bmi_calculator')
                    ->calculateBMI($weight, $user);
            } elseif ($data['bmi']) {
                $bmi = $data['bmi'];
                $weight = $this->get('bmi_calculator')
                    ->calculateWeight($bmi, $user);
                $goalDate = $this->get('weight_predictor')
                    ->predictDate($weight, $user);
                $weight = $this->get('weight_predictor')
                    ->predictWeight($goalDate, $user);
                $bmi = $this->get('bmi_calculator')
                    ->calculateBMI($weight, $user);
            } elseif ($data['date']) {
                $goalDate = $data['date'];
                $weight = $this->get('weight_predictor')
                    ->predictWeight($goalDate, $user);
                $bmi = $this->get('bmi_calculator')
                    ->calculateBMI($weight, $user);
            } else {
                $response['status'] = 'error';
                $response['message'] = 'ONe of date, weight, or BMI must be '.
                    'entered.';
            }
        } catch (\Exception $e) {
            $response['status'] = 'error';
            $response['message'] = $e->getMessage();
        }

        if (isset($goalDate) && isset($weight) && isset($bmi)) {
            $response['status'] = 'success';

            $response['date'] = array(
                    'year' => $goalDate->format('Y'),
                    'month' => $goalDate->format('n'),
                    'day' => $goalDate->format('j'),
                    );

            $response['weight'] = number_format(round($weight->toUnit(
                            $user->getPersonalDetails()->getWeightUnits()), 2), 2);

            $response['bmi'] = number_format(round($bmi, 1), 1);
        }
    }

    $r = new Response();
    $r->headers->set('Content-Type', 'application/json');
    $r->setContent(json_encode($response));

    return $r;
}
}
