<?php

namespace Count2Health\UserBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Count2Health\UserBundle\Entity\PersonalDetails
     *
     * @ORM\OneToOne(targetEntity="Count2Health\UserBundle\Entity\PersonalDetails",
     *     mappedBy="user")
     */
    private $personalDetails;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Count2Health\AppBundle\Entity\WeightDiaryEntry",
     *     mappedBy="user")
     */
    private $weightDiary;

    /**
     * @var Count2Health\AppBundle\Entity\FoodDiaryEntry
     *
     * @ORM\OneToMany(targetEntity="Count2Health\AppBundle\Entity\FoodDiaryEntry",
     *     mappedBy="user")
     */
    private $foodDiary;

    /**
     * @var Count2Health\AppBundle\Entity\HealthPlan
     *
     * @ORM\OneToOne(targetEntity="Count2Health\AppBundle\Entity\HealthPlan",
     *     mappedBy="user")
     */
    private $healthPlan;

    /**
     * @var string
     *
     * @ORM\Column(name="timeZone", type="string", length=255)
     * @Assert\NotBlank(message="Please select your timezone.",
     *     groups={"Registration", "Profile"})
     */
    private $timeZone;

    /**
     * @var string
     *
     * @ORM\Column(name="authToken", type="string", length=255,
     *     nullable=true)
     */
    private $authToken;

    /**
     * @var string
     *
     * @ORM\Column(name="authSecret", type="string", length=255,
     *     nullable=true)
     */
    private $authSecret;

    /**
     * @var string
     *
     * @ORM\Column(name="requestToken", type="string", length=255,
     *     nullable=true)
     */
    private $requestToken;

    /**
     * @var string
     *
     * @ORM\Column(name="requestSecret", type="string", length=255,
     *     nullable=true)
     */
    private $requestSecret;

    /**
     * @var bool
     *
     * @ORM\Column(name="connected", type="boolean", nullable=true)
     */
    private $connected;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

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
     * Set personal details
     *
     * @param \Count2Health\UserBundle\Entity\PersonalDetails
     * $personalDetails
     * @return User
     */
    public function setPersonalDetails(\Count2Health\UserBundle\Entity\PersonalDetails $personalDetails = null)
    {
        $this->personalDetails = $personalDetails;

        return $this;
    }

    /**
     * Get personal details
     *
     * @return \Count2Health\UserBundle\Entity\PersonalDetails 
     */
    public function getPersonalDetails()
    {
        return $this->personalDetails;
    }

    /**
     * Add weightDiary
     *
     * @param \Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiaryEntrys
     * @return User
     */
    public function addWeightDiaryEntry(\Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiaryEntry)
    {
        $this->weightDiary[] = $weightDiaryEntry;

        return $this;
    }

    /**
     * Remove weightDiaryEntry
     *
     * @param \Count2Health\AppBundle\Entity\WeightDiaryEntry
     * $weightDiaryEntry
     */
    public function removeWeightLog(\Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiaryEntry)
    {
        $this->weightDiary->removeElement($weightDiaryEntry);
    }

    /**
     * Get weightDiary
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWeightDiary()
    {
        return $this->weightDiary;
    }

    /**
     * Gets the last weight
     *
     * @return Count2Health\AppBundle\Entity\WeightDiaryEntry
     */
    public function getLastWeight()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(array('date' => Criteria::DESC))
            ->setFirstResult(0)
            ->setMaxResults(1);

        $entries = $this->getWeightDiary()->matching($criteria);

        if (empty($entries)) {
            return;
        }

        return $entries[0];
    }

    public function getWeightBefore(WeightDiaryEntry $weight)
    {
        $criteria = Criteria::create();
        $criteria
            ->where($criteria->expr()->lt('date', $weight->getDate()))
            ->orderBy(array('date' => Criteria::DESC))
            ->setFirstResult(0)
            ->setMaxResults(1);

        $entries = $this->getWeightDiary()->matching($criteria);

        if ($entries->isEmpty()) {
            return;
        }

        return $entries[0];
    }


    /**
     * Set authToken
     *
     * @param string $authToken
     * @return User
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Get authToken
     *
     * @return string 
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set authSecret
     *
     * @param string $authSecret
     * @return User
     */
    public function setAuthSecret($authSecret)
    {
        $this->authSecret = $authSecret;

        return $this;
    }

    /**
     * Get authSecret
     *
     * @return string 
     */
    public function getAuthSecret()
    {
        return $this->authSecret;
    }

    /**
     * Add weightDiary
     *
     * @param \Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiary
     * @return User
     */
    public function addWeightDiary(\Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiary)
    {
        $this->weightDiary[] = $weightDiary;

        return $this;
    }

    /**
     * Remove weightDiary
     *
     * @param \Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiary
     */
    public function removeWeightDiary(\Count2Health\AppBundle\Entity\WeightDiaryEntry $weightDiary)
    {
        $this->weightDiary->removeElement($weightDiary);
    }

    /**
     * Set healthPlan
     *
     * @param \Count2Health\AppBundle\Entity\HealthPlan $healthPlan
     * @return User
     */
    public function setHealthPlan(\Count2Health\AppBundle\Entity\HealthPlan $healthPlan = null)
    {
        $this->healthPlan = $healthPlan;

        return $this;
    }

    /**
     * Get healthPlan
     *
     * @return \Count2Health\AppBundle\Entity\HealthPlan 
     */
    public function getHealthPlan()
    {
        return $this->healthPlan;
    }

    public function getBMR()
    {
        $entry = $this->getLastWeight();

        if ($entry) {
            $weight = $entry->getTrend();
        }
        else {
            $weight = $this->getPersonalDetails()->getStartWeight();
        }

    $bmr = 10 * $weight->toUnit('kg');
    $bmr += 6.25 * $this->getPersonalDetails()->getHeight()->toUnit('cm');

    // Get years since birth, i.e., age
    $today = new \DateTime();
    $age = $today->diff($this->getPersonalDetails()->getBirthDate());
    $bmr -= 4.92 * $age->y;

    if ('male' == $this->getPersonalDetails()->getGender()) {
        $bmr += 5;
    }
    elseif ('female' == $this->getPersonalDetails()->getGender()) {
        $bmr -= 161;
    }

    return $bmr;
    }

