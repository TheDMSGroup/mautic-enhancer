<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/14/18
 * Time: 11:11 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class GenderFromNameIntegration extends AbstractEnhancerIntegration
{
    public function getName()
    {
        return 'GenderFromName';
    }

    public function getDisplayName()
    {
        return 'Choose Gender From Name';
    }

    protected function getEnhancerFieldArray()
    {
        return [
            'gfn_gender' => [
                'label' => 'Gender',
                'type'  => 'string',
            ],
        ];
    }

    public function doEnhancement(Lead &$lead)
    {
        // TODO: Implement doEnhancement() method.
    }

    public function getAuthenticationType()
    {
        return 'none';
    }
}
