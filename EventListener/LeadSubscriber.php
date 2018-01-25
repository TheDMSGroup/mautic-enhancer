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
     * @var IntegrationHelper
     */
       
    public function doEnhancements(LeadEvent $e) {
        $integration_helper = EnhancerHelper::getHelper();
        $integration_settings = $integration_helper->getIntegrationSettings();

      foreach($integration_settings as $integration){
        $plugin = $integration->getPlugin();
        $pluginName = $plugin->getBundle();
        if($pluginName == "MauticEnhancerBundle") {  // Only concerned with Integrations from this Plugin.
          $integrationName = $integration->getName();
          // crazy gymnastics to get the real Integration object (not integration entity)
          $integrationObject = $integration_helper->getIntegrationObject($integrationName);
          if ($integration->getIsPublished()) {  // dont bother if not published
            if (method_exists($integrationObject, 'doEnhancement')) {
              $integrationObject->doEnhancement($e->getLead());
            }
          }
        }
      }
    }    
}
