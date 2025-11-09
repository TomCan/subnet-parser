<?php

use PHPUnit\Framework\TestCase;
use TomCan\SubnetParser\SubnetParser;
use TomCan\SubnetParser\Subnet;

class SubnetMergerTest extends TestCase
{
    private SubnetParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SubnetParser();
    }

    /**
     * Test single subnet merge
     */
    public function testMergeSubnets(): void
    {
        // 2 adjacent subnets in the same parent subnet
        $subnets = [
            new Subnet(inet_pton('192.168.1.0'), 25),
            new Subnet(inet_pton('192.168.1.128'), 25),
        ];

        $result = $this->parser->mergeSubnets($subnets);

        // The two /25 subnets should be merged into a /24
        $this->assertEquals(1, count($result));
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
    }

    /**
     * Test adjacent but not within same parent subnet
     */
    public function testMergeSubnetsDiffParent(): void
    {
        // 2 adjacent subnets in the same parent subnet
        $subnets = [
            new Subnet(inet_pton('192.168.1.0'), 24),
            new Subnet(inet_pton('192.168.2.0'), 24),
        ];

        $result = $this->parser->mergeSubnets($subnets);

        // Should just have the 2 subnets
        $this->assertEquals(2, count($result));
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
        $this->assertEquals('192.168.2.0', $result[1]->network);
        $this->assertEquals(24, $result[1]->prefixlength);
    }

    /**
     * Test recursive subnets merges
     */
    public function testMergeSubnetsRecursive(): void
    {
        // One /24 + * /25 that are all adjecent and form a /23
        $subnets = [
            new Subnet(inet_pton('192.168.0.0'), 24), // 192.168.1.0/25
            new Subnet(inet_pton('192.168.1.0'), 25), // 192.168.1.0/25
            new Subnet(inet_pton('192.168.1.128'), 25), // 192.168.1.128/25
        ];

        $result = $this->parser->mergeSubnets($subnets);

        // The /24 and the two /25 subnets should be merged into a single /23
        $this->assertEquals(1, count($result));
        $this->assertEquals('192.168.0.0', $result[0]->network);
        $this->assertEquals(23, $result[0]->prefixlength);
    }
}
