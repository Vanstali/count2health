<?php

namespace Count2Health\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Count2Health\AppBundle\Validator\Constraints\IsUnit;
use Count2Health\AppBundle\Validator\Constraints\UnitGreaterThan;

/**
 * Setting
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Count2Health\AppBundle\Entity\SettingRepository")
 * @Assert\GroupSequence(
 *     {"Setting", "Secondary", "Tertiary", "Quaternary", "Quinary"})
 */
class Setting
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
     * @Assert\NotNull()
     * @ORM\OneToOne(targetEntity="Count2Health\UserBundle\Entity\User",
     *     inversedBy="setting")
     */
    private $user;

    /**
     * @var string
     *
     * The units for the weights to be in.
     *
     * Can be either lb or kg.
     *
     * @Assert\NotBlank(message="Please select a weight unit.")
     * @Assert\Choice(choices={"lb", "kg"},
     *     message="An invalid weight unit was selected.",
     *     groups={"Secondary"})
     * @ORM\Column(name="weightUnits", type="string", length=2)
     */
    private $weightUnits;

    /**
     * @var string
     *
     * The units for the height to be in.
     *
     * Can be either inch or cm.
     *
     * @Assert\NotBlank(message="Please select a height unit.")
     * @Assert\Choice(choices={"inch", "cm"},
     *     message="An invalid height unit was selected.", groups={"Secondary"})
     * @ORM\Column(name="heightUnits", type="string", length=4)
     */
    private $heightUnits;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Please select a gender.")
     * @Assert\Choice(choices={"male", "female"},
     *     message="An invalid gender was selected.", groups={"Secondary"})
     * @ORM\Column(name="gender", type="string", length=6)
     */
    private $gender;

    /**
     * @var float
     *
     * @Assert\NotNull(message="Please enter your height.",
     *     groups={"Tertiary"})
     * @IsUnit(message="Height must be a unit.", groups={"Quaternary"})
     * @UnitGreaterThan(value=0,
     *     message="Height must be greater than 0.", groups={"Quinary"})
     * @ORM\Column(name="height", type="length", precision=15, scale=14)
     */
    private $height;

    /**
     * @var float
     *
     * @UnitGreaterThan(value=0,
     *     message="Weight must be greater than 0.")
     * @ORM\Column(name="startWeight", type="mass")
     */
    private $startWeight;

    /**
     * @var float
     *
     * @ORM\OneToOne(targetEntity="WeightDiaryEntry")
     */
    private $currentWeight;

    /**
     * @var \DateTime
     *
     * @Assert\NotNull(message="Please select your date of birth.")
     * @Assert\Date(message="Birth date must be a date.", groups={"Secondary"})
     * @Assert\LessThan(value="today",
     *     message="Birth date must be before today.", groups={"Tertiary"})
     * @ORM\Column(name="birthDate", type="date")
     */
    private $birthDate;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Please select an activity level.")
     * @Assert\Choice(choices={"s", "l", "m", "v", "e"},
     *     message="An invalid activity level has been selected.",
     *     groups={"Secondary"})
     * @ORM\Column(name="activityLevel", type="string", length=1)
     */
    private $activityLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="timeZone", type="string", length=100, nullable=true)
     */
    private $timeZone;


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
     * Set weightUnits
     *
     * @param string $weightUnits
     * @return Setting
     */
    public function setWeightUnits($weightUnits)
    {
        $this->weightUnits = $weightUnits;

        return $this;
    }

    /**
     * Get weightUnits
     *
     * @return string 
     */
    public function getWeightUnits()
    {
        return $this->weightUnits;
    }

    /**
     * Set heightUnits
     *
     * @param string $heightUnits
     * @return Setting
     */
    public function setHeightUnits($heightUnits)
    {
        $this->heightUnits = $heightUnits;

        return $this;
    }

    /**
     * Get heightUnits
     *
     * @return string 
     */
    public function getHeightUnits()
    {
        return $this->heightUnits;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return Setting
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set height
     *
     * @param length $height
     * @return Setting
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return length 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set birthDate
     *
     * @param \DateTime $birthDate
     * @return Setting
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Get birthDate
     *
     * @return \DateTime 
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Set activityLevel
     *
     * @param string $activityLevel
     * @return Setting
     */
    public function setActivityLevel($activityLevel)
    {
        $this->activityLevel = $activityLevel;

        return $this;
    }

    /**
     * Get activityLevel
     *
     * @return string 
     */
    public function getActivityLevel()
    {
        return $this->activityLevel;
    }

    /**
     * Set user
     *
     * @param \Count2Health\UserBundle\Entity\User $user
     * @return Setting
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

    /**
     * Set startWeight
     *
     * @param PhpUnitsOfMeasure\PhysicalQuantity\Mass $startWeight
     * @return Setting
     */
    public function setStartWeight($startWeight = null)
    {
        $this->startWeight = $startWeight;

        return $this;
    }

    /**
     * Get startWeight
     *
     * @return PhpUnitsOfMeasure\PhysicalQuantity\Mass
     */
    public function getStartWeight()
    {
        return $this->startWeight;
    }

    /**
     * Set currentWeight
     *
     * @param \Count2Health\AppBundle\Entity\WeightDiaryEntry $currentWeight
     * @return Setting
     */
    public function setCurrentWeight(\Count2Health\AppBundle\Entity\WeightDiaryEntry $currentWeight = null)
    {
        $this->currentWeight = $currentWeight;

        return $this;
    }

    /**
     * Get currentWeight
     *
     * @return \Count2Health\AppBundle\Entity\WeightDiaryEntry 
     */
    public function getCurrentWeight()
    {
        return $this->currentWeight;
    }

    /**
     * Set timeZone
     *
     * @param string $timeZone
     * @return Setting
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
}
