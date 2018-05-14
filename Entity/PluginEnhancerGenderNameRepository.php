<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 10:50 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\MauticEnhancerBundle\Model\GenderNameModel;

class PluginEnhancerGenderNameRepository extends CommonRepository
{
    public function getTableName()
    {
        return MAUTIC_TABLE_PREFIX.PluginEnhancerGenderName::TABLE_NAME;
    }

    /**
     * @throws DBALException
     */
    public function createReferenceTable()
    {
        $table_name = $this->getTableName();

        $sql = <<<EOSQL
CREATE TABLE `$table_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `gender` varchar(1) NOT NULL,
  `probability` float(7,4) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_probable_gender` (`name`, `probability`, `gender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOSQL;
        try {
            $this->getEntityManager()->getConnection()->exec($sql);
        } catch (DBALException $e) {
            exit('Failed to create '.$table_name.': '.$e->getMessage());
        }
    }

    /**
     * @return int
     *
     * @throws DBALException
     */
    public function verifyReferenceTable()
    {
        try {
            $sql     = 'SELECT COUNT(*) FROM '.$this->getTableName();
            $results = $this->getEntityManager()->getConnection()->fetchArray($sql);

            return $results[0][0];
        } catch (DBALException $e) {
            $this->createReferenceTable();

            return 0;
        }
    }

    /**
     * @throws DBALException
     */
    public function emptyReferenceTable()
    {
        try {
            $sql = 'TRUNCATE plugin_enhancer_city_state_postal_code';
            $this->getEntityManager()->getConnection()->exec($sql);
        } catch (DBALException $e) {
            $this->createReferenceTable();
        }
    }

    /**
     * @param GenderNameModel $model
     *
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateReferenceTable(GenderNameModel $model)
    {
        $this->emptyReferenceTable();
        $em        = $this->getEntityManager();

        $preppedData = $model->prepareGenderNameData();
        $count       = 0;

        foreach ($preppedData as $datum) {
            $entry = new PluginEnhancerGenderName();
            $entry->setName($datum['name']);

            $em->persist($entry);
            if (100 < ++$count) {
                $em->flush();
                $count = 0;
            }
        }

        $em->flush();
        $em->clear();
    }
}
