<?php

namespace App\Tests\functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FooterControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_gets_legal_notice(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/mentions-legales');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mentions légales');
    }

    /**
     * @test
     */
    public function it_gets_privacy_policy(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/politique-de-confidentialite');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Politique de confidentialité');
    }
}