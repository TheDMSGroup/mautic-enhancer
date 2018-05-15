<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 10:50 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\MauticEnhancerBundle\Model\GenderNameModel;

class PluginEnhancerGenderNameRepository extends CommonRepository
{
    public function getTableName()
    {
        return MAUTIC_TABLE_PREFIX.PluginEnhancerGenderName::TABLE_NAME;
    }

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
  UNIQUE KEY `key_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOSQL;
        try {
            $this->getEntityManager()->getConnection()->exec($sql);
        } catch (\Exception $e) {
            exit('Failed to create '.$table_name.': '.$e->getMessage());
        }
    }

    /**
     * @return int
     */
    public function verifyReferenceTable()
    {
        try {
            $sql     = 'SELECT COUNT(*) FROM '.$this->getTableName();
            $results = $this->getEntityManager()->getConnection()->fetchArray($sql);

            return $results[0][0];
        } catch (\Exception $e) {
            $this->createReferenceTable();

            return 0;
        }
    }

    public function emptyReferenceTable()
    {
        try {
            $sql = 'TRUNCATE '.$this->getTableName();
            $this->getEntityManager()->getConnection()->exec($sql);
        } catch (\Exception $e) {
            $this->createReferenceTable();
        }
    }

    /**
     * @param GenderNameModel $model
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateReferenceTable(GenderNameModel $model)
    {
        $this->emptyReferenceTable();
        $em        = $this->getEntityManager();

        $preppedData = $model->prepareGenderNameData();
        $batchSize   = 200;
        $count       = 0;

        echo 'Inserting data'.PHP_EOL;
        foreach ($preppedData as $datum) {
            $record = new PluginEnhancerGenderName();
            $record
                ->setName($datum['name'])
                ->setGender($datum['gender'])
                ->setProbability($datum['probability'])
                ->setCount($datum['count']);
            $em->persist($record);
            $count += 1;
            if (0 === ($count % $batchSize)) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $em->clear();
    }
}
