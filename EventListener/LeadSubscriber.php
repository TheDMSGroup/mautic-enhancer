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
use MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerInterface;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /** @var EnhancerHelper */
    protected $helper;

    /**
     * LeadSubscriber constructor.
     *
     * @param EnhancerHelper $helper
     */
    public function __construct(EnhancerHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE => [ // instead of LEAD_IDENTIFIED
                'doEnhancements',
                0,
            ],
        ];
    }

    /**
     * @param LeadEvent $e
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doEnhancements(LeadEvent $e)
    {
        $integrations = $this->helper->getIntegrations();
        $lead         = $e->getLead();

        $completed = [];
        foreach ($integrations as $name => $integration) {
            $settings = $integration->getIntegrationSettings();
            if (method_exists($settings, 'getKeys')) {
                $keys = $settings->getKeys();
            }
            if ($settings->getIsPublished() && (
                    (isset($keys['autorun_enabled']) && $keys['autorun_enabled']) ||
                    (1)
                )) {
                $integration->doEnhancement($lead);
                $completed[] = $name;
            }
            if ($integration instanceof NonFreeEnhancerInterface) {
                $new_attribution = $lead->getAttribution() + $integration->getCostPerEnhancement();
                $lead->setUpdatedField($new_attribution);
                $this->em->getRepository('Lead')->saveEntitiy($lead);
                $this->em->flush();
            }
        }
    }
}
