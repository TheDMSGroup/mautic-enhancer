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
use Mautic\LeadBundle\Entity\TimelineTrait;

class PluginEnhancerTrustedformRepository extends CommonRepository
{
    use TimelineTrait;

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

    /**
     * @param       $contactId
     * @param array $options
     *
     * @return array
     */
    public function getTimelineStats($contactId, $options = [])
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select(
                [
                    't.*',
                    't.contact_id AS contactId',
                    'IF(t.created_at IS NOT NULL, t.created_at, t.date_added) AS timestamp',
                    '"Trustedform" AS enhancer',
                ]
            )
            ->from(MAUTIC_TABLE_PREFIX.$this->getTableName(), 't')
            ->where('t.contact_id = '.(int) $contactId, \PDO::PARAM_INT);

        return $this->getTimelineResults(
            $qb,
            $options,
            't.location',
            't.created_at',
            ['geo', 'claims'],
            ['t.date_added', 't.created_at', 't.expires_at']
        );
    }
}
