<?php


namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class FourleafIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'Fourleaf';
 
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }
    
    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . ' Data Enhancer';    
    }
        
    public function getAuthenticationType()
    {
        return 'keys';
    }
    
    public function getRequiredKeyFields()
    {
        return [
            'id' => $this->translator->trans('mautic.integration.fourleaf.id.label'),
            'key' => $this->translator->trans('mautic.integration.fourleaf.key.label'),
            'url' => $this->translator->trans('mautic.integration.fourleaf.url.label'),
        ];
    }
    
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'keys') {
            $builder->add(
                'autorun',
                'yesno_button_group',
                [
                    'label' => $this->translator->trans('mautic.integration.autorun.label'),
                    'data'  => !isset($data['autorun']) ? false : $data['autorun'],
                    'attr'  => [
                        'tooltip' => $this->translator->trans('mautic.integration.autorun.tooltip'),
                    ]
                ]
            );
        }
    }
             
    protected function getEnhancerFieldArray()
    {
        return [
            'fourleaf_algo'             => ['label' => 'Algo', 'object'=>'extendedField'],
            'fourleaf_low_intel'        => ['label' => 'Low Intel', 'object'=>'extendedField'],
            'fourleaf_activity_score'   => ['label' => 'Activity Score', 'object'=>'extendedField'],
            'fourleaf_hygiene_reason'   => ['label' => 'Hygiene Reason', 'object'=>'extendedField'],
            'fourleaf_hygiene_score'    => ['label' => 'Hygiene Score', 'object'=>'extendedField'],
            //'fourleaf_md5',
        ];
    }

    public function doEnhancement(Lead $lead)
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