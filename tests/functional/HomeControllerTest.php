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
        $this->assertSelectorTextContains('h1', 'Chaque semaine, reçois une sélection de films choisis pour tes enfants ✉');
    }
}
