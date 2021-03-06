<?php

namespace Count2Health\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FoodDiaryEntry
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Count2Health\AppBundle\Entity\FoodDiaryEntryRepository")
 */
class FoodDiaryEntry
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Count2Health\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Count2Health\UserBundle\Entity\User",
     *     inversedBy="foodDiary")
     */
    private $user;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="calories", type="integer")
     */
    private $calories;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set calories
     *
     * @param integer $calories
     * @return FoodDiaryEntry
     */
    public function setCalories($calories)
    {
        $this->calories = $calories;

        return $this;
    }

    public function addCalories($calories)
    {
        $this->calories += $calories;

        return $this;
    }

    public function subtractCalories($calories)
    {
        $this->calories -= $calories;

        return $this;
    }

    /**
     * Get calories
     *
     * @return integer 
     */
    public function getCalories()
    {
        return $this->calories;
    }

    /**
     * Set user_id
     *
     * @param \Count2Health\UserBundle\Entity\User $userId
     * @return FoodDiaryEntry
     */
    public function setUserId(\Count2Health\UserBundle\Entity\User $userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return \Count2Health\UserBundle\Entity\User 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return FoodDiaryEntry
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set user
     *
     * @param \Count2Health\UserBundle\Entity\User $user
     * @return FoodDiaryEntry
     */
    public function setUser(\Count2Health\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Count2Health\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
