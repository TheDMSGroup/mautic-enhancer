<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Interface NonFreeEnhancerInterface.
 */
interface NonFreeEnhancerInterface
{
    /**
     * @param Lead  $lead
     * @param array $config
     */
    public function pushLead(Lead $lead, array $config = []);

    /**
     * @return bool
     */
    public function getAutorunEnabled();

    /**
     * @return string|float
     */
    public function getCostPerEnhancement();
}
