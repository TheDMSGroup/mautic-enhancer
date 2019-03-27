<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:04 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\CityStateFromPostalCodeIntegration;
use PHPUnit\Framework\TestCase;

class CityStateFromPostalCodeIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue'])
            ->getMock();

        $mockIntegration = $this->getMockBuilder(CityStateFromPostalCodeIntegration::class)
            ->setMethods([])
            ->getMock();

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
