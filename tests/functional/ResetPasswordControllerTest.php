<?php

namespace App\Tests\functional;

use App\Service\SendInBlueApiService;
use App\Tests\builder\database\SubscriberBuilder;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetPasswordControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();
        /**
         * @var EntityManagerInterface $entityManager
         */
        $entityManager = $container->get(EntityManagerInterface::class);
        $purger        = new ORMPurger($entityManager);
        $purger->purge();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new SubscriberBuilder($connection))
            ->withEmail('marty@mcfly.com')
            ->withIsVerified(false)
            ->insert();
    }

    /**
     * @test
     */
    public function it_gets_forgot_password(): void
    {
        // Act
        $this->client->request('GET', '/mot-de-passe-oublie/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Réinitialiser mon mot de passe');
    }

    /**
     * @test
     */
    public function it_requests_new_password(): void
    {
        // Arrange
        $container            = $this->client->getContainer();
        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $sendInBlueApiService->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview())
        ;
        $sendInBlueApiService->expects(self::once())
            ->method('sendTransactionalEmail')
        ;
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        // Act
        $this->client->request(
            'POST',
            '/mot-de-passe-oublie/',
            [
                '_username' => 'marty@mcfly.com',
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Code envoyé');
    }
}
