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
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;

/**
 * Class LeadSubsciber.
 */
class LeadSubscriber extends CommonSubscriber
{
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_PRE_SAVE => ['doAutoRunEnhancements', 10],
        ];
    }

    /**
     * Runs enhancements before the Lead is persisted.
     *
     * @param LeadEvent $event
     *
     * @throws ApiErrorException
     */
    public function doAutoRunEnhancements(LeadEvent $event)
    {
        if (!$event->getLead()->getId()) {
            $lead = $event->getLead();
            /**
             * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[]
             */
            $integrations = $this->enhancerHelper->getEnhancerIntegrations();
            foreach ($integrations as $integration) {
                $settings = $integration->getIntegrationSettings();
                if ($settings->getIsPublished()) {
                    $features = $settings->getFeatureSettings();
                    if (isset($features['autorun_enabled']) && $features['autorun_enabled']) {
                        try {
                            $stop = 'here instead';
                            $integration->doEnhancement($lead);
                        } catch (\Exception $exception) {
                            $e = new ApiErrorException(
                                'There was an issue using enhancer: '.$integration->getName(),
                                0,
                                $exception
                            );
                            if (!empty($lead)) {
                                $e->setContact($lead);
                            }
                            throw $e;
                        }
                    }
                }
            }
        }
        $this->logger->addInfo('AutoEnhancement of lead '.$lead->getId().'complete');
    }

    /**
     * @param LeadEvent $event
     *
     * @throws ApiErrorException
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
                    try {
                        $lead = $event->getLead();
                        $integration->doEnhancement($lead);
                    } catch (\Exception $exception) {
                        $e = new ApiErrorException(
                            'There was an issue using enhancer: '.$integration->getName(),
                            0,
                            $exception
                        );
                        if (!empty($lead)) {
                            $e->setContact($lead);
                        }
                        throw $e;
                    }
                }
            }
        }
    }
}
