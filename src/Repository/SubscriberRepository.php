<?php

namespace App\Repository;

use App\Entity\Platform;
use App\Entity\Subscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Subscriber>
 *
 * @method Subscriber|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscriber|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscriber[]    findAll()
 * @method Subscriber[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriberRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriber::class);
    }

    public function add(Subscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Subscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Subscriber) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTotalNumberOfSubscribers(): int
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT id 
                     FROM subscriber
                     WHERE is_verified = true'
            )
            ->rowCount();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNumberOfNewSubscribersThisMonth(): int
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                "SELECT id 
                     FROM subscriber
                     WHERE is_verified = true
                     AND created_at >= EXTRACT(MONTH FROM NOW())"
            )
            ->rowCount();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNumberOfSubscribersForNetflix(): int
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT s.id
                     FROM subscriber s 
                     INNER JOIN subscriber_platform sp ON s.id = sp.subscriber_id
                     INNER JOIN platform p ON sp.platform_id = p.id
                     WHERE s.is_verified = true
                     AND p.name = :platform',
                [
                    'platform' => Platform::NETFLIX,
                ]
            )
            ->rowCount();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNumberOfSubscribersForDisney(): int
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT s.id 
                     FROM subscriber s 
                     INNER JOIN subscriber_platform sp ON s.id = sp.subscriber_id
                     INNER JOIN platform p ON sp.platform_id = p.id
                     WHERE s.is_verified = true
                     AND p.name = :platform',
                [
                    'platform' => Platform::DISNEY,
                ]
            )
            ->rowCount();
    }

//    /**
//     * @return Subscriber[] Returns an array of Subscriber objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Subscriber
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
