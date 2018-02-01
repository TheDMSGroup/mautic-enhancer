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
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;


class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [

            LeadEvents::LEAD_POST_SAVE => [ // instead of LEAD_IDENTIFIED
                'doEnhancements',
                0
            ],
        ];
    }
    
    /**
     * @param LeadEvent $e
     */
    public function doEnhancements(LeadEvent $e)
    {    
        $integrations = EnhancerHelper::getIntegrations();
        
        $completed = array();
        foreach ($integrations as $name => $integration) {
            $settings = $integration->getIntegrationSettings();
            $keys = $settings->getKeys();
            if ($settings->getIsPublished() && $keys['autorun']) {                        
                $integration->doEnhancement($e->getLead());
                $completed[] = $name;
            }
        }
    }
}
