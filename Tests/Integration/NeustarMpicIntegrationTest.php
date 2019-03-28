<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:08 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\NeustarMpicIntegration;
use PHPUnit\Framework\TestCase;

class NeustarMpicIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $this->markTestSkipped('WIP');
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue'])
            ->getMock();

        $mockIntegration = $this->getMockBuilder(NeustarMpicIntegration::class)
            ->setMethods([])
            ->getMock();

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
