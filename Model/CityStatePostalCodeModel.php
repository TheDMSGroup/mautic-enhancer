<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/10/18
 * Time: 9:00 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;

/**
 * Class CityStatePostalCodeModel.
 */
class CityStatePostalCodeModel extends AbstractCommonModel
{
    const REFERENCE_LOCAL  = '/tmp/';

    const REFERENCE_NAME   = 'allCountries.zip';

    const REFERENCE_REMOTE = 'http://download.geonames.org/export/zip/';

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function verifyReferenceTable()
    {
        return $this->getRepository()->verifyReferenceTable();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCodeRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return '\MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCode';
    }

    /**
     * @return bool
     */
    public function updateReferenceTable()
    {
        try {
            $this->getRepository()->updateReferenceTable($this);

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }
    }

    /**
     * @return bool|resource
     */
    public function fetchAllCountriesZip()
    {
        try {
            ini_set('memory_limit', '-1');
            file_put_contents(
                self::REFERENCE_LOCAL.self::REFERENCE_NAME,
                file_get_contents(self::REFERENCE_REMOTE.self::REFERENCE_NAME)
            );
        } catch (\Exception $e) {
            $this->logger->error('Unable to download data file: '.$e->getMessage());

            return false;
        }

        $zip = new \ZipArchive();
        if (
            true === $zip->open(self::REFERENCE_LOCAL.self::REFERENCE_NAME) &&
            1 === $zip->numFiles
        ) {
            return $zip->getStream($zip->getNameIndex(0));
        }

        $this->logger->error('Unable to locate data file in archive');

        return false;
    }
}
