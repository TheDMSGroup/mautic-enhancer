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
use MauticBundle\MauticEnhancerBundle\Helper\EnhancerHelper;

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
    
    /**
     * @var IntergrationHelper
     */
       
    public function doEnhancements(LeadEvent $e) {
        $integration_helper = EnhancerHelper::getHelper();
        $integration_settings = $integration_helper->getInegrationSettings();
            
        foreach ($integration_settings as $integration) {
            if ($integration->getIsPublished() && $integration->getPlugin() == '') {
                $e->getEntity()->doEnhancement($e->getEntity());
            }
        }
    }    
}
