<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:09 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use MauticPlugin\MauticEnhancerBundle\Integration\TrustedFormIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class TrustedFormIntegrationTest extends TestCase
{
    private $leadObserver;

    private $mockIntegration;

    protected function setUp()
    {
        $this->leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue', 'getId', 'getUtmTags'])
            ->getMock();

        $this->leadObserver->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $mockUtmTag = $this->getMockBuilder(UtmTag::class)
            ->setMethods(['getUtmSource', 'getDateAdded'])
            ->getMock();

        $mockUtmTag->expects($this->any())
            ->method('getUtmSource')
            ->willReturn('a_source_tag');

        $mockUtmTag->expects($this->any())
            ->method('getDateAdded')
            ->willReturn(new \DateTime());

        $this->leadObserver->expects($this->any())
            ->method('getUtmTags')
            ->willReturn([$mockUtmTag]);

        $this->mockIntegration = $this->getMockBuilder(TrustedFormIntegration::class)
            ->setMethods(['getFingers', 'getKeys', 'makeRequest'])
            ->getMock();

        $this->mockIntegration->expects($this->any())
            ->method('getFingers')
            ->willReturn([]);

        $this->mockIntegration->expects($this->any())
            ->method('getKeys')
            ->willReturn(['username' => 'username', 'password' => 'password']);

        $mockLogger = $this->createMock(Logger::class);
        $this->mockIntegration->setLogger($mockLogger);
    }

    public function testDoEnhancement20XResponse()
    {
        $created_at = '2019-04-01 00:00:00';
        $expires_at = '2024-04-01 00:00:00';

        $this->leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['xx_trusted_form_cert_url', null, 'https://cert.trustedform.com'],
                ['trusted_form_created_at', null, null],
            ]);

        $this->leadObserver->expects($this->exactly(5))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['trusted_form_created_at', $created_at, null],
                ['trusted_form_expires_at', $expires_at, null],
                ['trusted_form_share_url', 'https://share.trustedform.com', null]
            );

        $this->mockIntegration->expects($this->once())
            ->method('getFingers')
            ->willReturn(['email' => 'dummy@example.com', 'phone' => '9876543210']);

        $response       = new \stdClass();
        $response->code = 200;
        $response->body = '{"created_at":"'.$created_at.'","xx_trusted_form_cert_url":"https://cert.trustedform.com","expires_at":"'.$expires_at.'","share_url":"https://share.trustedform.com"}';
        $this->mockIntegration->expects($this->any())
            ->method('makeRequest')
            ->willReturn($response);

        $this->assertTrue($this->mockIntegration->doEnhancement($this->leadObserver), 'Unexpected result.');
    }

    public function testDoEnhancement404Response()
    {
        $this->leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['xx_trusted_form_cert_url', null, 'https://cert.trustedform.com'],
                ['trusted_form_created_at', null, null],
            ]);

        $response       = new \stdClass();
        $response->code = 404;
        $response->body = '{"message":"Contact not found"}';
        $this->mockIntegration->expects($this->once())
            ->method('makeRequest')
            ->willReturn($response);

        $this->assertFalse($this->mockIntegration->doEnhancement($this->leadObserver), 'Unexpected result.');
    }

    public function testDoEnhancement40XResponse()
    {
        $this->leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['xx_trusted_form_cert_url', null, 'https://cert.trustedform.com'],
                ['trusted_form_created_at', null, null],
            ]);

        $response       = new \stdClass();
        $response->code = 401;
        $response->body = '{"message":"Failed to authenticate"}';
        $this->mockIntegration->expects($this->once())
            ->method('makeRequest')
            ->willReturn($response);

        $this->assertFalse($this->mockIntegration->doEnhancement($this->leadObserver), 'Unexpected result.');
    }

    public function testDoEnhancement50XResponse()
    {
        $this->leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['xx_trusted_form_cert_url', null, 'https://cert.trustedform.com'],
                ['trusted_form_created_at', null, null],
            ]);

        $response       = new \stdClass();
        $response->code = 503;
        $response->body = '{"message":"Please try again"}';
        $this->mockIntegration->expects($this->exactly(5))
            ->method('makeRequest')
            ->willReturn($response);

        $this->assertFalse($this->mockIntegration->doEnhancement($this->leadObserver), 'Unexpected result.');
    }
}
