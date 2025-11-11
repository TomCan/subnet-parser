<?php

use PHPUnit\Framework\TestCase;
use TomCan\SubnetParser\SubnetParser;
use TomCan\SubnetParser\Subnet;

class SubnetTest extends TestCase
{
    /**
     * Test next subnet
     */
    public function testNextSingle(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.254'), 32);
        $this->assertEquals('192.168.1.255', $subnet->next);
    }

    public function testNextSubnet(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.0'), 24);
        $this->assertEquals('192.168.2.0', $subnet->next);
    }

    public function testNextRollOver(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.255'), 32);
        $this->assertEquals('192.168.2.0', $subnet->next);

        $subnet = new Subnet(inet_pton('192.255.255.255'), 32);
        $this->assertEquals('193.0.0.0', $subnet->next);
    }

    public function testNextNull(): void
    {
        $subnet = new Subnet(inet_pton('255.255.255.255'), 32);
        $this->assertNull($subnet->next);
    }

    /**
     * Test prev subnet
     */
    public function testPrevSingle(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.254'), 32);
        $this->assertEquals('192.168.1.253', $subnet->prev);
    }

    public function testPrevSubnet(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.0'), 24);
        $this->assertEquals('192.168.0.255', $subnet->prev);
    }

    public function testPrevRollOver(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.0'), 32);
        $this->assertEquals('192.168.0.255', $subnet->prev);

        $subnet = new Subnet(inet_pton('192.0.0.0'), 32);
        $this->assertEquals('191.255.255.255', $subnet->prev);
    }

    public function testPrevNull(): void
    {
        $subnet = new Subnet(inet_pton('0.0.0.0'), 32);
        $this->assertNull($subnet->prev);
    }
}
