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

/**
 * Class LeadSubsciber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_PRE_SAVE => ['doAutoRunEnhancements', 10],
        ];
    }

    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper
     */
    protected $enhancerHelper;

    /**
     * @param \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $helper
     */
    public function __construct(EnhancerHelper $helper)
    {
        $this->enhancerHelper = $helper;
    }

    /**
     * Runs enhancements before the Lead is persisted
     *
     * @param LeadEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doAutoRunEnhancements(LeadEvent $event)
    {
        if (!$event->getLead()->getId()) {
            /**
             * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[]
             */
            $integrations = $this->enhancerHelper->getEnhancerIntegrations();
            foreach ($integrations as $integration) {
                if ($integration->getIntegrationSettings()->getIsPublished()) {
                    $keys = $integration->getKeys();
                    if (isset($keys['autorun_enabled']) && $keys['autorun_enabled'])  {
                        $lead = $event->getLead();
                        $integration->doEnhancement($lead);
                    }
                }
            }
        }
    }

    /**
     * @param LeadEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doPostSaveEnhancements(LeadEvent $event)
    {
        /**
         * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[]
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
