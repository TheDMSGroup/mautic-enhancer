<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 10:50 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerGenderDictionary;

class PluginEnhancerGenderDictionaryRepository extends CommonRepository
{
    /**
     * @throws DBALException
     */
    public function createReferenceTable()
    {
        $sql = <<<EOSQL
CREATE TABLE `plugin_enhancer_city_state_postal_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `gender` varchar(1) NOT NULL,
  `probability` float(7,4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOSQL;

        $this->getEntityManager()->getConnection()->exec($sql);
    }

    /**
     * @return int
     *
     * @throws DBALException
     */
    public function verifyReferenceTable()
    {
        try {
            $sql     = 'SELECT COUNT(*) FROM plugin_enhancer_city_state_postal_code WHERE 1';
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
     * @param CityStatePostalCodeModel $model
     *
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateReferenceTable(CityStatePostalCodeModel $model)
    {
        if (false !== ($fp = $model->fetchAllCountriesZip())) {
            $this->emptyReferenceTable();
            $em        = $this->getEntityManager();
            $batchSize = 500;
            $count     = 0;
            while (!feof($fp)) {
                $data                                               = explode("\t", trim(fgets($fp)));
                list($country, $postalCode, $city, $stateProvince)  = array_slice($data, 0, 4);
                $record                                             = new PluginEnhancerCityStatePostalCode();
                $record
                    ->setCountry($country)
                    ->setPostalCode($postalCode)
                    ->setCity($city)
                    ->setStateProvince($stateProvince);
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
}