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

class PluginsEnhancerCityStatePostalCodeRepository extends CommonRepository
{
    /**
     * @throws DBALException
     */
    public function createReferenceTable()
    {
        $sql = <<<EOSQL
CREATE TABLE plugin_enhancer_city_state_zip (
  id             INT AUTO_INCREMENT NOT NULL, 
  postal_code    VARCHAR(255) NOT NULL, 
  city           VARCHAR(255) DEFAULT NULL, 
  state_province VARCHAR(255) DEFAULT NULL, 
  country        VARCHAR(255) DEFAULT NULL, 
  PRIMARY KEY(id),
  INDEX idx_postal_code (postal_code),
  INDEX idx_country (country, postal_code)
) 
DEFAULT CHARACTER SET utf8 
COLLATE utf8_unicode_ci 
ENGINE = InnoDB
EOSQL;

        $this->getEntityManager()->getConnection()->exec($sql);
    }
}
