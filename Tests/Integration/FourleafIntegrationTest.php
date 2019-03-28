<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:05 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\FourleafIntegration;
use PHPUnit\Framework\TestCase;

class FourleafIntegrationTest extends TestCase
{
    public function testDoEnhancementNew()
    {
        $this->markTestSkipped('Unable to test with cURL functions - refactoring required');

        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue', 'getEmail'])
            ->getMock();

        $mockIntegration = $this->getMockBuilder(FourleafIntegration::class)
            ->setMethods([])
            ->getMock();

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }

    public function testDoEnhancementExisting()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue', 'getEmail'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getEmail')
            ->willReturn('nobody@example.com');

        $leadObserver->expects($this->once())
            ->method('getFieldValue')
            ->willReturn(('dummy algo'));

        $mockIntegration = $this->getMockBuilder(FourleafIntegration::class)
            ->setMethodsExcept(['doEnhancement'])
            ->getMock();

        $this->assertFalse($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
