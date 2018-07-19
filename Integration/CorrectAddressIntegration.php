<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class CorrectAddressIntegration.
 */
class CorrectAddressIntegration extends AbstractEnhancerIntegration
{
    const CA_CORRECTA_CMD  = 'cmd';

    const CA_CORRECTA_DATA = 'data_dir';

    const CA_REMOTE_FILE   = 'file';

    const CA_REMOTE_HOST   = 'host';

    const CA_REMOTE_PATH   = 'home';

    const CA_REMOTE_PSWD   = 'password';

    const CA_REMOTE_USER   = 'username';

    /** @var array */
    protected $attempts;

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
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'sftp';
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'address_valid' => [
                'label' => 'Address Validated',
                'type'  => 'boolean',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            self::CA_REMOTE_USER => 'mautic.enhancer.integration.correctaddress.username',
            self::CA_REMOTE_PSWD => 'mautic.enhancer.integration.correctaddress.password',
        ];
    }

    /**
     * @return array
     */
    public function getSecretKeys()
    {
        return [self::CA_REMOTE_PSWD];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        /** @var \Symfony\Component\Translation\TranslatorInterface $trans */
        $translator = $this->getTranslator();

        if ('features' === $formArea) {
            $builder
                ->add(
                    self::CA_REMOTE_HOST,
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => $translator->trans('mautic.enhancer.integration.correctaddress.data_server'),
                        'data'     => isset($data[self::CA_REMOTE_HOST]) ? $data[self::CA_REMOTE_HOST] : '',
                        'attr'     => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.correctaddress.data_server.tooltip'
                            ),
                        ],
                    ]
                )
                ->add(
                    self::CA_REMOTE_PATH,
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => $translator->trans('mautic.enhancer.integration.correctaddress.data_path'),
                        'data'     => isset($data[self::CA_REMOTE_PATH]) ? $data[self::CA_REMOTE_PATH] : '/CorrectAddress/USA',
                        'attr'     => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.correctaddress.data_path.tooltip'
                            ),
                        ],
                    ]
                )
                ->add(
                    self::CA_REMOTE_FILE,
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => $translator->trans('mautic.enhancer.integration.correctaddress.data_file'),
                        'data'     => isset($data[self::CA_REMOTE_FILE]) ? $data[self::CA_REMOTE_FILE] : 'CorrectAddressData.zip',
                        'attr'     => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.correctaddress.data_file.tooltip'
                            ),
                        ],
                    ]
                )
                ->add(
                    self::CA_CORRECTA_CMD,
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => $translator->trans('mautic.enhancer.integration.correctaddress.correcta_cmd'),
                        'data'     => isset($data[self::CA_CORRECTA_CMD]) ? $data[self::CA_CORRECTA_CMD] : '/IstCorrectAddress/CallCorrectA',
                        'attr'     => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.correctaddress.correcta_cmd.tooltip'
                            ),
                        ],
                    ]
                )
                ->add(
                    self::CA_CORRECTA_DATA,
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => $translator->trans('mautic.enhancer.integration.correctaddress.correcta_data'),
                        'data'     => isset($data[self::CA_CORRECTA_DATA]) ? $data[self::CA_CORRECTA_DATA] : '/IstCorrectAddress/Data',
                        'attr'     => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.correctaddress.correcta_data.tooltip'
                            ),
                        ],
                    ]
                )
                ->add(
                    'autorun_enabled',
                    HiddenType::class,
                    [
                        'data' => true,
                    ]
                );
        }
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        $result       = false;
        $leadAddress1 = $lead->getAddress1();
        $leadAddress2 = $lead->getAddress2();
        $leadZipCode  = $lead->getZipcode();
        $combined     = trim($leadAddress1).'|'.trim($leadAddress2).'|'.trim($leadZipCode);
        if (strlen($combined) < 6) {
            $this->getLogger()->debug(
                'Correct Address: Not enough address data to enhance contact '.$lead->getId()
            );

            return $result;
        }

        if (isset($this->attempts[$combined])) {
            $this->getLogger()->debug('Correct Address: Already processed address on contact '.$lead->getId());

            return $result;
        }

        $leadCorrected = $lead->getFieldValue('address_valid');
        if (true === $leadCorrected) {
            $this->getLogger()->debug('Correct Address: Already validated contact '.$lead->getId());

            return $result;
        }

        if (
            $leadAddress1 == strtoupper($leadAddress1)
            && $leadAddress2 == strtoupper($leadAddress2)
            && strlen($leadAddress1) > 2
            && ' ' == substr($leadAddress1, -1)
        ) {
            $this->getLogger()->debug('Correct Address: Likely already processed contact '.$lead->getId());

            return $result;
        }

        $address = implode(
            '|',
            [
                $this->sanitizeAddressData($leadAddress1),
                $this->sanitizeAddressData($leadAddress2),
                $this->sanitizeAddressData($leadZipCode),
            ]
        );

        // Run the executable to correct the address.
        $this->attempts[$combined] = true;
        $corrected                 = $this->callCorrectA($address);
        if (!$corrected) {
            // Complete failure to parse address.
            $this->getLogger()->debug('Correct Address: Failure to parse address on contact '.$lead->getId());

            return $result;
        }
        list($address1, $address2, $city_st_zip, $code) = explode('|', $corrected);

        if (1 <= (int) $code) {
            $city_st_zip = explode(' ', $city_st_zip);
            $zipCode     = array_pop($city_st_zip);
            $state       = array_pop($city_st_zip);
            $city        = implode(' ', $city_st_zip);

            $address1  = trim($address1);
            $address2  = trim($address2);
            $city      = trim($city);
            $state     = trim($state);
            $zipCode   = trim($zipCode);
            $leadCity  = $lead->getCity();
            $leadState = $lead->getState();
            if (!empty($address1) && $address1 !== $leadAddress1) {
                $lead->setAddress1($address1);
                $lead->addUpdatedField('address1', $address1, $leadAddress1);
                $this->getLogger()->debug(
                    'Correct Address: Updated address1 to '.$address1.' for contact '.$lead->getId()
                );
                $result = true;
            }
            if (!empty($address2) && $address2 !== $leadAddress2) {
                $lead->setAddress2($address2);
                $lead->addUpdatedField('address2', $address2, $leadAddress2);
                $this->getLogger()->debug(
                    'Correct Address: Updated address2 to '.$address2.' for contact '.$lead->getId()
                );
                $result = true;
            }
            if (!empty($city) && $city !== $leadCity) {
                $lead->setCity($city);
                $lead->addUpdatedField('city', $city, $leadCity);
                $this->getLogger()->debug(
                    'Correct Address: Updated city to '.$city.' for contact '.$lead->getId()
                );
                $result = true;
            }
            if (!empty($state) && $state !== $leadState) {
                $lead->setState($state);
                $lead->addUpdatedField('state', $state, $leadState);
                $this->getLogger()->debug(
                    'Correct Address: Updated state to '.$state.' for contact '.$lead->getId()
                );
                $result = true;
            }
            if (!empty($zipCode) && $zipCode !== $leadZipCode) {
                $lead->setZipcode($zipCode);
                $lead->addUpdatedField('zipcode', $zipCode, $leadZipCode);
                $this->getLogger()->debug(
                    'Correct Address: Updated zipcode to '.$zipCode.' for contact '.$lead->getId()
                );
                $result = true;
            }
            if ($result) {
                $lead->addUpdatedField('address_valid', true, $leadCorrected);
            }
        } else {
            $this->getLogger()->debug('Correct Address: Could not discern accurate address, returned code '.$code);
        }

        return $result;
    }

    /**
     * @param $addressData
     *
     * @return string
     */
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

    /**
     * @param $addressData
     *
     * @return bool|string
     */
    protected function callCorrectA($addressData)
    {
        $return   = false;
        $settings = $this->getIntegrationSettings()->getFeatureSettings();

        if (!file_exists($settings[self::CA_CORRECTA_CMD])) {
            $this->getLogger()->error(
                'Correct Address: Could not find executable '.$settings[self::CA_CORRECTA_CMD]
            );
        } else {
            $pipes   = [];
            $process = proc_open(
                $settings[self::CA_CORRECTA_CMD],
                [
                    ['pipe', 'r'], // stdin
                    ['pipe', 'w'], // stdout
                    ['pipe', 'w'], // stderr
                ],
                $pipes,
                dirname($settings[self::CA_CORRECTA_CMD]),
                ['CA_DATA' => $settings[self::CA_CORRECTA_DATA]]
            );

            if (is_resource($process)) {
                // Send input to CallCorrectA and close its stdin
                fwrite($pipes[0], $addressData) && fclose($pipes[0]);

                // Log issues and cleanup
                if ($err = stream_get_contents($pipes[2])) {
                    $this->getLogger()->error(
                        'Correct Address: Error from executable '.$err
                    );
                } else {
                    $return = fgets($pipes[1]);
                }

                fclose($pipes[1]) && fclose($pipes[2]) && proc_close($process);
            } else {
                $this->getLogger()->error(
                    'Correct Address: Could not open executable '.$settings[self::CA_CORRECTA_CMD]
                );
            }
        }

        return $return;
    }
}
