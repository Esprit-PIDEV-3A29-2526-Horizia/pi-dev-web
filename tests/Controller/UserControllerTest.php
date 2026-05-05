<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testProfileRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-profil');
        $this->assertResponseStatusCodeSame(302);
    }
}