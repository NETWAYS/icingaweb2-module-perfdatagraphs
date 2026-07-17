<?php

namespace Tests\Icinga\Module\Perfdatagraphs;

use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;

use PHPUnit\Framework\TestCase;

final class PerfdataRequestTest extends TestCase
{
    public function test_perfdatarequest()
    {
        $pfr = new PerfdataRequest(
            hostName: "host",
            serviceName: "service",
            checkCommand: "check",
            checkInterval: 60,
            duration: "PT12H",
            isHostCheck: true,
            includeMetrics: [],
            excludeMetrics: [],
        );

        $this->assertEquals("host", $pfr->getHostname());
        $this->assertEquals("service", $pfr->getServicename());
        $this->assertEquals("check", $pfr->getCheckcommand());
        $this->assertEquals("PT12H", $pfr->getDuration());
        $this->assertEquals(60, $pfr->getCheckInterval());
        $this->assertTrue($pfr->isHostCheck());
        $this->assertEquals([], $pfr->getIncludeMetrics());
        $this->assertEquals([], $pfr->getExcludeMetrics());

        $pfr = new PerfdataRequest(
            hostName: "host",
            serviceName: "service",
            checkCommand: "check",
            checkInterval: 60,
            duration: "PT12H",
            isHostCheck: false,
            includeMetrics: ["foobar"],
            excludeMetrics: ["barfoo"],
        );

        $this->assertEquals("host", $pfr->getHostname());
        $this->assertEquals("service", $pfr->getServicename());
        $this->assertEquals("check", $pfr->getCheckcommand());
        $this->assertEquals("PT12H", $pfr->getDuration());
        $this->assertEquals(60, $pfr->getCheckInterval());
        $this->assertFalse($pfr->isHostCheck());
        $this->assertEquals(["foobar"], $pfr->getIncludeMetrics());
        $this->assertEquals(["barfoo"], $pfr->getExcludeMetrics());
    }
}
