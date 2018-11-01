<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
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
        $this->createReferenceTable();
    }

    /**
     * @throws DBALException
     */
    public function createReferenceTable()
    {
        $table = MAUTIC_TABLE_PREFIX.PluginEnhancerCityStatePostalCode::TABLE_NAME;
        $sql   = <<<EOSQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postal_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state_province` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `county` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
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
            $this->createReferenceTable();
            $em        = $this->getEntityManager();
            $batchSize = 500;
            $count     = 0;
            while (!feof($fp)) {
                $data = explode("\t", trim(fgets($fp)));
                list(
                    $country,
                    $postalCode,
                    $city,
                    $stateProvince,
                    $state,
                    $county,
                    $a, $b, $c,
                    $latitude,
                    $longitude
                    ) = array_slice(
                    $data,
                    0,
                    11
                );
                if (!$country || !$postalCode) {
                    continue;
                }
                $record = new PluginEnhancerCityStatePostalCode();
                $record
                    ->setCountry($country)
                    ->setPostalCode($postalCode)
                    ->setCity($city)
                    ->setStateProvince($stateProvince)
                    ->setCounty($county)
                    ->setLatitude($latitude)
                    ->setLongitude($longitude);
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
            $table = MAUTIC_TABLE_PREFIX.PluginEnhancerCityStatePostalCode::TABLE_NAME;
            $sql   = 'DROP TABLE IF EXISTS `'.$table.'`';
            $this->getEntityManager()->getConnection()->exec($sql);
        } catch (DBALException $e) {
        }
    }
}
