<?php

use PHPUnit\Framework\TestCase;
use TomCan\SubnetParser\SubnetParser;
use TomCan\SubnetParser\Subnet;

class SubnetParserTest extends TestCase
{
    private SubnetParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SubnetParser();
    }

    /**
     * Test parsing single IPv4 addresses
     */
    public function testParseSingleIPv4Address(): void
    {
        $ranges = ['192.168.1.100'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Subnet::class, $result[0]);
        $this->assertEquals('192.168.1.100', $result[0]->network);
        $this->assertEquals(32, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);
    }

    /**
     * Test parsing single IPv6 addresses
     */
    public function testParseSingleIPv6Address(): void
    {
        $ranges = ['2001:db8::1'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Subnet::class, $result[0]);
        $this->assertEquals('2001:db8::1', $result[0]->network);
        $this->assertEquals(128, $result[0]->prefixlength);
        $this->assertEquals(6, $result[0]->type);
    }

    /**
     * Test parsing IPv4 CIDR notation
     */
    public function testParseIPv4CIDR(): void
    {
        $ranges = ['192.168.1.0/24'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);
    }

    /**
     * Test parsing IPv4 CIDR notation of IP in middle of subnet
     */
    public function testParseIPv4CIDRMiddle(): void
    {
        $ranges = ['192.168.1.5/24'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);
    }

    /**
     * Test parsing IPv6 CIDR notation
     */
    public function testParseIPv6CIDR(): void
    {
        $ranges = ['2001:db8::/32'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertEquals('2001:db8::', $result[0]->network);
        $this->assertEquals(32, $result[0]->prefixlength);
        $this->assertEquals(6, $result[0]->type);
    }

    /**
     * Test parsing IPv6 CIDR notation of IP in middle of subnet
     */
    public function testParseIPv6CIDRMiddle(): void
    {
        $ranges = ['2001:db8::5/32'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertEquals('2001:db8::', $result[0]->network);
        $this->assertEquals(32, $result[0]->prefixlength);
        $this->assertEquals(6, $result[0]->type);
    }

    /**
     * Test parsing IPv4 with subnet mask notation
     */
    public function testParseIPv4WithSubnetMask(): void
    {
        $ranges = ['192.168.1.0/255.255.255.0'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(1, $result);
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);
    }

    /**
     * Test parsing various subnet masks
     */
    public function testParseVariousSubnetMasks(): void
    {
        $testCases = [
            ['10.0.0.0/0.0.0.0', 0],
            ['10.0.0.0/255.0.0.0', 8],
            ['10.0.0.0/255.128.0.0', 9],
            ['10.0.0.0/255.255.0.0', 16],
            ['10.0.0.0/255.255.255.128', 25],
            ['10.0.0.0/255.255.255.192', 26],
            ['10.0.0.0/255.255.255.224', 27],
            ['10.0.0.0/255.255.255.240', 28],
            ['10.0.0.0/255.255.255.248', 29],
            ['10.0.0.0/255.255.255.252', 30],
            ['10.0.0.0/255.255.255.254', 31],
            ['10.0.0.0/255.255.255.255', 32],
        ];

        foreach ($testCases as [$input, $expectedPrefix]) {
            $result = $this->parser->parseRanges([$input]);
            $this->assertCount(1, $result);
            $this->assertEquals($expectedPrefix, $result[0]->prefixlength, "Failed for input: $input");
        }
    }

    /**
     * Test parsing IPv4 ranges
     */
    public function testParseIPv4Range(): void
    {
        $ranges = ['192.168.1.0 - 192.168.1.255'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertEquals(1, count($result));
        $this->assertEquals('192.168.1.0', $result[0]->network);
        $this->assertEquals(24, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);
    }

    /**
     * Test parsing IPv4 ranges crossing subnet boundaries
     */
    public function testParseIPv4RangeMultiple(): void
    {
        $ranges = ['192.168.1.128 - 192.168.3.63'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertEquals(3, count($result));

        $this->assertEquals('192.168.1.128', $result[0]->network);
        $this->assertEquals(25, $result[0]->prefixlength);
        $this->assertEquals(4, $result[0]->type);

        $this->assertEquals('192.168.2.0', $result[1]->network);
        $this->assertEquals(24, $result[1]->prefixlength);
        $this->assertEquals(4, $result[1]->type);

        $this->assertEquals('192.168.3.0', $result[2]->network);
        $this->assertEquals(26, $result[2]->prefixlength);
        $this->assertEquals(4, $result[2]->type);
    }

    /**
     * Test parsing IPv6 ranges
     */
    public function testParseIPv6Range(): void
    {
        $ranges = ['2001:db8::0 - 2001:db8::ffff'];
        $result = $this->parser->parseRanges($ranges);

        $this->assertGreaterThan(0, count($result));
        // Check that all results are Subnet objects
        foreach ($result as $subnet) {
            $this->assertInstanceOf(Subnet::class, $subnet);
            $this->assertEquals(6, $subnet->type);
        }
    }

    /**
     * Test parsing multiple ranges
     */
    public function testParseMultipleRanges(): void
    {
        $ranges = [
            '192.168.1.0/24',
            '10.0.0.1',
            '2001:db8::/32',
            '172.16.0.0 - 172.16.0.127'
        ];
        $result = $this->parser->parseRanges($ranges);

        $this->assertEquals(4, count($result));

        // Check that we have both IPv4 and IPv6 subnets
        $ipv4Count = 0;
        $ipv6Count = 0;
        foreach ($result as $subnet) {
            $this->assertInstanceOf(Subnet::class, $subnet);
            if ($subnet->type === 4) {
                $ipv4Count++;
            } elseif ($subnet->type === 6) {
                $ipv6Count++;
            }
        }

        $this->assertEquals(3, $ipv4Count);
        $this->assertEquals(1, $ipv6Count);
    }

    /**
     * Test parsing empty and whitespace ranges
     */
    public function testParseEmptyAndWhitespaceRanges(): void
    {
        $ranges = ['', '   ', "\t", "\n"];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(0, $result);
    }

    /**
     * Test parsing invalid IP addresses
     */
    public function testParseInvalidIPAddresses(): void
    {
        $ranges = [
            '256.256.256.256',
            '192.168.1',
            '192.168.1.1.1',
            'not.an.ip.address',
            '2001:db8:::1',
            'invalid:ipv6:address'
        ];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(0, $result);
    }

    /**
     * Test parsing invalid CIDR notation
     */
    public function testParseInvalidCIDR(): void
    {
        $ranges = [
            '192.168.1.0/33',  // Invalid prefix length for IPv4
            '192.168.1.0/-1',  // Negative prefix length
            '192.168.1.0/abc', // Non-numeric prefix length
            '2001:db8::/129',  // Invalid prefix length for IPv6
            '192.168.1.0/',    // Missing prefix length
        ];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(0, $result);
    }

    /**
     * Test parsing invalid subnet masks
     */
    public function testParseInvalidSubnetMasks(): void
    {
        $ranges = [
            '192.168.1.0/255.255.255.1',   // Invalid mask (not contiguous)
            '192.168.1.0/255.255.128.255', // Invalid mask (holes)
            '192.168.1.0/256.0.0.0',       // Invalid IP in mask
        ];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(0, $result);
    }

    /**
     * Test parsing invalid IP ranges
     */
    public function testParseInvalidRanges(): void
    {
        $ranges = [
            '192.168.1.0 - 2001:db8::1', // Mixed IPv4 and IPv6
            '192.168.1.0 -',             // Missing end IP
            '- 192.168.1.255',           // Missing start IP
            '192.168.1.0 - - 192.168.1.255', // Multiple dashes
        ];
        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(0, $result);
    }

    /**
     * Test subnetsFromRange method directly
     */
    public function testSubnetsFromRange(): void
    {
        $startIP = inet_pton('192.168.1.10');
        $endIP = inet_pton('192.168.1.20');

        $result = $this->parser->subnetsFromRange($startIP, $endIP);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));

        foreach ($result as $subnet) {
            $this->assertInstanceOf(Subnet::class, $subnet);
        }
    }

    /**
     * Test edge case: /0 and /32 networks
     */
    public function testEdgeCaseNetworks(): void
    {
        $ranges = [
            '0.0.0.0/0',        // Entire IPv4 space
            '192.168.1.1/32',   // Single host
            '::/0',             // Entire IPv6 space
            '2001:db8::1/128'   // Single IPv6 host
        ];

        $result = $this->parser->parseRanges($ranges);

        $this->assertCount(4, $result);

        // Check /0 networks
        $this->assertEquals(0, $result[0]->prefixlength);
        $this->assertEquals(0, $result[2]->prefixlength);

        // Check single host networks
        $this->assertEquals(32, $result[1]->prefixlength);
        $this->assertEquals(128, $result[3]->prefixlength);
    }

    /**
     * Test parsing with mixed valid and invalid ranges
     */
    public function testParseMixedValidInvalidRanges(): void
    {
        $ranges = [
            '192.168.1.0/24',      // Valid
            'invalid.range',       // Invalid
            '10.0.0.1',           // Valid
            '256.256.256.256',    // Invalid
            '2001:db8::/32'       // Valid
        ];

        $result = $this->parser->parseRanges($ranges);

        // Should only get the 3 valid ranges
        $this->assertCount(3, $result);

        foreach ($result as $subnet) {
            $this->assertInstanceOf(Subnet::class, $subnet);
        }
    }

    /**
     * Test parsing ranges with extra whitespace
     */
    public function testParseRangesWithWhitespace(): void
    {
        $ranges = [
            '  192.168.1.0/24  ',
            "\t10.0.0.1\t",
            "  192.168.0.0 - 192.168.0.255  "
        ];

        $result = $this->parser->parseRanges($ranges);

        $this->assertEquals(3, count($result));
    }
}
