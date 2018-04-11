<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    /** @var \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration */
    protected $enhancer;

    /** @var \Mautic\LeadBundle\Entity\Lead */
    protected $lead;

    /** @var \Mautic\CampaignBundle\Entity\Campaign */
    protected $campaign;

    /**
     * MauticEnhancerEvent constructor.
     *
     * @param Enhancer      $enhancer
     * @param Lead          $lead
     * @param Campaign|null $campaign
     */
    public function __construct(Enhancer $enhancer, Lead $lead, Campaign $campaign = null)
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
