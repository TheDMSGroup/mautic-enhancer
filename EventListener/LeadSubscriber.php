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
use Mautic\PluginBundle\Helper\IntegrationHelper;

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
    protected $integration_helper;
    
    /**
     * @param IntegrationHelper $helper
     */
    public function __construct(PluginHelper $helper)
    {
        $this->integration_helper = $helper;
    }
    
    public function doEnhancements(LeadEvent $e) {
        $this->integration_helper->getIntegrationSettings();
        //->getIntegration()
        $e->getEntity()->doEnhancement($e->getEntity());
    }    
}
