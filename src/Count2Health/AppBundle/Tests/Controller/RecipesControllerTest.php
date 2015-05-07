<?php

namespace Count2Health\AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecipesControllerTest extends WebTestCase
{
    public function testSearch()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search');
    }

    public function testShow()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/show.html');
    }

}
