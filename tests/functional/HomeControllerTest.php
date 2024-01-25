<?php

namespace App\Tests\functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_gets_home(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'La newsletter des films cultes à (re)découvrir directement depuis ton canapé 🍿');
    }
}
