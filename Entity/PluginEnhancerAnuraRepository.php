<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 3:52 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class PluginEnhancerAnuraRepository extends CommonRepository
{
    /**
     * @param $ipAddress
     * @param $userAgent
     *
     * @return null|object
     */
    public function findByIpAndUserAgent($ipAddress, $userAgent)
    {
        return $this->findOneBy(['ipAddress' => $ipAddress, 'userAgent' => $userAgent]);
    }
}
