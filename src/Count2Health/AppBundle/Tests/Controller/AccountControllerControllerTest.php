<?php

namespace Count2Health\AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testEdit()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/edit');
    }

    public function testUpdate()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/update');
    }

}
