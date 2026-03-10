<?php

use PHPUnit\Framework\TestCase;
use TomCan\SubnetParser\SubnetParser;
use TomCan\SubnetParser\Subnet;

class SubnetTest extends TestCase
{
    /**
     * Test Subnet class
     */
    public function testSingleIp(): void
    {
        $subnet = new Subnet(inet_pton('127.0.0.1'), 32);
        $this->assertEquals('127.0.0.1', $subnet->network);
    }

    public function testSubnetIp(): void
    {
        $subnet = new Subnet(inet_pton('127.0.0.1'), 24);
        $this->assertEquals('127.0.0.0', $subnet->network);
    }

    public function testInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $subnet = new Subnet('invalid', 24);
    }

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

    /**
     * Test is_h parameter (human-readable input)
     */
    public function testIsHFalseIpv4(): void
    {
        $subnet = new Subnet('192.168.1.0', 24, false);
        $this->assertEquals('192.168.1.0', $subnet->network);
        $this->assertEquals('192.168.1.255', $subnet->last);
        $this->assertEquals(4, $subnet->type);
    }

    public function testIsHFalseIpv4Single(): void
    {
        $subnet = new Subnet('10.0.0.1', 32, false);
        $this->assertEquals('10.0.0.1', $subnet->network);
        $this->assertEquals('10.0.0.1', $subnet->last);
    }

    public function testIsHFalseIpv6(): void
    {
        $subnet = new Subnet('2001:db8::', 32, false);
        $this->assertEquals('2001:db8::', $subnet->network);
        $this->assertEquals(6, $subnet->type);
    }

    public function testIsHFalseMatchesDefault(): void
    {
        $a = new Subnet(inet_pton('10.20.30.0'), 24);
        $b = new Subnet('10.20.30.0', 24, false);
        $this->assertEquals($a->network, $b->network);
        $this->assertEquals($a->last, $b->last);
        $this->assertEquals($a->prev, $b->prev);
        $this->assertEquals($a->next, $b->next);
    }

    /**
     * Test full parameter (skip prev/next)
     */
    public function testFullFalseSkipsPrevNext(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.0'), 24, true, false);
        $this->assertEquals('192.168.1.0', $subnet->network);
        $this->assertEquals('192.168.1.255', $subnet->last);
        $this->assertNull($subnet->prev);
        $this->assertNull($subnet->prev_h);
        $this->assertNull($subnet->next);
        $this->assertNull($subnet->next_h);
    }

    public function testFullTrueCalculatesPrevNext(): void
    {
        $subnet = new Subnet(inet_pton('192.168.1.0'), 24, true, true);
        $this->assertEquals('192.168.0.255', $subnet->prev);
        $this->assertEquals('192.168.2.0', $subnet->next);
    }

    public function testIsHFalseWithFullFalse(): void
    {
        $subnet = new Subnet('172.16.0.0', 16, false, false);
        $this->assertEquals('172.16.0.0', $subnet->network);
        $this->assertEquals('172.16.255.255', $subnet->last);
        $this->assertNull($subnet->prev);
        $this->assertNull($subnet->next);
    }
}
