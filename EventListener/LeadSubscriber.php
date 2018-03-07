<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;

/**
 * Class LeadSubsciber
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE => [ 'doListenerEnhancements', 0 ],
        ];
    }

    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $enhancer_helper
     */
    protected $enhancerHelper;

    /**
     * @param \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $enhancer_helper
     */
    public function __construct(EnhancerHelper $helper)
    {
        $this->enhancerHelper = $enhancerHelper;
    }

    /**
     * @param LeadEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doListenerEnhancements(LeadEvent $event)
    {
        if ($event->isNew()) {
            /**
             * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[] $integrations
             */
            $integrations = $this->enhancerHelper->getEnhancerIntegrations();
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
