<?php

namespace Count2Health\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Count2Health\AppBundle\Validator\Constraints\IsUnit;
use Count2Health\AppBundle\Validator\Constraints\UnitGreaterThan;

/**
 * WeightDiaryEntry
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Count2Health\AppBundle\Entity\WeightDiaryEntryRepository")
 * @Assert\GroupSequence({"WeightDiaryEntry", "Secondary"})
 */
class WeightDiaryEntry
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
     *     inversedBy="weightDiary")
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @Assert\Date()
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var float
     *
     * @IsUnit(message="Please enter your weight.")
     * @UnitGreaterThan(value=0,
     *     message="Your weight must be greater than 0.",
     *     groups={"Secondary"})
     */
    private $weight;

    /**
     * @var float
     *
     * @ORM\Column(name="trend", type="mass", precision=18, scale=14)
     */
    private $trend;

    /**
     * @var string
     *
     * The comment for this weight diary entry.
     */
    private $comment;


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
     * Set date
     *
     * @param \DateTime $date
     * @return WeightDiaryEntry
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
     * Set weight
     *
     * @param string $weight
     * @return WeightDiaryEntry
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return string 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set trend
     *
     * @param string $trend
     * @return WeightDiaryEntry
     */
    public function setTrend($trend)
    {
        $this->trend = $trend;

        return $this;
    }

    /**
     * Get trend
     *
     * @return string 
     */
    public function getTrend()
    {
        return $this->trend;
    }

    /**
     * Get BMI
     *
     * @return float
     */
    public function getBMI()
    {
        if (null === $this->getUser()
                || null === $this->getUser()->getPersonalDetails()) {
            return;
        }

        $height = $this->getUser()->getPersonalDetails()->getHeight();
        $weight = $this->getWeight();

        return $weight->toUnit('kg') / pow($height->toUnit('m'), 2);
    }

    /**
     * Set user
     *
     * @param \Count2Health\UserBundle\Entity\User $user
     * @return WeightDiaryEntry
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

    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

}