public function getEstimatedTDEE()
{
    $tdee = $this->getBMR();

    switch ($this->getPersonalDetails()->getActivityLevel())
    {
        case 's':
            $tdee *= 1.2;
            break;

        case 'l':
            $tdee *= 1.375;
            break;

        case 'm':
            $tdee *= 1.55;
            break;

        case 'v':
            $tdee *= 1.725;
            break;

        case 'e':
            $tdee *= 1.9;
            break;
    }

    return $tdee;
}


    /**
     * Add foodDiary
     *
     * @param \Count2Health\AppBundle\Entity\FoodDiaryEntry $foodDiary
     * @return User
     */
    public function addFoodDiary(\Count2Health\AppBundle\Entity\FoodDiaryEntry $foodDiary)
    {
        $this->foodDiary[] = $foodDiary;

        return $this;
    }

    /**
     * Remove foodDiary
     *
     * @param \Count2Health\AppBundle\Entity\FoodDiaryEntry $foodDiary
     */
    public function removeFoodDiary(\Count2Health\AppBundle\Entity\FoodDiaryEntry $foodDiary)
    {
        $this->foodDiary->removeElement($foodDiary);
    }

    /**
     * Get foodDiary
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFoodDiary()
    {
        return $this->foodDiary;
    }

    /**
     * Set connected
     *
     * @param boolean $connected
     * @return User
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;

        return $this;
    }

    /**
     * Get connected
     *
     * @return boolean 
     */
    public function getConnected()
    {
        return $this->connected;
    }

/**
 * Check if account is connected to FatSecret.
 *
 * @return bool
 */
public function isConnected()
{
    return $this->connected;
}

    /**
     * Set requestToken
     *
     * @param string $requestToken
     * @return User
     */
    public function setRequestToken($requestToken)
    {
        $this->requestToken = $requestToken;

        return $this;
    }

    /**
     * Get requestToken
     *
     * @return string 
     */
    public function getRequestToken()
    {
        return $this->requestToken;
    }

    /**
     * Set requestSecret
     *
     * @param string $requestSecret
     * @return User
     */
    public function setRequestSecret($requestSecret)
    {
        $this->requestSecret = $requestSecret;

        return $this;
    }

    /**
     * Get requestSecret
     *
     * @return string 
     */
    public function getRequestSecret()
    {
        return $this->requestSecret;
    }

    /**
     * Set timeZone
     *
     * @param string $timeZone
     * @return User
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * Get timeZone
     *
     * @return string 
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

/**
 * Returns the DateTimeZone object for this user's timezone.
 *
 * @return \DateTimeZone
 */
public function getDateTimeZone()
{
    return new \DateTimeZone($this->getTimeZone());
}
}
