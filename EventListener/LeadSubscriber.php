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
     * @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper Helper for dealing with Enhancer integrations
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
     * Finds Enhancer integrations to run for this event
     *
     * @param \Mautic\LeadBundle\Event\LeadEvent $event
     */
    public function doEnhancements(LeadEvent $event)
    {
        $lead = $event->getLead();      
        $integrations = $this->enhancer_helper->getIntegrations();
        $completed = array();
        
        foreach ($integrations as $name => $integration) {
            if ($integration->isConfigured() && $integration->getInegrationSettings()->getIsPublished()) {            
                
                if (
                    (isset($keys['autorun_enabled']) && $keys['autorun_enabled'])
                    /* TODO: || this lead was pushed  ) */
                ) {
                    $integration->doEnhancement($lead);
                    $completed[] = $name;
                
                    if ($integration instanceof NonFreeEnhancerInterface) {
                        $new_attribution = $lead->getAttribution() + $integration->getCostPerEnhancement();
                        $lead->setUpdatedField($new_attribution);
                        $this->em->getRepository('Lead')->saveEntitiy($lead);
                        $this->em->flush();
                    }
                }
            }
        }
    }
}
