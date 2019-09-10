<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

class PluginEnhancerTrustedformRepository extends CommonRepository
{
    /**
     * @param int   $threadId
     * @param int   $maxThreads
     * @param int   $attemptLimit
     * @param int   $batchLimit
     * @param array $statuses
     *
     * @return array
     */
    public function findBatchToClaim(
        $threadId = 1,
        $maxThreads = 1,
        $attemptLimit = 10,
        $batchLimit = 100,
        $statuses = [0, 500, 502, 503]
    ) {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('t')
            ->where('t.status IN (:statuses)')
            ->setParameter('statuses', $statuses, Connection::PARAM_INT_ARRAY)
            ->andWhere('t.attempts < :attemptLimit')
            ->setParameter('attemptLimit', $attemptLimit, \PDO::PARAM_INT);

        if ($threadId && $maxThreads && $maxThreads > 1 && $threadId <= $maxThreads) {
            $qb->andWhere('MOD((t.id + :threadShift), :maxThreads) = 0')
                ->setParameter('threadShift', $threadId - 1, \PDO::PARAM_INT)
                ->setParameter('maxThreads', $maxThreads, \PDO::PARAM_INT);
        }

        $qb->setMaxResults($batchLimit);

        return $qb->getQuery()->getResult();
    }
}
