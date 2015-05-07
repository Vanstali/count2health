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

$date = new \DateTime('today',
new \DateTimeZone($user->getSetting()->getTimeZone()));

        $steps = $this->getSteps();

        $vars = array(
                'user' => $user,
                'steps' => $steps,
            );

        if (null !== $user->getSetting()) {
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

        $lastWeight = $profile['last_weight'];
        if (!$lastWeight) {
            $lastWeight = $user->getSetting()->getStartWeight();
        }

        $goalWeight = $profile['goal_weight'];

        $vars['weightToLose'] = new Mass(
                $lastWeight->toUnit('kg') - $goalWeight->toUnit('kg'),
                'kg'
                );

        if (isset($vars['dailyCalorieDeficit'])) {
            $d = new \DateTime('today',
                    new \DateTimeZone($user->getSetting()->getTimeZone()));

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
        $today = new \DateTime('today', new \DateTimeZone($user->getSetting()->getTimeZone()));
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

    /**
     * @Route("/settings.html", name="account_settings",
     *     options={"expose":true})
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editSettingsAction(Request $request)
    {
        $user = $this->getUser();

        $setting = $user->getSetting();
        if ($setting === null) {
            $setting = new Setting;
            $setting->setUser($user);
            $setting->setBirthDate(new \DateTime());
            $user->setSetting($setting);
        }

        $form = $this->createForm(new SettingType(), $setting)
        ->add('submit', 'submit', array('label' => 'Modify'));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($setting);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success',
                    'Your account settings have successfully been modified.');
            return $this->redirectToRoute('account');
        }

        return array(
                'form' => $form->createView(),
                );
    }

    /**
     * @Route("/health-plan/select-plan.html", name="account_health_plan_select",
     *     options={"expose": true})
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function selectHealthPlanTypeAction(Request $request)
    {
        $user = $this->getUser();

        if (null === $user->getSetting()) {
            $request->getSession()->getFlashBag()->add('error',
                    'You must modify your account settings before ' .
                    'setting your health profile.');
            return $this->redirectToRoute("account_settings");
        }

        return array(
                );
    }

    /**
     * @Route("/health-plan/{type}.html", name="account_health_plan",
     *     options={"expose": true})
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editHealthPlanAction($type, Request $request)
    {
        $user = $this->getUser();

        if (null === $user->getSetting()) {
            $request->getFlashBag()->add('error',
                    'Please modify your account settings before setting ' .
                    'your health plan.');
            return $this->redirectToRoute('account_settings');
        }

        $vars = array();
        $vars['type'] = $type;

        $healthPlan = new HealthPlan;
        $healthPlan->setUser($user);
        $healthPlan->setType($type);

        if ($type == 'maintenance') {
            $healthPlan->setGoalWeight($user->getSetting()->getStartWeight());
        }
        else {
        $healthPlan->setGoalDate(new \DateTime());
        }

        $form = $this->createForm(new HealthPlanType($user, $type), $healthPlan)
            ->add('submit', 'submit', array(
                        'label' => 'Submit',
                        ));
        $form->handleRequest($request);

        $vars['form'] = $form->createView();

        $tdee = $this->get('user_stats')->getEstimatedTDEE();
        $vars['tdee'] = $tdee;

        // Create some calorie deficit presets
        if ($type != 'maintenance') {
            $presets = array();

            $options = array();
                switch ($user->getSetting()->getWeightUnits())
                {
                    case 'lb':
                        $options = array(
                                250 => new Mass('0.5', 'lb'),
                                500 => new Mass('1', 'lb'),
                                750 => new Mass('1.5', 'lb'),
                                1000 => new Mass('2.0', 'lb'),
                                );
                        break;

                    case 'kg':
                        $options = array(
275 => new Mass('0.25', 'kg'),
550 => new Mass('0.5', 'kg'),
825 => new Mass('0.75', 'kg'),
1100 => new Mass('1', 'kg'),
                                );
                        break;
                }

            if ($type == 'loss') {
                if ($user->getSetting()->getGender() == 'male') {
                    $minimum = 1500;
                }
                elseif ($user->getSetting()->getGender() == 'female') {
                    $minimum = 1200;
                }
            }

foreach ($options as $calories => $weight)
{
    if ($type == 'loss'
            && ($tdee - $calories) < $minimum) {
        continue;
    }
    $presets[] = array(
            'calories' => $calories,
            'weight' => $weight,
            );
}

$vars['presets'] = $presets;
        }

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($healthPlan);
            $em->flush();

            return $this->redirectToRoute('account');
        }

return $vars;
    }

    /**
     * @Route("/account/get-recommended-weight.html", name="account_get_recommended_weight",
     *     options={"expose": true})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function getRecommendedWeight(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();

$height = $user->getSetting()->getHeight()->toUnit('in');
$gender = $user->getSetting()->getGender();

if ('male' == $gender) {
    $weight = 52;

    if ($height > 60) {
        $weight += 1.9 * floor($height - 60);
    }
}
elseif ('female' == $gender) {
    $weight = 49;

    if ($height > 60) {
        $weight += 1.7 * floor($height - 60);
    }
}

    $weight = new Mass($weight, 'kg');
    return new Response(round($weight->toUnit($user->getSetting()->getWeightUnits()), 1));
    }

/**
 * Calculate the goal date based on target calorie deficit/excess.
 *
 * @Route("/calculate-goal-date.html",
 *     name="account_calculate_goal_date",
 *     options={"expose": true})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateGoalDate(Request $request)
{
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();
        $result = array();

$calories = $request->request->get('calories');
$tdee = $this->get('user_stats')->getEstimatedTDEE();
$consumed = $tdee - $calories;

if ($request->request->get('goalWeight')) {
if ($request->request->get('type') == 'loss'
        && (('male' == $user->getSetting()->getGender()
            && $consumed < 1500)
            || ('female' == $user->getSetting()->getGender()
                && $consumed < 1200))) {
    $result['status'] = 'error';
    $result['error'] = 'This is an unhealthy rate of weight loss.';
}
else {
$goalWeight = new Mass($request->request->get('goalWeight'),
        $user->getSetting()->getWeightUnits());

// Does the user have any weight entries?
$weight = $user->getLastWeight();

if (!$weight) {
    $weight = $user->getSetting()->getStartWeight();
}

$weightToLose = $weight->subtract($goalWeight);

$days = $weightToLose->toUnit('lb')
* 3500 / $calories;

$date = new \DateTime();
$date->add(new \DateInterval('P' . floor($days) . 'D'));

$result['status'] = 'success';
                $result['year'] = $date->format('Y');
                $result['month'] = $date->format('n');
                $result['day'] = $date->format('j');
                }
}
else {
    $result['status'] = 'error';
    $result['error'] = 'Please enter a goal weight.';
}

$response = new Response;
$response->headers->set('Content-Type', 'application/json');
$response->setContent(json_encode($result));

return $response;
}

/**
 * @Route("/calculate-target-calories.html",
 *     name="account_calculate_target_calories",
 *     options={"expose": true})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateTargetCalories(Request $request)
{
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();
        $result = array();

        $requestDate = $request->request->get('goalDate');

        if ($request->request->get('goalWeight')) {
        $goalWeight = new Mass($request->request->get('goalWeight'),
                $user->getSetting()->getWeightUnits());
                $goalDate = new \DateTime(
                    sprintf('%4d-%02d-%02d',
                        $requestDate['year'], $requestDate['month'], $requestDate['day']
                        ));

                    $today = new \DateTime('today');
                    $interval = $goalDate->diff($today);

$days = $interval->days;

// Is there a weight for today?
$criteria = Criteria::create();
$criteria->where($criteria->expr()->eq(
            'date', new \DateTime()));
$weights = $user->getWeightDiary()
    ->matching($criteria);

if ($weights->isEmpty()) {
    $weight = $user->getSetting()->getStartWeight();
}
else {
    $weight = $weights[0]->getWeight();
}

$weightToLose = $weight->subtract($goalWeight)->toUnit('lb');

$weightToLose = abs($weightToLose);

$calories = round($weightToLose / $days * 3500);
$tdee = $this->get('user_stats')->getEstimatedTDEE();
$consumed = $tdee - $calories;

if ($request->request->get('type') == 'loss'
        && (('male' == $user->getSetting()->getGender()
            && $consumed < 1500)
            || ('female' == $user->getSetting()->getGender()
                && $consumed < 1200))) {
    $result['status'] = 'error';
    $result['error'] = 'This rate of weight loss is unhealthy. Please ' .
        'choose a later date.';
}
else {
    $result['status'] = 'success';
    $result['calories'] = $calories;
}
}
else {
    $result['status'] = 'error';
    $result['error'] = 'Please enter a goal weight.';
}

$response = new Response;
$response->headers->set('Content-Type', 'application/json');
$response->setContent(json_encode($result));

return $response;
}

/**
 * @Route("/calculate-bmi.html", name="account_calculate_bmi",
 *     options={"expose": true})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateBmi(Request $request)
{
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();
        $weight = new Mass($request->request->get('weight'),
                $user->getSetting()->getWeightUnits());

        $bmi = round(
                    $weight->toUnit('kg')
                    / pow($user->getSetting()->getHeight()->toUnit('m'), 2),
                1);

        if ($bmi < 18.5) {
            $categorization = 'underweight';
        }
        elseif ($bmi < 25) {
            $categorization = 'healthy';
        }
        elseif ($bmi < 30) {
            $categorization = 'overweight';
        }
        elseif ($bmi < 40) {
            $categorization = 'obese';
        }
        else {
            $categorization = 'morbidly obese';
        }

        $response = new Response;
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(array(
                        'bmi' => $bmi,
                        'categorization' => $categorization,
                        )));

        return $response;
}

/**
 * @Route("/calculate-calorie-deficit-from-weight-rate.html",
 *     name="account_calculate_calorie_deficit_from_weight_rate",
 *     options={"expose": true})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateCalorieDeficitFromWeightRate(Request $request)
{
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();

$weight = new Mass($request->request->get('weight'),
        $user->getSetting()->getWeightUnits());

$calories = $weight->toUnit('lb') / 7.0 * 3500;

$result = array();

if ($request->request->get('type') == 'loss') {
    $consumed = $this->get('user_stats')->getEstimatedTDEE() - $calories;

if (($user->getSetting()->getGender() == 'male' && $consumed < 1500)
        || ($user->getSetting()->getGender() == 'female' && $consumed < 1200)) {
    $result['status'] = 'error';
}
else {
    $result['status'] = 'success';
    $result['calories'] = $calories;
}
}
else {
    $result['status'] = 'success';
    $result['calories'] = $calories;
}

$response = new Response;
$response->headers->set('Content-Type', 'application/json');
$response->setContent(json_encode($result));

return $response;
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
                'url' => $this->get('router')->generate('account_settings'),
                );
        if (null === $user->getSetting()) {
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
                'url' => $this->generateUrl('account_health_plan_select'),
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

/**
 * @Route("/connect.html", name="account_connect")
 * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function connectAction(Request $request)
{
    return array();
}

/**
 * @Route("/connect/confirm.html", name="account_connect_confirm")
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function connectConfirmAction()
{
    $user = $this->getUser();

$response = $this->get('fatsecret')->getRequestToken(
        $this->generateUrl('account_connected', array(), true)
        );
var_dump($response);

$token = $response['oauth_token'];
$secret = $response['oauth_token_secret'];

$em = $this->getDoctrine()->getEntityManager();
$user->setRequestToken($token);
$user->setRequestSecret($secret);
$em->flush();

$url = 'http://www.fatsecret.com/oauth/authorize?' .
    http_build_query(array(
                'oauth_token' => $token,
                ));

return $this->redirect($url);
}

/**
 * @Route("/connected.html", name="account_connected")
 */
public function connectedAction(Request $request)
{
$em = $this->getDoctrine()->getManager();
$token = $request->query->get('oauth_token');
$verifier = $request->query->get('oauth_verifier');

$user = $em
    ->getRepository('Count2HealthUserBundle:User')
    ->findOneByRequestToken($token);

$response = $this->get('fatsecret')->getAccessToken($user, $verifier);

$user->setAuthToken($response['oauth_token']);
$user->setAuthSecret($response['oauth_token_secret']);
$user->setConnected(true);
$em->flush();

$request->getSession()->getFlashBag()->add('success',
        'Your account has been connected to FatSecret.');

return $this->redirectToRoute('account');
}

}
