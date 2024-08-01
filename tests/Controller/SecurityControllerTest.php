<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageDisplaysCorrectly()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertSelectorExists('form[name="login"]');

        $this->assertSelectorNotExists('.alert-danger');
    }

    public function testRedirectAuthenticatedUser()
    {
        $client = static::createClient();

        $client->loginUser($this->createMockUser($client));

        $client->request('GET', '/login');

        $this->assertResponseRedirects('/homepage');
    }

    private function createMockUser($client)
    {
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        $user = new \App\Entity\User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();
        
        return $user;
    }
}
