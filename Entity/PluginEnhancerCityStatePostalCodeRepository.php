<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 11:28 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel;

class PluginEnhancerCityStatePostalCodeRepository extends CommonRepository
{
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
    public function createReferenceTable()
    {
        $sql = <<<EOSQL
CREATE TABLE `plugin_enhancer_city_state_postal_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postal_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state_province` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_postal_code` (`country`,`postal_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOSQL;

        $this->getEntityManager()->getConnection()->exec($sql);
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
                $data                                              = explode("\t", trim(fgets($fp)));
                list($country, $postalCode, $city, $stateProvince) = array_slice($data, 0, 4);
                $record                                            = new PluginEnhancerCityStatePostalCode();
                $record
                    ->setCountry($country)
                    ->setPostalCode($postalCode)
                    ->setCity($city)
                    ->setStateProvince($stateProvince);
                $em->persist($record);
                ++$count;
                if (0 === ($count % $batchSize)) {
                    $em->flush();
                    $em->clear();
                }
            }
            $em->flush();
            $em->clear();
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
}
