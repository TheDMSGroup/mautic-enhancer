<?php

namespace MauticPlugin\MauticEnhancerBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration as Enhancer;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MauticEnhancerEvent
 *
 * @package \MauticPlugin\mauticEnhancerBundle\Event
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
     * Constructor
     *
     * @param \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration $enhancer
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     */
    public function __construct(Enhancer &$enhancer, Lead &$lead, Campaign &$campaign)
    {
        
        $this->enhancer = $enhancer;
        $this->lead = $lead;
    }

    /**
     * @return  \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration
     */
    public function getEnhancer()
    {
        return $this->enhancer;
    }

    /**
     *  @param \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration $enhancer
     *
     *  @return $this
     */
    public function setEnhancer(Enhancer $enhancer)
    {
        $this->enhancer = $enhancer;
        return $this;
    }

    /**
     * @param bool $display
     *
     * @return string
     */
    public function getEnhancerName($display = false)
    {
        return $display ? $this->enhancer->getDisplayName() : $this->enhancer->getName();
    }
}
