<?php

namespace Count2Health\AppBundle\Tests\FatSecret;

use Count2Health\AppBundle\FatSecret\ExerciseEntries;

class ExerciseEntriesTest extends \PHPUnit_Framework_TestCase
{
    protected function getFatSecretMock()
    {
        return $this->getMockBuilder('Count2Health\AppBundle\FatSecret')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsTemplate()
    {
        $fs = $this->getFatSecretMock();

        $ee = new ExerciseEntries($fs);

        $doc = new \SimpleXMLElement(__DIR__.'/template-day.xml', null, true);
        $this->assertEquals(1, $ee->isTemplate($doc));

        $doc = new \SimpleXMLElement(__DIR__.'/non-template-day.xml', null, true);
        $this->assertEquals(0, $ee->isTemplate($doc));
    }

    public function testCommitDay()
    {
        $fs = $this->getFatSecretMock();
        $user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();
        $date = new \DateTime('today');

        $response = new \SimpleXMLElement('<success>1</success>');

        $fs
            ->expects($this->once())
            ->method('dateTimeToDateInt')
            ->with($this->equalTo($date))
            ->will($this->returnValue(16550))
            ;

        $fs->expects($this->once())
            ->method('doApiCall')
            ->with($this->equalTo('exercise_entries.commit_day'),
                    $this->equalTo(array(
                            'date' => 16550,
                            )),
                    $this->equalTo('exercise'),
                    $this->equalTo($user))
            ->will($this->returnValue($response))
            ;

        $ee = new ExerciseEntries($fs);
        $ee->commitDay($date, $user);
    }
}
