<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

/**
 * Interface NonFreeEnhancerInterface.
 */
interface NonFreeEnhancerInterface
{
    /**
     * @return string|float
     */
    public function getCostPerEnhancement();
}
