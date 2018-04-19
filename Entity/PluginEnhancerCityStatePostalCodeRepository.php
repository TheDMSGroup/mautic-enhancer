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
     * @throws DBALException
     */
    public function createReferenceTable()
    {
        $sql = <<<EOSQL
CREATE TABLE plugin_enhancer_city_state_postal_code (
  id INT AUTO_INCREMENT NOT NULL, 
  postal_code VARCHAR(255) NOT NULL, 
  city VARCHAR(255) DEFAULT NULL, 
  state_province VARCHAR(255) DEFAULT NULL, 
  country VARCHAR(255) NOT NULL, 
  INDEX idx_postal_code (postal_code), 
  INDEX idx_country (country, postal_code), 
  PRIMARY KEY(id)
) 
DEFAULT CHARACTER SET utf8 
COLLATE utf8_unicode_ci 
ENGINE = InnoDB
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
