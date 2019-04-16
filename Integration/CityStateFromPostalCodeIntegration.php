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

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCode;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class CityStateFromPostalCodeIntegration.
 */
class CityStateFromPostalCodeIntegration extends AbstractEnhancerIntegration
{
    const MAX_LEV_DIST = 4;

    /** @var string */
    const COUNTRIES = 'AF,AX,AL,DZ,AS,AD,AO,AI,AQ,AG,AR,AM,AW,AU,AT,AZ,BS,BH,BD,BB,BY,BE,BZ,BJ,BM,BT,BO,BQ,BA,BW,BV,BR,IO,BN,BG,BF,BI,KH,CM,CA,CV,KY,CF,TD,CL,CN,CX,CC,CO,KM,CG,CD,CK,CR,CI,HR,CU,CW,CY,CZ,DK,DJ,DM,DO,EC,EG,SV,GQ,ER,EE,ET,FK,FO,FJ,FI,FR,GF,PF,TF,GA,GM,GE,DE,GH,GI,GR,GL,GD,GP,GU,GT,GG,GN,GW,GY,HT,HM,VA,HN,HK,HU,IS,IN,ID,IR,IQ,IE,IM,IL,IT,JM,JP,JE,JO,KZ,KE,KI,KP,KR,KW,KG,LA,LV,LB,LS,LR,LY,LI,LT,LU,MO,MK,MG,MW,MY,MV,ML,MT,MH,MQ,MR,MU,YT,MX,FM,MD,MC,MN,ME,MS,MA,MZ,MM,NA,NR,NP,NL,NC,NZ,NI,NE,NG,NU,NF,MP,NO,OM,PK,PW,PS,PA,PG,PY,PE,PH,PN,PL,PT,PR,QA,RE,RO,RU,RW,BL,SH,KN,LC,MF,PM,VC,WS,SM,ST,SA,SN,RS,SC,SL,SG,SX,SK,SI,SB,SO,ZA,GS,SS,ES,LK,SD,SR,SJ,SZ,SE,CH,SY,TW,TJ,TZ,TH,TL,TG,TK,TO,TT,TN,TR,TM,TC,TV,UG,UA,AE,GB,US,UM,UY,UZ,VU,VE,VN,VG,VI,WF,EH,YE,ZM,ZW';

    public static $US_STATES = [
        'AL' => 'Alabama',          'AK' => 'Alaska',
        'AZ' => 'Arizona',          'AR' => 'Arkansas',
        'CA' => 'California',       'CO' => 'Colorado',
        'CT' => 'Connecticut',      'DE' => 'Delaware',
        'FL' => 'Florida',          'GA' => 'Georgia',
        'HI' => 'Hawaii',           'ID' => 'Idaho',
        'IL' => 'Illinois',         'IN' => 'Indiana',
        'IA' => 'Iowa',             'KS' => 'Kansas',
        'KY' => 'Kentucky',         'LA' => 'Louisiana',
        'ME' => 'Maine',            'MD' => 'Maryland',
        'MA' => 'Massachusetts',    'MI' => 'Michigan',
        'MN' => 'Minnesota',        'MS' => 'Mississippi',
        'MO' => 'Missouri',         'MT' => 'Montana',
        'NE' => 'Nebraska',         'NV' => 'Nevada',
        'NH' => 'New Hampshire',    'NJ' => 'New Jersey',
        'NM' => 'New Mexico',       'NY' => 'New York',
        'NC' => 'North Carolina',   'ND' => 'North Dakota',
        'OH' => 'Ohio',             'OK' => 'Oklahoma',
        'OR' => 'Oregon',           'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',     'SC' => 'South Carolina',
        'SD' => 'South Dakota',     'TN' => 'Tennessee',
        'TX' => 'Texas',            'UT' => 'Utah',
        'VT' => 'Vermont',          'VA' => 'Virginia',
        'WA' => 'Washington',       'WV' => 'West Virginia',
        'WI' => 'Wisconsin',        'WY' => 'Wyoming',
        'DC' => 'District of Columbia',
        ];

    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected $integrationModel;

    /**
     * @return string
     */
    public function getName()
    {
        return 'CityStateFromPostalCode';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Fill Missing City, State/Province and County From Postal Code';
    }

    /**
     * @return array
     */
    protected function getEnhancerFieldArray()
    {
        // hi-jacking build routine to create reference table
        // this will ensure the table is installed
        try {
            $this->getIntegrationModel()->verifyReferenceTable();
        } catch (\Exception $e) {
            $this->logger->error('CityStateFromPostalCode: '.$e->getMessage());
            $this->settings->setIsPublished(false);
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans('mautic.enhancer.integration.citystatefromzip.failure')
            );
        }

