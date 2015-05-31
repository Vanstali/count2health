<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Count2Health\AppBundle\Form\FoodType;
use Count2Health\AppBundle\Form\FoodEditType;
use Count2Health\AppBundle\Entity\FoodDiaryEntry;
use Count2Health\AppBundle\FatSecret\FatSecretException;

/**
 * @Route("/diary/food")
 */
class FoodDiaryController extends Controller
{
    /**
     * @Route(".html", name="food_diary")
     * @Route("/date/{date}.html", name="food_diary_by_date")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function indexAction(Request $request, $date = null)
    {
        $user = $this->getUser();

        $session = $request->getSession();

        if (null === $date) {
            if ($session->has('date')) {
                $date = $session->get('date');
            } else {
                $date = new \DateTime('today', $user->getDateTimeZone());
            }
        } else {
            $date = new \DateTime($date, $user->getDateTimeZone());
        }

        $session->set('date', $date);

        $yesterday = clone $date;
        $yesterday->sub(new \DateInterval('P1D'));

        $tomorrow = clone $date;
        $tomorrow->add(new \DateInterval('P1D'));

        return array(
'date' => $date,
'yesterday' => $yesterday,
'tomorrow' => $tomorrow,
);
    }

/**
 * @Template()
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function showDayInfoAction(\DateTime $date)
{
    $user = $this->getUser();

    $rdi = $this->get('user_stats')->getRDI($date, $user);
    $tdee = $this->get('user_stats')->getTDEE($date, $user);
    $targetDeficit = $tdee - $rdi;

    $entries = $this->get('fatsecret.food_entries')->get($date, $user);

        // Index by meal
        $meals = array(
                'Breakfast',
                'Lunch',
                'Dinner',
                'Other',
                );

    $entryArray = array();
    $calories = 0;
    $carbohydrate = 0;
    $protein = 0;
    $fat = 0;

    foreach ($meals as $meal) {
        $thisMeal = array();
        $thisMeal['calories'] = 0;
        $thisMeal['carbohydrate'] = 0;
        $thisMeal['protein'] = 0;
        $thisMeal['fat'] = 0;

        foreach ($entries->food_entry as $entry) {
            if ($entry->meal == $meal) {
                $mealEntry = array();
                $mealEntry['food_entry_id'] = intval($entry->food_entry_id);
                $mealEntry['food_entry_description'] = "$entry->food_entry_description";
                $mealEntry['calories'] = intval($entry->calories);

                $thisMeal['calories'] += $mealEntry['calories'];
                $calories += $mealEntry['calories'];
                $thisMeal['carbohydrate'] += floatval($entry->carbohydrate);
                $carbohydrate += floatval($entry->carbohydrate);
                $thisMeal['protein'] += floatval($entry->protein);
                $protein += floatval($entry->protein);
                $thisMeal['fat'] += floatval($entry->fat);
                $fat += floatval($entry->fat);

                $thisMeal['entries'][] = $mealEntry;
            }
        }

        $entryArray[$meal] = $thisMeal;
    }

    $deficitToday = $tdee - $calories;

    return $this->render('Count2HealthAppBundle:FoodDiary:showDayInfo.html.twig',
array(
        'rdi' => $rdi,
'targetDeficit' => $targetDeficit,
'deficitToday' => $deficitToday,
        'entries' => $entryArray,
        'calories' => $calories,
        'carbohydrate' => $carbohydrate,
        'protein' => $protein,
        'fat' => $fat,
));
}

/**
 * @Route("/add/{id}.html", name="food_diary_new")
 * @Route("/add/{id}/{type}.html", name="food_diary_new_by_type")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function addFoodEntryAction($id, Request $request, $type = 'food')
{
    $user = $this->getUser();
    $session = $request->getSession();

    if ($type == 'food') {
        $food = $this->get('fatsecret.food')->get($id);
    } elseif ($type == 'recipe') {
        $food = $this->get('fatsecret.recipe')->get($id);
    }

    $form = $this->createForm(new FoodType($user, $food, $type))
                ->add('submit', 'submit', array(
                            'label' => 'Add',
                            ));
    $form->handleRequest($request);

    $data = $form->getData();
    $session->set('meal', $data['meal']);
    $session->set('date', $data['date']);

    $entryId = $this->get('fatsecret.food_entry')->create($id,
                    $data['name'],
                    $data['servings'],
                    $data['units'],
                    $data['meal'],
                    $data['date'],
                    $user);

    $entry = $this->get('fatsecret.food_entries')->get($entryId, $user);

    $em = $this->getDoctrine()->getEntityManager();

    $calories = $em
                ->getRepository('Count2HealthAppBundle:FoodDiaryEntry')
                ->findOneBy(array(
                            'date' => $data['date'],
                            'user' => $user,
                            ));

    if ($calories) {
        $calories->addCalories(intval($entry->food_entry[0]->calories));
    } else {
        $calories = new FoodDiaryEntry();
        $calories->setCalories(intval($entry->food_entry[0]->calories));
        $calories->setDate($data['date']);
        $calories->setUser($user);
        $em->persist($calories);
    }

    $em->flush();

    $this->get('memcache')->invalidateNamespace('food', $user);

    return $this->redirectToRoute('food_diary_by_date', array(
                        'date' => $data['date']->format('Y-m-d'),
                        ));
}

/**
 * @Route("/delete/{id}.html", name="food_diary_delete")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function deleteAction($id)
{
    $entry = $this->get('fatsecret.food_entries')->get($id, $this->getUser());
    $date = $this->get('fatsecret')->dateIntToDateTime(
            $entry->food_entry->date_int, $this->getUser());

    $this->get('fatsecret.food_entry')->delete($id, $this->getUser());

    $em = $this->getDoctrine()->getManager();
    $calories = $em
    ->getRepository('Count2HealthAppBundle:FoodDiaryEntry')
    ->findOneBy(array(
            'user' => $this->getUser(),
            'date' => $date,
            ));

    if ($calories) {
        $calories->subtractCalories(intval($entry->food_entry->calories));
    }

    $this->get('memcache')->flush();

    return $this->redirectToRoute('food_diary_by_date', array(
'date' => $date->format('Y-m-d'),
));
}

    /**
     * @Route("/edit/{id}.htlml", name="food_diary_edit",
     *     options={"expose":true})
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function editAction($id, Request $request)
    {
        $user = $this->getUser();

// Fetch the entry
$entry = $this->get('fatsecret.food_entries')->get($id, $user);

// Is it a food?
$food = null;

        try {
            $food = $this->get('fatsecret.food')
->get(intval($entry->food_entry->food_id));
        } catch (FatSecretException $e) {
        }

        $data = array();
        $data['entryId'] = $id;
        $data['name'] = (string) ($entry->food_entry->food_entry_name);
        $data['numberOfUnits'] = floatval($entry->food_entry->number_of_units);
        $data['meal'] = (string) ($entry->food_entry->meal);

        if (null != $food) {
            $data['servings'] = intval($entry->food_entry->serving_id);
        }

        $form = $this->createForm(new FoodEditType($food), $data);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $this->get('fatsecret.food_entry')->edit($id,
$data['name'],
$data['numberOfUnits'],
(isset($data['servings']) ? $data['servings'] : null),
$data['meal'],
$user);

            $this->get('memcache')->invalidateNamespace('food', $user);

            $date = $this->get('fatsecret')
->dateIntToDateTime($entry->food_entry->date_int, $user);

            return $this->forward('Count2HealthAppBundle:FoodDiary:showDayInfo', array(
'date' => $date->format('Y-m-d'),
));
        }

        return array(
'food' => $food,
'form' => $form->createView(),
);
    }

/**
 * @Route("/month.html", name="food_diary_monthly_log")
 * @Route("/month/{year}/{month}.html", name="food_diary_monthly_log_by_date")
 * @Template
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
public function getMonthlyLogAction(Request $request, $year = null, $month = null)
{
    $session = $request->getSession();

    $user = $this->getUser();

    if (!$year || !$month) {
        if ($session->has('date')) {
            $year = $session->get('date')->format('Y');
            $month = $session->get('date')->format('m');
        } else {
            $d = new \DateTime('today', $user->getDateTimeZone());
            $year = $d->format('Y');
            $month = $d->format('m');
        }
    }

    $date = new \DateTime(
            sprintf('%04d-%02d-01', $year, $month),
            $user->getDateTimeZone());
    $today = new \DateTime('today', $user->getDateTimeZone());

    $lastMonth = clone $date;
    $lastMonth->sub(new \DateInterval('P1M'));

    $nextMonth = clone $date;
    $nextMonth->add(new \DateInterval('P1M'));

    $entries = $this->get('fatsecret.food_entries')
        ->getMonth($date, $user);

    $days = array();
    $fudgeFactor = $this->get('user_stats')
        ->getFudgeFactor($today, $user);

    foreach ($entries->day as $entry) {
        $thisDate = $this->get('fatsecret')
            ->dateIntToDateTime($entry->date_int, $user);
        $day = array();
        $day['date'] = $thisDate;
        $day['calories'] = intval($entry->calories);
        $day['tdee'] = $this->get('user_stats')
            ->getTDEE($thisDate, $user, $fudgeFactor);
        $day['deficit'] = $day['tdee'] - $day['calories'];
        $day['carbohydrate'] = floatval($entry->carbohydrate);
        $day['carbohydrate_percent'] = $day['carbohydrate'] * 4 / $day['calories'];
        $day['protein'] = floatval($entry->protein);
        $day['protein_percent'] = $day['protein'] * 4 / $day['calories'];
        $day['fat'] = floatval($entry->fat);
        $day['fat_percent'] = $day['fat'] * 9 / $day['calories'];

        array_unshift($days, $day);
    }

    return array(
            'days' => $days,
            'date' => $date,
            'lastMonth' => $lastMonth,
            'nextMonth' => $nextMonth,
            );
}
}
