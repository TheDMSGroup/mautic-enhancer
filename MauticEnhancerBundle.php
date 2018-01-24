<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

class MauticEnhancerBundle extends PluginBundleBase
{
    public function boot()
    {
        parent::boot();

        EnhancerHelper::init($this->container->get('mautic.helper.integration'));
    }    
}
