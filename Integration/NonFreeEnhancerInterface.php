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

/**
 * Interface NonFreeEnhancerInterface.
 */
interface NonFreeEnhancerInterface
{
    public function getAutorunEnabled();

    public function getCostPerEnhancement();    
}