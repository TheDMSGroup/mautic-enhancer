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
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {

        return [
            LeadEvents::LEAD_POST_SAVE => [
                'doEnhancements',
                0
            ],
        ];
    }
    
    /**
     * @var IntergrationHelper
     */
       
    public function doEnhancements(LeadEvent $e) {
        $integrations = EnhancerHelper::getIntegrations();
            
        foreach ($integrations as $integration) {
            $settings = $integration->getIntegrationSettings();
            if ($settings->getIsPublished()) {
                
                $integration->doEnhancement($e->getLead());
            }
        }
    }    
}
