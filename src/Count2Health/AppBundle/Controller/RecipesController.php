<?php

namespace Count2Health\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Count2Health\AppBundle\Form\RecipeSearchType;
use Count2Health\AppBundle\Form\FoodType;

/**
 * @Route("/recipes")
 */
class RecipesController extends Controller
{
    /**
     * @Route("/search.html", name="recipes_search")
     * @Template()
     */
    public function searchAction()
    {
        $form = $this->createForm(new RecipeSearchType())
            ->add('submit', 'submit', array(
                        'label' => 'Search',
                        ));

        return array(
                'form' => $form->createView(),
                );
    }

        /**
         * @Route("/search/ajax.html", name="recipes_search_ajax",
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

            $results = $this->get('fatsecret.recipes')->search(
                    $request->request->get('search'),
                    $page);

            $totalPages = floor($results->total_results / $results->max_results);

            return array(
                    'results' => $results,
                    'total_pages' => $totalPages,
                    );
        }

    /**
     * @Route("/show/{id}.html", name="recipe_view")
     * @Template()
     */
    public function showAction($id)
    {
        $user = $this->getUser();

        $recipe = $this->get('fatsecret.recipe')->get($id);

        $data = array();
        $data['units'] = 1;
        $data['date'] = new \DateTime('today', $user->getDateTimeZone());
        $data['name'] = "$recipe->recipe_name";

        $form = $this->createForm(new FoodType($recipe, 'recipe'), $data, array(
                        'action' => $this->generateUrl('food_diary_new_by_type', array(
                                'id' => $id,
                                'type' => 'recipe',
                                )),
                        ))
                ->add('submit', 'submit', array(
                            'label' => 'Add',
                            ));

        $servings = array();

        foreach ($recipe->serving_sizes->serving as $s) {
            $serving = array();
            foreach ($s->children() as $child) {
                $serving[$child->getName()] = "$child";
            }

            $servings[] = $serving;
        }

        return array(
                    'recipe' => $recipe,
                    'servings' => json_encode($servings),
                    'form' => $form->createView(),
                    );
    }
}
