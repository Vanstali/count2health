<?php

namespace Count2Health\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use FOS\UserBundle\Model\UserInterface;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\UserBundle\Entity\PersonalDetails;
use Count2Health\UserBundle\Form\PersonalDetailsType;
use Count2Health\UserBundle\Form\HealthPlanType;

class ProfileController extends BaseController
{
    public function showAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('FOSUserBundle:Profile:show.html.twig', array(
                    'user' => $user,
                    ));
    }

    /**
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editPersonalDetailsAction(Request $request)
    {
        $user = $this->getUser();

        $personal = $user->getPersonalDetails();
        if (null === $personal) {
            $personal = new PersonalDetails();
            $personal->setUser($user);
            $personal->setStartDate(
                    new \DateTime('today', $user->getDateTimeZone()));
            $personal->setBirthDate(
                    new \DateTime('today', $user->getDateTimeZone()));
            $user->setPersonalDetails($personal);
        }

        $form = $this->createForm(new PersonalDetailsType(), $personal)
        ->add('submit', 'submit', array('label' => 'Modify'));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($personal);
            $em->flush();

            $this->addFlash('success',
                    'Your personal details have successfully been modified.');

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return array(
                'form' => $form->createView(),
                );
    }

    /**
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function selectHealthPlanTypeAction(Request $request)
    {
        $user = $this->getUser();

        if (null === $user->getPersonalDetails()) {
            $request->getSession()->getFlashBag()->add('error',
                    'You must modify your account settings before '.
                    'setting your health profile.');

            return $this->redirectToRoute('profile_personal_details');
        }

        return array(
                );
    }

    /**
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editHealthPlanAction($type, Request $request)
    {
        $user = $this->getUser();
        $date = new \DateTime('today', $user->getDateTimeZone());

        if (null === $user->getPersonalDetails()) {
            $request->getFlashBag()->add('error',
                    'Please modify your account settings before setting '.
                    'your health plan.');

            return $this->redirectToRoute('profile_personal_details');
        }

        $vars = array();
        $vars['type'] = $type;

        if (null == $user->getHealthPlan()
                || $type != $user->getHealthPlan()->getType()) {
            $healthPlan = new HealthPlan();
            $healthPlan->setUser($user);
            $healthPlan->setType($type);

            if ($type == 'maintenance') {
                $healthPlan->setGoalWeight($user->getPersonalDetails()->getStartWeight());
            } else {
                $healthPlan->setGoalDate(new \DateTime());
            }
        } else {
            $healthPlan = $user->getHealthPlan();
        }

        $form = $this->createForm(new HealthPlanType($user, $type), $healthPlan)
            ->add('submit', 'submit', array(
                        'label' => 'Submit',
                        ));
        $form->handleRequest($request);

        $vars['form'] = $form->createView();

        $tdee = $this->get('user_stats')->getEstimatedTDEE($date, $user);
        $vars['tdee'] = $tdee;

        // Create some calorie deficit presets
        if ($type != 'maintenance') {
            $presets = array();

            $options = array();
            switch ($user->getPersonalDetails()->getWeightUnits()) {
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
                if ($user->getPersonalDetails()->getGender() == 'male') {
                    $minimum = 1500;
                } elseif ($user->getPersonalDetails()->getGender() == 'female') {
                    $minimum = 1200;
                }
            }

            foreach ($options as $calories => $weight) {
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

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $vars;
    }

    /**
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function getRecommendedWeightAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
        }

        $user = $this->getUser();

        $height = $user->getPersonalDetails()->getHeight()->toUnit('in');
        $gender = $user->getPersonalDetails()->getGender();

        if ('male' == $gender) {
            $weight = 52;

            if ($height > 60) {
                $weight += 1.9 * floor($height - 60);
            }
        } elseif ('female' == $gender) {
            $weight = 49;

            if ($height > 60) {
                $weight += 1.7 * floor($height - 60);
            }
        }

        $weight = new Mass($weight, 'kg');

        return new Response(round($weight->toUnit($user->getPersonalDetails()->getWeightUnits()), 1));
    }

/**
 * Calculate the goal date based on target calorie deficit/excess.
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateGoalDateAction(Request $request)
{
    if (!$request->isXmlHttpRequest()) {
        throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
    }

    $user = $this->getUser();
    $date = new \DateTime('today', $user->getDateTimeZone());

    $result = array();

    $calories = $request->request->get('calories');
    $tdee = $this->get('user_stats')->getEstimatedTDEE($date, $user);
    $consumed = $tdee - $calories;

    if ($request->request->get('goalWeight')) {
        if ($request->request->get('type') == 'loss'
        && (('male' == $user->getPersonalDetails()->getGender()
            && $consumed < 1500)
            || ('female' == $user->getPersonalDetails()->getGender()
                && $consumed < 1200))) {
            $result['status'] = 'error';
            $result['error'] = 'This is an unhealthy rate of weight loss.';
        } else {
            $goalWeight = new Mass($request->request->get('goalWeight'),
        $user->getPersonalDetails()->getWeightUnits());

// Does the user have any weight entries?
$prevEntries = $this->get('fatsecret.weight')
->getEntries($date, $user, 1, true);

            if (empty($prevEntries)) {
                $weight = $user->getPersonalDetails()->getStartWeight();
            } else {
                $weight = new Mass(floatval($prevEntries[0]->weight_kg), 'kg');
            }

            $weightToLose = $weight->subtract($goalWeight);

            $days = $weightToLose->toUnit('lb')
* 3500 / $calories;

            $date->add(new \DateInterval('P'.floor($days).'D'));

            $result['status'] = 'success';
            $result['year'] = $date->format('Y');
            $result['month'] = $date->format('n');
            $result['day'] = $date->format('j');
        }
    } else {
        $result['status'] = 'error';
        $result['error'] = 'Please enter a goal weight.';
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($result));

    return $response;
}

/**
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateTargetCaloriesAction(Request $request)
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
                $user->getPersonalDetails()->getWeightUnits());
        $goalDate = new \DateTime(
                    sprintf('%4d-%02d-%02d',
                        $requestDate['year'], $requestDate['month'], $requestDate['day']
                        ), $user->getDateTimeZone());

        $today = clone $user->getPersonalDetails()->getStartDate();
        $interval = $goalDate->diff($today);

        $days = $interval->days;

        $weight = $user->getPersonalDetails()->getStartWeight();
        $weightToLose = $weight->subtract($goalWeight)->toUnit('lb');
        $weightToLose = abs($weightToLose);

        $calories = round($weightToLose / $days * 3500);
        $tdee = $this->get('user_stats')->getEstimatedTDEE($today, $user);
        $consumed = $tdee - $calories;

        if ($request->request->get('type') == 'loss'
        && (('male' == $user->getPersonalDetails()->getGender()
            && $consumed < 1500)
            || ('female' == $user->getPersonalDetails()->getGender()
                && $consumed < 1200))) {
            $result['status'] = 'error';
            $result['error'] = 'This rate of weight loss is unhealthy. Please '.
        'choose a later date.';
        } else {
            $result['status'] = 'success';
            $result['calories'] = $calories;
        }
    } else {
        $result['status'] = 'error';
        $result['error'] = 'Please enter a goal weight.';
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($result));

    return $response;
}

/**
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateBmiAction(Request $request)
{
    if (!$request->isXmlHttpRequest()) {
        throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
    }

    $user = $this->getUser();
    $weight = new Mass($request->request->get('weight'),
                $user->getPersonalDetails()->getWeightUnits());

    $bmi = round(
                    $weight->toUnit('kg')
                    / pow($user->getPersonalDetails()->getHeight()->toUnit('m'), 2),
                1);

    if ($bmi < 18.5) {
        $categorization = 'underweight';
    } elseif ($bmi < 25) {
        $categorization = 'healthy';
    } elseif ($bmi < 30) {
        $categorization = 'overweight';
    } elseif ($bmi < 40) {
        $categorization = 'obese';
    } else {
        $categorization = 'morbidly obese';
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode(array(
                        'bmi' => $bmi,
                        'categorization' => $categorization,
                        )));

    return $response;
}

/**
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function calculateCalorieDeficitFromWeightRateAction(Request $request)
{
    if (!$request->isXmlHttpRequest()) {
        throw $this->createAccessDeniedException(
                    'Request must be an ajax request.');
    }

    $user = $this->getUser();
    $date = new \DateTime('today', $user->getDateTimeZone());

    $weight = new Mass($request->request->get('weight'),
        $user->getPersonalDetails()->getWeightUnits());

    $calories = $weight->toUnit('lb') / 7.0 * 3500;

    $result = array();

    if ($request->request->get('type') == 'loss') {
        $consumed = $this->get('user_stats')->getEstimatedTDEE($date, $user)
        - $calories;

        if (($user->getPersonalDetails()->getGender() == 'male' && $consumed < 1500)
        || ($user->getPersonalDetails()->getGender() == 'female' && $consumed < 1200)) {
            $result['status'] = 'error';
        } else {
            $result['status'] = 'success';
            $result['calories'] = $calories;
        }
    } else {
        $result['status'] = 'success';
        $result['calories'] = $calories;
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($result));

    return $response;
}

/**
 * @Template()
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function connectAction(Request $request)
{
    return array();
}

/**
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function connectConfirmAction()
{
    $user = $this->getUser();

    $response = $this->get('fatsecret')->getRequestToken(
        $this->generateUrl('profile_connected', array(), true)
        );
    var_dump($response);

    $token = $response['oauth_token'];
    $secret = $response['oauth_token_secret'];

    $em = $this->getDoctrine()->getEntityManager();
    $user->setRequestToken($token);
    $user->setRequestSecret($secret);
    $em->flush();

    $url = 'http://www.fatsecret.com/oauth/authorize?'.
    http_build_query(array(
                'oauth_token' => $token,
                ));

    return $this->redirect($url);
}

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

        return $this->redirectToRoute('fos_user_profile_show');
    }
}
