<?php

namespace TomCan\SubnetParser;

class SubnetParser
{
    /**
     * Try and convert anything that looks like and IP range into a Subnet object
     *   Single ip 1.2.3.4 (treats as /32 or /128)
     *   CIDR: 1.2.3.4/32
     *   Full subnet mask: 192.168.1.0/255.255.255.0
     *   IP range: 192.168.0.0 - 192.168.0.127 -> 192.168.0.0/25
     *   IP range: 192.168.0.0 - 192.168.1.127 -> 192.168.0.0/24 + 192.168.1.0/25
     *
     * @param string[] $ranges
     * @return Subnet[]
     */
    public function parseRanges(array $ranges): array
    {
        $subnets = [];
        foreach ($ranges as $range) {
            $range = trim($range);
            if ($range) {
                // is it a single IP
                if (filter_var($range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    // Single IPv4, treat as /32
                    $subnets[] = new Subnet(inet_pton($range), 32);
                } elseif (filter_var($range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    // Single IPv6, treat as /128
                    $subnets[] = new Subnet(inet_pton($range), 128);
                } elseif (strpos($range, '/') !== false) {
                    // Possible CIDR or subnet mask
                    $split = explode('/', $range);
                    if (count($split) == 2) {
                        $ip = trim($split[0]);
                        $mask = trim($split[1]);
                        // Check if first part is valid IP
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            // IPv4, check if mask is numeric
                            if (is_numeric($mask)) {
                                // Mask must be integer range 0-32
                                if (intval($mask) == $mask && intval($mask) >= 0 && intval($mask) <= 32) {
                                    // Convert into Subnet
                                    $subnets[] = new Subnet(inet_pton($ip), $mask);
                                }
                            } else {
                                // check if mask is IP address format (eg. 255.255.0.0)
                                if (filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                    // Convert to binary
                                    $binary = str_pad(decbin(ip2long($mask)), 32, '0', STR_PAD_LEFT);
                                    // All 1s (if any) need to be at the start, and all zeroes (if any) at the end
                                    if (preg_match('/^1*0*$/', $binary)) {
                                        // Valid subnet mask, get number of 1 bits
                                        $idx = strpos($binary, '0');
                                        if ($idx === false) {
                                            // All 1s /32 subnet mask
                                            $idx = 32;
                                        }
                                        // Convert to Subnet
                                        $subnets[] = new Subnet(inet_pton($ip), $idx);
                                    }
                                }
                            }
                        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            // IPv6, check if mask is numeric, integer and range 0-128
                            if (is_numeric($mask) && intval($mask) == $mask && intval($mask) >= 0 && intval($mask) <= 128) {
                                $subnets[] = new Subnet(inet_pton($ip), $mask);
                            }
                        }
                    }
                } elseif (strpos($range, '-') !== false) {
                    // Possible x - y range
                    $split = explode('-', $range);
                    if (count($split) == 2) {
                        // trim values
                        $split[0] = trim($split[0]);
                        $split[1] = trim($split[1]);
                        if (
                            (filter_var($split[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($split[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                            || (filter_var($split[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($split[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
                        ) {
                            // convert to packed and sort
                            $ips = [inet_pton($split[0]), inet_pton($split[1])];
                            sort($ips);
                            // get all subnets for this range
                            $subnets = array_merge($subnets, $this->subnetsFromRange($ips[0], $ips[1]));
                        }

                    }
                }

            }
        }

        return $subnets;
    }

    /**
     * Convert a range of any 2 IP address of the same type (IPv4 or IPv6) into an array of Subnets
     *   that contain all the IPs in the given range. The start and end address do not need to align
     *   on CIDR boundaries, the script will add additional subnets for that.
     *
     * eg: 192.168.0.7 - 192.168.0.66
     *       192.168.0.7/32
     *       192.168.0.8/29
     *       192.168.0.16/28
     *       192.168.0.32/27
     *       192.168.0.64/31
     *       192.168.0.66/32
     *
     * @param string $startAddress_h
     * @param string $endAddress_h
     * @return Subnet[]
     */
    public function subnetsFromRange(string $startAddress_h, string $endAddress_h): array
    {
        $prefixLength = 0;
        for ($i = 0; $i < strlen($startAddress_h); $i++) {
            if ($startAddress_h[$i] == $endAddress_h[$i]) {
                $prefixLength += 8;
            } else {
                $startBin = str_pad(decbin(ord($startAddress_h[$i])), 8, '0', STR_PAD_LEFT);
                $endBin = str_pad(decbin(ord($endAddress_h[$i])), 8, '0', STR_PAD_LEFT);
                for ($j = 0; $j < strlen($startBin); $j++) {
                    if ($startBin[$j] == $endBin[$j]) {
                        $prefixLength += 1;
                    } else {
                        break;
                    }
                }
                break;
            }
        }

        $subnets = [];
        while ($prefixLength <= 128) {
            // max possible subnet, see if $from is first address
            echo 'C '.inet_ntop($startAddress_h).'/'.$prefixLength.PHP_EOL;
            $subnet = new Subnet($startAddress_h, $prefixLength);
            if ($subnet->network_h == $startAddress_h) {
                // start matches
                if ($subnet->last_h <= $endAddress_h) {
                    // fits
                    $subnets[] = $subnet;
                    if ($subnet->last_h != $endAddress_h) {
                        // still some left
                        $after = $this->subnetsFromRange($subnet->next_h, $endAddress_h);
                        $subnets = array_merge($subnets, $after);
                    }
                    break;
                } else {
                    $prefixLength += 1;
                }
            } else {
                // try next address
                $next = new Subnet($subnet->next_h, $prefixLength);
                echo 'N '.inet_ntop($subnet->next_h).'/'.$prefixLength.PHP_EOL;
                if ($next->last_h <= $endAddress_h) {
                    // fits
                    $subnets[] = $next;
                    // find subnets before
                    $before = $this->subnetsFromRange($startAddress_h, $next->prev_h);
                    $subnets = array_merge($subnets, $before);
                    if ($next->next_h <= $endAddress_h) {
                        // find subnets after
                        $after = $this->subnetsFromRange($next->next_h, $endAddress_h);
                        $subnets = array_merge($subnets, $after);
                    }
                    break;
                } else {
                    // try smaller subnet
                    $prefixLength += 1;
                }
            }
        }

        usort($subnets, function ($a, $b) {
            if ($a->network_h == $b->network_h) {
                return $a->prefixlength == $b->network_h ? 0 : ($a->prefixlength > $b->network_h ? 1 : -1);
            } else {
                return $a->network_h > $b->network_h ? 1 : -1;
            }
        });

        return $subnets;
    }

    /**
     * Merge overlapping / adjacent subnets
     *
     * @param Subnets[] $subnets
     * @return Subnets[]
     */
    public function mergeSubnets(array $subnets): array
    {
        // init variables
        $merged = $subnets;
        $retryMerge = true;

        while ($retryMerge) {
            // transfer from previous run
            $subnets = $merged;
            $merged = [];
            $retryMerge = false;
            // sort by type and network_h
            usort($subnets, function ($a, $b) {
                if ($a->type == $b->type) {
                    return $a->network_h > $b->network_h ? 1 : -1;
                } else {
                    return $a->type > $b->type ? 1 : -1;
                }
            });

            /** @var ?Subnet $prev */
            $prev = null;
            foreach ($subnets as $subnet) {
                if ($prev === null) {
                    // first, always insert
                    $merged[] = $subnet;
                    $prev = $subnet;
                } else {
                    if ($subnet->type == $prev->type) {
                        if ($subnet->network_h < $prev->last_h) {
                            // subnet completely fits in previous, ignore
                            continue;
                        } elseif ($subnet->network_h == $prev->next_h) {
                            // neighbouring subnets, check for merge
                            if ($subnet->prefixlength == $prev->prefixlength) {
                                // check if prev network is same as network with prefixlength -1
                                $larger = new Subnet($prev->network_h, $prev->prefixlength);
                                if ($larger->network == $prev->network) {
                                    // same network, merge/update from larger
                                    $prev->prefixlength = $larger->prefixlength;
                                    $prev->prefixlength_h = $larger->prefixlength_h;
                                    $prev->last = $larger->last;
                                    $prev->last_h = $larger->last_h;
                                    $prev->prev = $larger->prev;
                                    $prev->prev_h = $larger->prev_h;
                                    $prev->next = $larger->next;
                                    $prev->next_h = $larger->next_h;
                                    // flag to retry next run
                                    $retryMerge = true;
                                } else {
                                    // not the same network, can't merge
                                    $merged[] = $subnet;
                                    $prev = $subnet;
                                }
                            } else {
                                // not the same prefixlength, can't merge
                                $merged[] = $subnet;
                                $prev = $subnet;
                            }
                        } else {
                            // non-neighbouring subnets, insert and prev
                            $merged[] = $subnet;
                            $prev = $subnet;
                        }
                    }
                }
            }
        }

        return $merged;
    }
}
