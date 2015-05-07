<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Form\ExerciseType;
use Count2Health\AppBundle\Entity\ActivityCategory;
use Count2Health\AppBundle\Entity\Activity;
use Count2Health\AppBundle\Form\ActivitiesType;

/**
 * @Route("/activities")
 */
class ActivitiesController extends Controller
{
    /**
     * @Route("/browse.html", name="activities_browse")
     * @Route("/browse/{category}.html", name="activities_browse_by_category")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     */
    public function browseAction(ActivityCategory $category = null)
    {
$em = $this->getDoctrine()->getManager();
if (null == $category) {
$categories = $em
->getRepository('Count2HealthAppBundle:ActivityCategory')
->findAll();

return array(
'categories' => $categories,
);
}
else {
$activities = $em
->getRepository('Count2HealthAppBundle:Activity')
->findByCategory($category);

return array(
'activities' => $activities,
'category' => $category,
);
}
            }

    /**
     * @Route("/view/{activity}.html", name="activities_view")
     * @Template()
     */
    public function showAction(Activity $activity, Request $request)
    {
$em = $this->getDoctrine()->getManager();

$vars = array(
'activity' => $activity,
);

if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
$user = $this->getUser();

$session = $request->getSession();

if ($session->has('date')) {
$date = $session->get('date');
}
else {
$this->addFlash('error', 'Please select a date.');
return $this->redirectToRoute('activities_diary');
}

$exerciseEntries = $this->get('fatsecret.exercise_entries')->get($date, $user);
$exercises = array();

foreach ($exerciseEntries->exercise_entry as $entry)
{
$exercise = array();
$exercise['minutes'] = intval($entry->minutes);

$id = intval($entry->exercise_id);
$name = "$entry->exercise_name";

$parsedName = $this->get('activity_name_parser')->parse($id, $name);

if ($parsedName instanceof Activity) {
if (0 == $id) {
$exercise['id'] = "$parsedName";
}
else {
$exercise['id'] = $id;
}

$exercise['name'] = "$parsedName";
}
else {
$exercise['id'] = $id;
$exercise['name'] = $name;
}

$exercises[] = $exercise;
}

$form = $this->createForm(new ActivitiesType($exercises))
->add('submit', 'submit', array(
'label' => 'Add',
));

$vars['form'] = $form->createView();

$prevEntries = $this->get('fatsecret.weight')
->getEntries($date, $user, 1, true);

if (empty($prevEntries)) {
$weight = $user->getSetting()->getStartWeight();
}
else {
$weight = new Mass(floatval($prevEntries[0]->weight_kg), 'kg');
}

$form->handleRequest($request);

if ($form->isValid()) {
$data = $form->getData();
$minutes = $data['time']['hour'] * 60 + $data['time']['minute'];

if (null == $activity->getFatsecretEntryId()) {
$calories = $activity->getCaloriesBurned($weight, $minutes);

// Add existing calories
foreach ($exerciseEntries->exercise_entry as $exercise)
{
if ("$exercise->exercise_name" == "$activity"
|| "$exercise->exercise_id" == $activity->getFatsecretEntryId()) {
$calories += intval($exercise->calories);
}
}

$toId = 0;
$toName = "$activity";
}
else {
$calories = 0;
$toId = $activity->getFatsecretEntryId();
$toName = null;
}

if (is_numeric($data['from'])) {
$fromId = $data['from'];
$fromName = null;
}
else {
$fromId = 0;
$fromName = $data['from'];
}

if (0 == $fromId) {
$fromNameParts = explode(' > ', $fromName);
$fromActivity = $em
->getRepository('Count2HealthAppBundle:Activity')
->findOneByName($fromNameParts[1]);
$fromMinutes = 0;

foreach ($exerciseEntries->exercise_entry as $exercise)
{
if ("$exercise->exercise_name" == "$fromActivity"
|| "$exercise->exercise_id" == $fromActivity->getFatsecretEntryId()) {
$fromMinutes = intval($exercise->minutes);
}
}

$fromMinutes -= $minutes;
$fromCalories = $fromActivity->getCaloriesBurned($weight, $fromMinutes);
}

if (0 == $toId && 0 == $fromId) {
// Move minutes to rest first, because of bug in API
$this->get('fatsecret.exercise_entry')
->edit($date, 2, null, $fromId, $fromName, $fromMinutes + $minutes, null, $user);
$this->get('fatsecret.exercise_entry')
->edit($date, $toId, $toName, 2, null, $minutes, $calories, $user);

if ($fromMinutes > 0) {
$this->get('fatsecret.exercise_entry')
->edit($date, $fromId, $fromName, 2, null, $fromMinutes, $fromCalories, $user);
}
}
elseif (0 == $fromId) {
$this->get('fatsecret.exercise_entry')
->edit($date, $toId, null, $fromId, $fromName, $fromMinutes + $minutes, null, $user);
$this->get('fatsecret.exercise_entry')
->edit($date, $fromId, $fromName, $toId, null, $fromMinutes, $fromCalories, $user);
}
else {
$this->get('fatsecret.exercise_entry')
->edit($date, $toId, $toName, $fromId, $fromName, $minutes, $calories, $user);
}

$this->get('memcache')->invalidateNamespace('exercise', $user);

$this->addFlash('success', 'The activity has been added to your diary.');

return $this->redirectToRoute('activities_diary');
}

}
else {
$weight = new Mass('150', 'lb');
}

$vars['weight'] = $weight;

return $vars;
    }

}
