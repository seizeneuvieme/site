<?php

namespace App\Tests\functional;

use App\Service\BrevoApiService;
use App\Tests\builder\database\SubscriberBuilder;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
        $this->client->request('GET', '/password-forgotten/');

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
        $BrevoApiService = $this->createMock(BrevoApiService::class);
        $BrevoApiService->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview())
        ;
        $BrevoApiService->expects(self::once())
            ->method('sendTransactionalEmail')
        ;
        $container->set(BrevoApiService::class, $BrevoApiService);

        // Act
        $this->client->request(
            'POST',
            '/password-forgotten/',
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
