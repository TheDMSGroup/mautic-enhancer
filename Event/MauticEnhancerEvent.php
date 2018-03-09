<?php

namespace MauticPlugin\MauticEnhancerBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration as Enhancer;

/**
 * Class MauticEnhancerEvent.
 */
class MauticEnhancerEvent extends CommonEvent
{
    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration
     */
    protected $enhancer;

    /**
     * Constructor.
     *
     * @param \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration $enhancer
     * @param \Mautic\LeadBundle\Entity\Lead                                             $lead
     * @param bool                                                                       $isNew
     */
    public function __construct(Enhancer &$enhancer, Lead &$lead, $isNew)
    {
        parent::__contruct($lead, $isNew);

        $this->enhancer = $enhancer;
    }

    /**
     * @return \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration
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
