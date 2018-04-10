<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/10/18
 * Time: 9:00 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Model\AbstractCommonModel;

class CityStatePostalCodeModel extends AbstractCommonModel
{
    const REFERENCE_REMOTE = 'http://download.geonames.org/export/zip/';
    const REFERENCE_LOCAL = '/tmp/';
    const REFERENCE_NAME  = 'allCountries.zip';

    /**
     */
    public function createReferenceTable()
    {
        $sql = <<<EOSQL
CREATE TABLE plugin_enhancer_citystatezip (
  id            INT AUTO_INCREMENT NOT NULL, 
  postal_code   VARCHAR(255) NOT NULL, 
  city          VARCHAR(255) DEFAULT NULL, 
  stateProvince VARCHAR(255) DEFAULT NULL, 
  country       VARCHAR(255) DEFAULT NULL, 
  PRIMARY KEY(id),
  INDEX idx_postal_code (postal_code),
  INDEX idx_country (country, postal_code)
) 
DEFAULT CHARACTER SET utf8 
COLLATE utf8_unicode_ci 
ENGINE = InnoDB
EOSQL;
        try {
            $this->em->getConnection()->exec($sql);
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     */
    public function fillReferenceTable()
    {
        try {
            $sql = 'TRUNCATE plugin_enhancer_city_state_postal_code';
            $this->em->getConnection()->exec($sql);
        } catch (DBALException $e) {
            $this->createReferenceTable();
        }

        if (false !== ($fp = $this->fetchAllCountriesZip())) {
            $batchSize = 500;
            $count = 0;
            try {
                while (!feof($fp)) {
                    $data = explode("\t", trim(fgets($fp)));
                    list($country, $postalCode, $city, $statProvince) = array_slice($data, 0, 4);
                    $record = $this->getEntity();
                    $record
                        ->setCountry($country)
                        ->setPostalCode($postalCode)
                        ->setCity($city)
                        ->setStateProvince($statProvince);
                    $this->em->persist($record);
                    $count += 1;
                    if (0 === ($count % $batchSize)) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }
                $this->em->flush();
                $this->em->clear();
            } catch (OptimisticLockException $e) {
                $this->logger->error($e->getMessage());
            }
        }
   }

    public function fetchAllCountriesZip()
    {
        try {
            file_put_contents(
                self::REFERENCE_LOCAL . self::REFERENCE_NAME,
                file_get_contents(
                    self::REFERENCE_REMOTE . self::REFERENCE_NAME
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Unable to download data file: '.$e->getMessage());
            return false;
        }

        $zip = new \ZipArchive();
        if (
            true === $zip->open(self::REFERENCE_LOCAL.self::REFERENCE_NAME) &&
            1    === $zip->numFiles
        ) {
            return $zip->getStream($zip->getNameIndex(0));
        }

        $this->logger->error('unable to locate data file in archive');
        return false;
    }
}