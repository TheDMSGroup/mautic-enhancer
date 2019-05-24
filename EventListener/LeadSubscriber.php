<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
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
    /** @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper */
    protected $enhancerHelper;

    /** @var array Leads that have already ran the auto-enhancers in this session by ID. */
    protected $leadsEnhanced = [];

    /**
     * LeadSubscriber constructor.
     *
     * @param EnhancerHelper $helper
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
        $lead = $event->getLead();
        if ($lead && (null !== $lead->getDateIdentified() || !$lead->isAnonymous() || !empty($lead->getFieldValue('xx_trusted_form_cert_url')))) {
            // Ensure we do not duplicate this work within the same session.
            $leadKey = strtolower(
                implode(
                    '|',
                    [
                        $lead->getFirstname(),
                        ($lead->getLastActive() ? $lead->getLastActive()->format('c') : ''),
                        $lead->getEmail(),
                        $lead->getPhone(),
                        $lead->getMobile(),
                    ]
                )
            );
            if (strlen($leadKey) > 3) {
                if (isset($this->leadsEnhanced[$leadKey])) {
                    return;
                } else {
                    $this->leadsEnhanced[$leadKey] = true;
                }
            }

            /** @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration */
            $integrations = $this->enhancerHelper->getEnhancerIntegrations();
            foreach ($integrations as $integration) {
                $settings = $integration->getIntegrationSettings();
                if ($settings->getIsPublished()) {
                    $features = $settings->getFeatureSettings();
                    if (isset($features['autorun_enabled']) && $features['autorun_enabled']) {
                        try {
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
            $this->logger->info('doAutoRunEnhancements complete');
        }
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
