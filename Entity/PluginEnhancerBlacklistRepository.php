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

class PluginEnhancerBlacklistRepository extends CommonRepository
{
    /**
     * @param $phone
     * @param $ageMinutes
     *
     * @return null|object
     */
    public function findByPhone($phone)
    {
        return $this->findOneBy(['phone' => $phone]);
    }
}
