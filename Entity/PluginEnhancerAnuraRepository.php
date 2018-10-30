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
