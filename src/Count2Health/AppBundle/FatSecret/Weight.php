<?php

namespace Count2Health\AppBundle\FatSecret;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.weight")
 */
class Weight extends FatSecretEntries
{

	private $entityManager;

	/**
	 * @DI\InjectParams({
	 *     "fatSecret" = @DI\Inject("fatsecret"),
	 *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager")
	 * })
	 */
	public function __construct(FatSecret $fatSecret,
			EntityManager $entityManager)
	{
		$this->fatSecret = $fatSecret;
		$this->entityManager = $entityManager;
	}

	public function getMonth(\DateTime $date, User $user)
	{
		if (null === $date) {
			$date = new \DateTime();
		}

		$weights = $this->fatSecret->doApiCall('weights.get_month',
				array(
					'date' => $this->fatSecret->dateTimeToDateInt($date),
				     ),
'weight',
				$user);

        return $weights;
	}

    public function calculateTrend(\DateTime $date, $user, $term = 0)
    {
$days = 21;
$multiplier = 2.0 / floatval($days + 1);
$accuracy = 0.9999;
$numTerms = round(log(1-$accuracy) / log(1-$multiplier));

$prevEntries = $this->getEntries($date, $user, 2, true);

if ($term == $numTerms
|| count($prevEntries) == 1) {
return new Mass(floatval($prevEntries[0]->weight_kg), 'kg');
}
elseif (empty($prevEntries)) {
    return $user->getPersonalDetails()->getStartWeight();
}

$yesterday = $this->fatSecret->dateIntToDateTime($prevEntries[0]->date_int, $user);
$yesterday->sub(new \DateInterval('P1D'));
$prevTrend = $this->calculateTrend($yesterday, $user, $term+1);

$trend = ((floatval($prevEntries[0]->weight_kg) - $prevTrend->toUnit('kg'))
* $multiplier) + $prevTrend->toUnit('kg');

return new Mass($trend, 'kg');
    }

}
