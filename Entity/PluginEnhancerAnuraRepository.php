<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 3:52 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;


use Mautic\CoreBundle\Entity\CommonRepository;

class PluginEnhancerAnuraRepository extends CommonRepository
{
    /**
     * @param $ipAddress
     * @param $userAgent
     *
     * @return null|bool
     */
    public function findByIpAndUserAgent($ipAddress, $userAgent)
    {
        /** @var PluginEnhancerAnura $found */
        $found = $this->findOneBy(['ipAddress' => $ipAddress, 'userAgent' => $userAgent]);
        if ($found) {
            return $found->isSuspect();
        }

        return null;
    }
}
