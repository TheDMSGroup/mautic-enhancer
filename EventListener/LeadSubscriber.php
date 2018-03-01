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
use MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerInterface;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;

/**
 * Class LeadSubsciber
 * 
 * @package \MauticPlugin\MauticEnhancerBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE => [ 'doEnhancements', 0 ],
        ];
    }
    
    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $enhancer_helper Helper for dealing with Enhancer integrations
     */
    protected $enhancer_helper;
    
    /**
     * Constructor
     * 
     * @param \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $enhancer_helper
     */
    public function __construct(EnhancerHelper $enhancer_helper)
    {
        $this->enhancer_helper = $enhancer_helper;    
    }
    
    /**
     * Runs Enhancer integrations configured to autorun (only for new contacts)
     *
     * @param \Mautic\LeadBundle\Event\LeadEvent $event
     */
    public function doEnhancements(LeadEvent $event)
    {
        if ($event->isNew()) {
            /**
             * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[] $integrations
             */
            $integrations = $this->enhancer_helper->getEnhancerIntegrations();
            foreach ($integrations as $integration) {
                if ($integration->isConfigured() && $integration->getIntegrationSettings()->getIsPublished()) {            
                    $keys = $integration->getKeys();
                    if ($keys['autorun_enabled']) {
                        $integration->doEnhancement($event->getLead());
                    }
                }   
            }
        }
    }
}
