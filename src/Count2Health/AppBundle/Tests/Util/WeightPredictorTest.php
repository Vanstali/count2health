<?php

namespace Count2Health\AppBundle\Tests\Util;

use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Util\WeightPredictor;

class WeightPredictorTest extends \PHPUnit_Framework_TestCase
{

    private $date;
    private $user;
    private $weight;
    private $stats;

    protected function setup()
    {
        $this->date = new \DateTime('today', new \DateTimeZone('America/New_York'));

        $this->user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();

        $this->user
            ->method('getDateTimeZone')
            ->will($this->returnValue(new \DateTimeZone('America/New_York')));

        $this->weight = $this->getMockBuilder(
                'Count2Health\AppBundle\FatSecret\Weight')
            ->disableOriginalConstructor()
            ->getMock()
            ;

        $this->weight
            ->method('calculateTrend')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(new Mass('200.0', 'lb')))
            ;

        $this->stats = $this->getMockBuilder(
                'Count2Health\AppBundle\Util\UserStats')
            ->disableOriginalConstructor()
            ->getMock();
        ;

        $this->weightPredictor = new WeightPredictor($this->stats, $this->weight);
    }

    public function testPredictDate()
    {
        $weight = new Mass(160, 'lb');

        // A 1,000 calorie deficit per day means losing 40 lb in 140 days
        $goalDate = clone $this->date;
        $goalDate->add(new \DateInterval('P140D'));

        $this->stats
            ->expects($this->once())
            ->method('getDailyCalorieDeficit')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(1000))
            ;

        $this->weight
            ->expects($this->once())
            ->method('calculateTrend')
            ;

        $this->assertEquals($goalDate,
                $this->weightPredictor
                ->predictDate($weight, $this->user));
    }

    public function testPredictDateGainingWeight()
    {
        $weight = new Mass(240, 'lb');

        // A 1,000 calorie excess means gaining 40 lb. in 140 days
        $goalDate = clone $this->date;
        $goalDate->add(new \DateInterval('P140D'));

        $this->stats
            ->expects($this->once())
            ->method('getDailyCalorieDeficit')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(-1000))
            ;

        $this->weight
            ->expects($this->once())
            ->method('calculateTrend')
            ;

        $this->assertEquals($goalDate,
                $this->weightPredictor
                ->predictDate($weight, $this->user));
    }

    /**
     * @expectedException Count2Health\AppBundle\Exception\InvalidWeightException
     */
    public function testPredictDateGainingButShouldBeLosing()
    {
        $weight = new Mass(160, 'lb');

        // A 1,000 calorie deficit per day means losing 40 lb in 140 days
        $goalDate = clone $this->date;
        $goalDate->add(new \DateInterval('P140D'));

        $this->stats
            ->expects($this->once())
            ->method('getDailyCalorieDeficit')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(-1000))
            ;

        $this->weight
            ->expects($this->once())
            ->method('calculateTrend')
            ;

        $this->weightPredictor
            ->predictDate($weight, $this->user);
    }

    public function testPredictWeight()
    {
        $date = clone $this->date;
        $date->add(new \DateInterval('P140D'));

        // A 1,000 calorie deficit means that in 140 days, 40 lb. would be lost.
        $weight = new Mass(160, 'lb');

        $this->stats
            ->expects($this->once())
            ->method('getDailyCalorieDeficit')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(1000))
            ;

        $this->weight
            ->expects($this->once())
            ->method('calculateTrend')
            ;

        $this->assertEquals($weight,
                $this->weightPredictor->predictWeight($date, $this->user));
    }

    public function testPredictWeightGainingWeight()
    {
        $date = clone $this->date;
        $date->add(new \DateInterval('P140D'));

        // A 1,000 calorie excess means that in 140 days, 40 lb. would be gained.
        $weight = new Mass(240, 'lb');

        $this->stats
            ->expects($this->once())
            ->method('getDailyCalorieDeficit')
            ->with($this->equalTo($this->date), $this->equalTo($this->user))
            ->will($this->returnValue(-1000))
            ;

        $this->weight
            ->expects($this->once())
            ->method('calculateTrend')
            ;

        $this->assertEquals($weight,
                $this->weightPredictor->predictWeight($date, $this->user));
    }

    /**
     * @expectedException Count2Health\AppBundle\Exception\InvalidDateException
     */
    public function testPredictWeightPastDate()
    {
        $date = clone $this->date;
        $date->sub(new \DateInterval('P140D'));

        $this->stats
            ->expects($this->never())
            ->method('getDailyCalorieDeficit')
            ;

        $this->weight
            ->expects($this->never())
            ->method('calculateTrend')
            ;

        $this->weightPredictor->predictWeight($date, $this->user);
    }

    /**
     * @expectedException Count2Health\AppBundle\Exception\InvalidDateException
     */
    public function testPredictWeightCurrentDate()
    {
        $date = clone $this->date;

        $this->stats
            ->expects($this->never())
            ->method('getDailyCalorieDeficit')
            ;

        $this->weight
            ->expects($this->never())
            ->method('calculateTrend')
            ;

        $this->weightPredictor->predictWeight($date, $this->user);
    }

}
