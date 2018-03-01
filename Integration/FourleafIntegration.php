<?php


namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class FourleafIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /**
     * Implements NonFreeEnhancerInterface
     */
    use NonFreeEnhancerTrait {
        getRequiredKeyFields as getNonFreeKeyFields;
    }
 
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Fourleaf';
    }
        
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'id' => $this->translator->trans('mautic.integration.fourleaf.id.label'),
            'key' => $this->translator->trans('mautic.integration.fourleaf.key.label'),
            'url' => $this->translator->trans('mautic.integration.fourleaf.url.label'),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        $this->appendCostToForm($builder, $data, $formArea);
    }
             
    /**
     * {@inheritdoc}
     */
    protected function getEnhancerFieldArray()
    {
      $object = class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle') ? 'extendedField' : 'lead';

      return [
            'fourleaf_algo'             => ['label' => 'Algo', 'object'=>$object],
            'fourleaf_low_intel'        => ['label' => 'Low Intel', 'object'=>$object],
            'fourleaf_activity_score'   => ['label' => 'Activity Score', 'object'=>$object],
            'fourleaf_hygiene_reason'   => ['label' => 'Hygiene Reason', 'object'=>$object],
            'fourleaf_hygiene_score'    => ['label' => 'Hygiene Score', 'object'=>$object],
            //'fourleaf_md5',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function doEnhancement(Lead $lead, array $config = [])
    {
        $algo = $lead->getFieldValue('fourleaf_algo');
        $email = $lead->getEmail();
        
        if ($algo || !$email) {
            return;
        }
                      
        $keys = $this->getDecryptedApiKeys();
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $keys['url'] . $lead->getEmail(),
            CURLOPT_HTTPHEADER => [
                "x-fourleaf-id: $keys[id]",
                "x-fourleaf-key: $keys[key]",                
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);        
        curl_close($ch);
        
        $response = json_decode($response, true);
               
        error_log(print_r($response, true));
        
        foreach ($response as $key => $value) {
            if ($key === 'md5') {
                continue;
            }
            $alias = 'fourleaf_' . str_replace('user_', '', $key);
            $default = $lead->getFieldValue($alias);
            $lead->addUpdatedField($alias, $value, $default);        
        }
        
        $this->leadModel->saveEntity($lead);
        $this->em->flush();
    }
}