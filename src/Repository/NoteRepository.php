<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

   /**
    * FindByQuery
    * Méthode pour la recherche de note dans l'application CodeXpress
    * @param string $query
    * @return array
    */
   public function findByQuery($query): array
   {
       return $this->createQueryBuilder('n')
           ->where('n.is_public = :is_public')
           ->andWhere('n.title LIKE :q OR n.content LIKE :q')
           ->setParameter('is_public', true)
           ->setParameter('q', '%'. $query .'%')
           ->orderBy('n.created_at', 'DESC')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult()
       ;
   }

   /**
    * Trouve les notes d'un auteur
    * @param int $id
    * @return array|null
    */
   public function findByAuthor($id): ?array
   {
       $result = $this->createQueryBuilder('n')
           ->where('n.is_public = :is_public')
           ->andWhere('n.author = :id')
           ->setParameter('is_public', true)
           ->setParameter('id', $id)
           ->orderBy('n.created_at', 'DESC')
           ->setMaxResults(3)
           ->getQuery()
           ->getResult();

       return empty($result) ? null : $result;
   }
}