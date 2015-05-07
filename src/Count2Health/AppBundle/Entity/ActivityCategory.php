<?php

namespace Count2Health\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivityCategory
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Count2Health\AppBundle\Entity\ActivityCategoryRepository")
 */
class ActivityCategory
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
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="category")
     */
    private $children;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;


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
     * Set name
     *
     * @param string $name
     * @return ActivityCategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add children
     *
     * @param \Count2Health\AppBundle\Entity\Activity $children
     * @return ActivityCategory
     */
    public function addChild(\Count2Health\AppBundle\Entity\Activity $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Count2Health\AppBundle\Entity\Activity $children
     */
    public function removeChild(\Count2Health\AppBundle\Entity\Activity $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }
}
