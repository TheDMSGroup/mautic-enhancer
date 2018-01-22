<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadEvent;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {

        return [
            LeadEvents::LEAD_IDENTIFIED => [
                'doEnhancements',
                0
            ],
        ];
    }
    
    public function __construct()
    {
        
    }
    
    public function doEnhancements(LeadEvent $e) {
        //->getIntegration()
        $e->getEntity()->doEnhancement($e->getEntity());
    }

    
    public function __construct()
    {
        
    }
}
