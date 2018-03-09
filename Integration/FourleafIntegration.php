<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;

/**
 * Class FourleafIntegration.
 */
class FourleafIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /*
     * @var NonFreeEnhancerInterface
     */
    use NonFreeEnhancerTrait {
        getRequiredKeyFields as getNonFreeKeyFields;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Fourleaf';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Email Engagement Scoring with '.$this->getName();
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        $integrationFields = [
            'id'  => $this->translator->trans('mautic.integration.fourleaf.id.label'),
            'key' => $this->translator->trans('mautic.integration.fourleaf.key.label'),
            'url' => $this->translator->trans('mautic.integration.fourleaf.url.label'),
        ];

        return array_merge($integrationFields, $this->getNonFreeKeyFields());
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        $object = class_exists(
            'MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle'
        ) ? 'extendedField' : 'lead';

        return [
            'fourleaf_algo'           => ['label' => 'Algo', 'object' => $object],
            'fourleaf_low_intel'      => ['label' => 'Low Intel', 'object' => $object],
            'fourleaf_activity_score' => ['label' => 'Activity Score', 'object' => $object],
            'fourleaf_hygiene_reason' => ['label' => 'Hygiene Reason', 'object' => $object],
            'fourleaf_hygiene_score'  => ['label' => 'Hygiene Score', 'object' => $object],
            //'fourleaf_md5',
        ];
    }

    /**
     * @param Lead  $lead
     * @param array $config
     *
     * @return mixed|void
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doEnhancement(Lead &$lead, array $config = [])
    {
        $algo  = $lead->getFieldValue('fourleaf_algo');
        $email = $lead->getEmail();

        if ($algo || !$email) {
            return;
        }

        $keys = $this->getDecryptedApiKeys();

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $keys['url'].$lead->getEmail(),
            CURLOPT_HTTPHEADER     => [
                "x-fourleaf-id: $keys[id]",
                "x-fourleaf-key: $keys[key]",
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        foreach ($response as $key => $value) {
            if ('md5' === $key) {
                continue;
            }
            $alias   = 'fourleaf_'.str_replace('user_', '', $key);
            $default = $lead->getFieldValue($alias);
            $lead->addUpdatedField($alias, $value, $default);
        }

//        $this->leadModel->saveEntity($lead);
//        $this->em->flush();

        if ($this->dispatcher->hasListeners(MauticEnhancerEvents::ENHANCER_COMPLETED)) {
            $isNew = !$lead->getId();
            $complete = new MauticEnhancerEvent($this, $lead, $isNew);
            $this->dispatcher->dispatch(MauticEnhancerEvents::ENHANCER_COMPLETED, $complete);
        }
    }
}
