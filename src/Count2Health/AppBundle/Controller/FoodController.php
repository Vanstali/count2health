<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Count2Health\AppBundle\Form\FoodSearchType;
use Count2Health\AppBundle\Form\FoodType;

/**
 * @Route("/food")
 */
class FoodController extends Controller
{
    /**
     * @Route("/search.html", name="food_search")
     * @Template()
     */
    public function searchAction()
    {
        $form = $this->createForm(new FoodSearchType())
            ->add('submit', 'submit', array(
                        'label' => 'Search',
                        ));

        return array(
                'form' => $form->createView(),
                );    }

        /**
         * @Route("/search/ajax.html", name="food_search_ajax",
         *     options={"expose": true})
         * @Template()
         */
        public function doSearchAction(Request $request)
        {
            if (!$request->isXmlHttpRequest()) {
                throw $this->createAccessDeniedException(
                        'Request must be an ajax request.');
            }

            $page = 0;
            if ($request->request->has('page')) {
                $page = intval($request->request->get('page'));
            }

            $results = $this->get('fatsecret.foods')->search(
                    $request->request->get('search'),
                    $page);

            $totalPages = floor($results->total_results / $results->max_results);

            return array(
                    'results' => $results,
                    'total_pages' => $totalPages,
                    );
        }

        /**
         * @Route("/view/{id}.html", name="food_view")
         * @Template();
         */
        public function showAction($id, Request $request)
        {
            $user = $this->getUser();
$session = $request->getSession();

if ($session->has('date')) {
$date = $session->get('date');
}
else {
$date = new \DateTime('today', $user->getDateTimeZone());
}

            $food = $this->get('fatsecret.food')->get($id);

            $data = array();
            $data['units'] = floatval($food->servings->serving[0]->number_of_units);
            $data['date'] = $date;
            $data['name'] = "$food->food_name";
if ($request->getSession()->has('meal')) {
$data['meal'] = $request->getSession()->get('meal');
}

            $form = $this->createForm(new FoodType($user, $food), $data, array(
                        'action' => $this->generateUrl('food_diary_new', array('id' => $id)),
                        ))
                ->add('submit', 'submit', array(
                            'label' => 'Add',
                            ));

            $servings = array();

            foreach ($food->servings->serving as $s)
            {
                $serving = array();
                foreach ($s->children() as $child)
                {
                    $serving[$child->getName()] = "$child";
                }

                $servings[] = $serving;
            }

            return array(
                    'food' => $food,
                    'servings' => json_encode($servings),
                    'form' => $form->createView(),
                    );
        }

        /**
         * @Route("/search/most-eaten.html", name="food_most_eaten")
         * @Route("/search/most-eaten/{meal}.html", name="food_most_eaten_by_meal")
         * @Template()
         * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
         */
        public function mostEatenAction($meal = null)
        {
            $user = $this->getUser();

            $foods = $this->get('fatsecret.foods')->getMostEaten($meal, $user);

            return array(
                    'results' => $foods,
                    'meal' => $meal,
                    );
        }

        /**
         * @Route("/search/recently-eaten.html", name="food_recently_eaten")
         * @Route("/search/recently-eaten/{meal}.html", name="food_recently_eaten_by_meal")
         * @Template()
         * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
         */
        public function recentlyEatenAction($meal = null)
        {
            $user = $this->getUser();

            $foods = $this->get('fatsecret.foods')->getRecentlyEaten($meal, $user);

            return array(
                    'results' => $foods,
                    'meal' => $meal,
                    );
        }

}
