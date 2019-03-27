<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:09 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

class TrustedFormIntegrationTest extends TestCase
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
