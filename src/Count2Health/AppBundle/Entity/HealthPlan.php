<?php

namespace Count2Health\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Count2Health\AppBundle\Validator\Constraints\UnitGreaterThan;
use Count2Health\AppBundle\Validator\Constraints\GoalWeightLessThanStartWeight;
use Count2Health\AppBundle\Validator\Constraints\GoalWeightGreaterThanStartWeight;
use Count2Health\AppBundle\Validator\Constraints\GoalWeightWithinFiveLbOfStartWeight;
use Count2Health\AppBundle\Validator\Constraints\DeficitIsHealthy;

/**
 * HealthPlan.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Count2Health\AppBundle\Entity\HealthPlanRepository")
 * @GoalWeightLessThanStartWeight(groups={"Loss"})
 * @GoalWeightGreaterThanStartWeight(groups={"Gain"})
 * @GoalWeightWithinFiveLbOfStartWeight(groups={"Maintenance"})
 * @DeficitIsHealthy(groups={"Loss"})
 */
class HealthPlan
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Count2Health\UserBundle\Entity\User
     *
     * @ORM\OneToOne(targetEntity="Count2Health\UserBundle\Entity\User",
     *     inversedBy="healthPlan")
     */
    private $user;

    /**
     * @var string
     *
     * @Assert\Choice(choices={"loss", "maintenance", "gain"})
     * @ORM\Column(name="type", type="string", length=11)
     */
    private $type;

    /**
     * @var mass
     *
     * @UnitGreaterThan(value=0)
     * @ORM\Column(name="goalWeight", type="mass")
     */
    private $goalWeight;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="goalDate", type="date")
     * @Assert\NotNull(message="Please enter a goal date.",
     *     groups={"Loss", "Gain"})
     * @Assert\GreaterThan(value="today",
     *     message="The goal date must be later than today.",
     *     groups={"Loss", "Gain"})
     */
    private $goalDate;

    /**
     * The target calorie deficit per day.
     *
     * The deficit is the difference between the TDEE and calories consumed.
     * If the user is trying to lose weight, the calories consumed will be
     * less than the TDEE. if the user is trying to gain weight, the
     * calories consumed will be higher than the TDEE.
     *
     * @var int
     *
     * @ORM\Column(name="targetCalorieDeficit", type="integer")
     * @Assert\NotNull(groups={"Loss", "Gain"})
     * @Assert\GreaterThan(value=0, groups={"Loss", "Gain"})
     */
    private $targetCalorieDeficit;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return HealthPlan
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set goalWeight.
     *
     * @param mass $goalWeight
     *
     * @return HealthPlan
     */
    public function setGoalWeight($goalWeight)
    {
        $this->goalWeight = $goalWeight;

        return $this;
    }

    /**
     * Get goalWeight.
     *
     * @return mass
     */
    public function getGoalWeight()
    {
        return $this->goalWeight;
    }

    /**
     * Set goalDate.
     *
     * @param \DateTime $goalDate
     *
     * @return HealthPlan
     */
    public function setGoalDate($goalDate)
    {
        $this->goalDate = $goalDate;

        return $this;
    }

    /**
     * Get goalDate.
     *
     * @return \DateTime
     */
    public function getGoalDate()
    {
        return $this->goalDate;
    }

    /**
     * Set targetCalorieDeficit.
     *
     * @param int $targetCalorieDeficit
     *
     * @return HealthPlan
     */
    public function setTargetCalorieDeficit($targetCalorieDeficit)
    {
        $this->targetCalorieDeficit = $targetCalorieDeficit;

        return $this;
    }

    /**
     * Get targetCalorieDeficit.
     *
     * @return int
     */
    public function getTargetCalorieDeficit()
    {
        return $this->targetCalorieDeficit;
    }

    /**
     * Set user.
     *
     * @param \Count2Health\UserBundle\Entity\User $user
     *
     * @return HealthPlan
     */
    public function setUser(\Count2Health\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Count2Health\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
