<?php

namespace MauticPlugin\MauticEnhancerBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration as Enhancer;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MauticEnhancerEvent.
 */
class MauticEnhancerEvent extends Event
{
    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration
     */
    protected $enhancer;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    protected $lead;

    /**
     * @var \Mautic\CampaignBundle\Entity\Campaign
     */
    protected $campaign;

    /**
     * Constructor.
     *
     * @param \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration $enhancer
     * @param \Mautic\LeadBundle\Entity\Lead                                             $lead
     */
    public function __construct(Enhancer &$enhancer, Lead &$lead, Campaign &$campaign =null)
    {
        $this->enhancer = $enhancer;
        $this->lead     = $lead;
        $this->campaign = $campaign;
    }

    /**
     * @return \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration
     */
    public function getEnhancer()
    {
        return $this->enhancer;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
}