        return [
            'county' => [
                'label' => 'County',
                'type'  => 'text',
            ],
        ];
    }

    /**
     * @return \Mautic\CoreBundle\Model\AbstractCommonModel|\MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected function getIntegrationModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.citystatepostalcode');
        }

        return $this->integrationModel;
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        $persist = false;

        $leadCity   = $lead->getCity();
        $leadState  = $lead->getState();
        $leadCounty = $lead->getFieldValue('county');

        // Get country in standard ISO3166 format.
        $leadCountry = $lead->getCountry();

        if (empty($leadCountry)) {
            /** @var mixed $ipDetails */
            $ipDetails   = $this->factory->getIpAddress()->getIpDetails();
            $leadCountry = isset($ipDetails['country']) ? strtoupper($ipDetails['country']) : 'US';
        } elseif (2 !== strlen($leadCountry)) {
            $leadCountry = $this->countryNameToISO3166($leadCountry);
        }

        // Get standardized zip without +4
        $leadZipCode = $lead->getZipcode();
        $dash        = strpos((string) $leadZipCode, '-');
        $leadZipCode = $dash ? substr((string) $leadZipCode, 0, $dash) : $leadZipCode;

        // Override User city, state if zip code given
        if (!(empty($leadCountry) || empty($leadZipCode))) {
            /** @var PluginEnhancerCityStatePostalCode $cityStatePostalCode */
            $cityStatePostalCode = $this->getIntegrationModel()->getRepository()->findOneBy(
                [
                    'postalCode' => $leadZipCode,
                    'country'    => $leadCountry,
                ]
            );

            if (null !== $cityStatePostalCode) {
                if (/*empty($leadCity) &&*/ !empty($cityStatePostalCode->getCity() && $leadCity !== $cityStatePostalCode->getCity())) {
                    $this->logger->debug('CityStateFromPostalCode: Found city for lead '.$lead->getId());
                    $lead->setCity($cityStatePostalCode->getCity());
                    $persist = true;
                }

                if (/*empty($leadState) &&*/ !empty($cityStatePostalCode->getStateProvince()) && $leadState !== $cityStatePostalCode->getStateProvince()) {
                    $this->logger->debug('CityStateFromPostalCode: Found state/province for lead '.$lead->getId());
                    $spLong  = $cityStatePostalCode->getStateProvince();
                    $spShort = in_array($spLong, self::$US_STATES)
                        ? array_search($spLong, self::$US_STATES)
                        : $spLong;
                    $lead->setState($spShort);
                    $persist = true;
                }

                if (/*empty($leadCounty) &&*/ !empty($cityStatePostalCode->getCounty()) && $leadCounty !== $cityStatePostalCode->getCounty()) {
                    $this->logger->debug('CityStateFromPostalCode: Found county for lead '.$lead->getId());
                    $lead->addUpdatedField('county', $cityStatePostalCode->getCounty(), $leadCounty);
                    $persist = true;
                }
            }
        } elseif (
            !(empty($leadCity) || empty($leadState) || empty($leadCountry)) &&
            empty($leadZipCode) &&
            empty($lead->getAddress1())
        ) {
            $stateProvince = array_key_exists($leadState, self::$US_STATES)
                ? self::$US_STATES[$leadState]
                : $leadState;
            /** @var PluginEnhancerCityStatePostalCode $cityStatePostalCode */
            $cityStatePostalCode = $this->getIntegrationModel()->getRepository()->findOneBy(
                [
                    'city'          => $leadCity,
                    'stateProvince' => $stateProvince,
                    'country'       => $leadCountry,
                ]
            );
            if (null !== $cityStatePostalCode) {
                $this->logger->debug('CityStateFromPostalCode: Found zipcode for lead '.$lead->getId());
                $lead->setZipcode($cityStatePostalCode->getPostalCode());
                $persist = true;
            } else {
                //handle non-exact city match
                /** @var QueryBuilder $qb */
                $qb = $this->em->getConnection()->createQueryBuilder();

                $qb->select(['city', 'MIN(postal_code) AS postal_code'])
                    ->from('plugin_enhancer_city_state_postal_code')
                    ->where(
                        'state_province = :state',
                        'country = :country'
                    )
                    ->groupBy('city')
                    ->setParameters([
                        'state'   => $stateProvince,
                        'country' => $leadCountry,
                    ]);

                $results = $qb->execute()->fetchAll();
                $picked  = [];
                foreach ($results as $result) {
                    if (empty($picked)) {
                        $picked['postal_code'] = $result['postal_code'];
                        $picked['distance']    = levenshtein($leadCity, $result['city']);
                    } else {
                        $distance = levenshtein($leadCity, $result['city']);
                        if ($distance < $picked['distance']) {
                            $picked['postal_code'] = $result['postal_code'];
                            $picked['distance']    = $distance;
                        }
                    }
                }
                if (isset($picked['distance']) && $picked['distance'] < self::MAX_LEV_DIST) {
                    $lead->setZipcode($picked['postal_code']);
                    $persist = true;
                }
            }
        }
        $this->em->clear('PluginEnhancerCityStatePostalCode');

        return $persist;
    }

    /**
     * @param $countryName
     *
     * @return string
     */
    private function countryNameToISO3166($countryName)
    {
        foreach (explode(',', self::COUNTRIES) as $countryCode) {
            $generatedCountryName = \Locale::getDisplayRegion('-'.$countryCode, 'EN');
            if (0 === strcasecmp($countryName, $generatedCountryName)) {
                return $countryCode;
                break;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'autorun_enabled',
                HiddenType::class,
                [
                    'data' => true,
                ]
            );
        }
    }

    /**
     * @param $section
     *
     * @return mixed
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return $this->translator->trans('mautic.enhancer.integration.citystatefromzip.custom_note');
        }
    }
}
