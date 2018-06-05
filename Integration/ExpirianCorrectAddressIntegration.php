<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 6/5/18
 * Time: 12:02 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;


use Mautic\LeadBundle\Entity\Lead;

class ExpirianCorrectAddressIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'CorrectAddress';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Expirian Correct Address';
    }

    /**
     * @return array
     */
    protected function getEnhancerFieldArray()
    {
        return [];
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    public function appendToForm(&$builder, $data, $formArea)
    {
        //TODO: Complete settings form
        //expirian username and password for data downloads
        //remote server (sftp, 22)
        //location of remotae data files


        //location of data files -> data_dir
        //location of so and executable  -> working_dir
        //name of executable -> proc_cmd


    }

    public function doEnhancement(Lead &$lead)
    {
        $address = implode('|', [
            $this->sanitizeAddressData($lead->getAddress1()),
            $this->sanitizeAddressData($lead->getAddress2()),
            $this->sanitizeAddressData($lead->getZipcode())
        ]);

        $corrected = $this->callCorrectA($address);
        
        list($address1, $address2, $city_st_zip, $code) = explode('|', $corrected);
        list($city, $state, $zipcode) = explode(' ', $city_st_zip);

        if ('1' <= $code) {
            $lead->addUpdatedField('address_1', $address1, $lead->getAddress1());
            $lead->addUpdatedField('address_2', $address2, $lead->getAddress2());
            $lead->addUpdatedField('city', $city, $lead->getCity());
            $lead->addUpdatedField('state', $state, $lead->getState());
            $lead->addUpdatedField('zipcode', $zipcode, $lead->getZipcode());
        }

    }

    protected function sanitizeAddressData($addressData)
    {
        return str_pad(
            preg_replace(
                '/[^-A-Z0-9 ]/',
                '',
                strtoupper($addressData)
            ),
            64,
            ' ',
            STR_PAD_RIGHT
        );
    }

    protected function callCorrectA($addressData)
    {
        $settings = $this->getSupportedFeatures();
        $return = false;

        $stdio = [
            ['pipe', 'r'], //stdin
            ['pipe', 'w'], //stdout
            ['pipe', 'w']  //stderr
        ];

        $process = proc_open( $settings['proc_cmd'], $stdio, $pipes, $settings['working_dir'], ['CA_DATA' => $settings['data_dir']]);

        if (is_resource($process)) {

            //send input to CallCorrectA and close its stdin
            fwrite($pipes[0], $addressData) && fclose($pipes[0]);
            
            //log issues and cleanup
            if ($err = stream_get_contents($pipes[2])) {
                $this->getLogger()->error($err);
            }
            else {
                $return = fgets($pipes[1], 194);
            }
            
            fclose($pipes[1]) && fclose($pipes[2]) && proc_close($process);

        }
        return $return;
    }
}
