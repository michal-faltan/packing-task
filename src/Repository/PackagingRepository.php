<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Packaging;

class PackagingRepository extends EntityRepository
{
    const DEFAULT_PACKAGING_TYPE = 'box';
    const DEFAULT_PACKAGING_MAX_QUANTITY = 1;
    const DEFAULT_PACKAGING_WEIGHT = '';

    public function getPackagingsApiData()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select([
        'p.id AS id', 
        'p.width AS w', 
        'p.height AS h', 
        'p.length AS d', 
        'p.maxWeight AS max_wg'
        ]);

        $rows = $qb->getQuery()->getArrayResult();

        foreach ($rows as &$row) 
        {
            $row['q'] = self::DEFAULT_PACKAGING_MAX_QUANTITY;
            $row['wg'] = self::DEFAULT_PACKAGING_WEIGHT;
            $row['type'] = self::DEFAULT_PACKAGING_TYPE;
        } 

        return $rows;
    }
}