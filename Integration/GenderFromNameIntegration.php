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
    /** @var \MauticPlugin\MauticEnhancerBundle\Model\GenderNameModel */
    protected $integrationModel;

    public function getName()
    {
        return 'GenderFromName';
    }

    public function getDisplayName()
    {
        return 'Choose Gender From Name';
    }

    protected function getIntegrationModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.gendername');
        }

        return $this->integrationModel;
    }

    /**
     * @return array
     */
    protected function getEnhancerFieldArray()
    {
        try {
            $this->getIntegrationModel()->verifyReferenceTable();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->settings->setIsPublished(false);
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans('mautic.enhancer.integration.genderfromname.failure')
            );
        }

        return [];
    }

    /**
     * @param Lead $lead
     *
     * @return mixed|void
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!$lead->getFieldValue('gender') or $this->replaceCurrent) {
            $gender = $this->getIntegrationModel()->getGender($lead->getFirstname());
            if ($gender) {
                $lead->addUpdatedField('gender', $gender);
            }
        }
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $data
     * @param string                              $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'autorun_enabled',
                'hidden',
                [
                    'data' => true,
                ]
            );
        }
    }

    /**
     * @param $section
     *
     * @return string|void
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return $this->translator->trans('mautic.enhancer.integration.genderfromname.custom_note');
        }
    }
}
