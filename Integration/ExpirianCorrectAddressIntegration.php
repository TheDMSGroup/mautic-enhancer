<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 6/5/18
 * Time: 12:02 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ExpirianCorrectAddressIntegration extends AbstractEnhancerIntegration
{
    const CA_REMOTE_HOST = 'host';
    const CA_REMOTE_PORT = 'port';
    const CA_REMOTE_PATH = 'home';
    const CA_REMOTE_FILE = 'file';

    const CA_REMOTE_USER = 'username';
    const CA_REMOTE_PSWD = 'password';
    const CA_REMOTE_FNGR = 'fingerprint';

    const CA_CORRECTA_PATH = 'work_dir';
    const CA_CORRECTA_CMD  = 'cmd';
    const CA_CORRECTA_DATA = 'data_dir';

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
        //expirian username and password for data downloads
        return 'keys';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        $integrationFields = [
            self::CA_REMOTE_USER  => $this->translator->trans('mautic.integration.correctaddress.username'),
            self::CA_REMOTE_PSWD  => $this->translator->trans('mautic.integration.correctaddress.password'),
            self::CA_REMOTE_FNGR  => $this->translator->trans('mautic.integration.correctaddress.fingerprint'),
        ];

        return $integrationFields;
    }

    public function appendToForm(&$builder, $data, $formArea)
    {
        $builder
            ->add(
                self::CA_REMOTE_HOST,
                TextType::class,
                [
                    'default_protocol' => 'sftp',
                    'required'         => true,
                    'label'            => 'mautic.enhancer.correctaddress.data_server',
                    'data'             => $data['data_server'],
                ]
            )
            ->add(
                self::CA_REMOTE_PORT,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.data_port',
                    'data'  => $data['data_port'] ?: '22',
                ]
            )
            ->add(
                self::CA_REMOTE_PATH,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.data_path',
                    'data'  => $data['data_path'] ?: '/CorrectAddress/USA',
                ]
            )
            ->add(
                self::CA_REMOTE_FILE,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.data_file',
                    'data'  => $data['data_file'] ?: 'CorrectAddressData.zip',
                ]
            )
            ->add(
                self::CA_CORRECTA_PATH,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.correcta_path',
                    'data'  => $data[self::CA_CORRECTA_PATH] ?: '/IstCorrectAddress',
                ]
            )
            ->add(
                self::CA_CORRECTA_CMD,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.correcta_cmd',
                    'data'  => $data[self::CA_CORRECTA_CMD] ?: 'CallCorrectA',
                ]
            )
            ->add(
                self::CA_CORRECTA_DATA,
                TextType::class,
                [
                    'label' => 'mautic.enhancer.correctaddress.correcta_data',
                    'data'  => $data[self::CA_CORRECTA_DATA] ?: $data[self::CA_CORRECTA_PATH].'/Data',
                ]
            )
        ;
    }

    public function doEnhancement(Lead &$lead)
    {
        $address = implode('|', [
            $this->sanitizeAddressData($lead->getAddress1()),
            $this->sanitizeAddressData($lead->getAddress2()),
            $this->sanitizeAddressData($lead->getZipcode()),
        ]);

        $corrected = $this->callCorrectA($address);

        list($address1, $address2, $city_st_zip, $code) = explode('|', $corrected);
        list($city, $state, $zipcode)                   = explode(' ', $city_st_zip);

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
        $return   = false;

        $stdio = [
            ['pipe', 'r'], //stdin
            ['pipe', 'w'], //stdout
            ['pipe', 'w'],  //stderr
        ];

        $process = proc_open(
            $settings[self::CA_CORRECTA_CMD],
            $stdio,
            $pipes,
            $settings[self::CA_CORRECTA_PATH],
            ['CA_DATA' => $settings[self::CA_CORRECTA_DATA]]
        );

        if (is_resource($process)) {
            //send input to CallCorrectA and close its stdin
            fwrite($pipes[0], $addressData) && fclose($pipes[0]);

            //log issues and cleanup
            if ($err = stream_get_contents($pipes[2])) {
                $this->getLogger()->error($err);
            } else {
                $return = fgets($pipes[1], 194);
            }

            fclose($pipes[1]) && fclose($pipes[2]) && proc_close($process);
        }

        return $return;
    }
}
