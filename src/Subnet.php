<?php

namespace TomCan\SubnetParser;

class Subnet
{
    public int $type = 4;
    public string $network;
    public string $network_h;
    public int $prefixlength;
    public string $prefixlength_h;
    public string $last;
    public string $last_h;
    public ?string $prev = null;
    public ?string $prev_h = null;
    public ?string $next = null;
    public ?string $next_h = null;

    /**
     * @param int $type
     * @param string $network
     * @param string $cidr
     * @param string $broadcast
     * @param string $next
     */
    public function __construct(string $network_h, int $prefixLength)
    {
        if (strlen($network_h) == 4) {
            $this->type = 4;
        } elseif (strlen($network_h) == 16) {
            $this->type = 6;
        } else {
            throw new Exception("Invalid network h length for ".bin2hex($network_h));
        }
        $this->prefixlength_h = substr('00'.dechex($prefixLength), -2);

        $this->network_h = $this->applyMask($network_h, $prefixLength, 0);
        $this->network = inet_ntop($this->network_h);
        $this->prefixlength = $prefixLength;

        $this->last_h = $this->applyMask($network_h, $prefixLength, 1);
        $this->last = inet_ntop($this->last_h);

        $this->prev_h = $this->getPrev($this->network_h);
        if ($this->prev_h) {
            $this->prev = inet_ntop($this->prev_h);
        } else {
            $this->prev = null;
        }

        $this->next_h = $this->getNext($this->last_h);
        if ($this->next_h) {
            $this->next = inet_ntop($this->next_h);
        } else {
            $this->next = null;
        }
    }

    private function getNext(string $packed): ?string
    {
        for ($i = strlen($packed) - 1; $i >= 0; $i--) {
            if (ord($packed[$i]) == 255) {
                $packed[$i] = chr(0);
            } else {
                $packed[$i] = chr(ord($packed[$i]) + 1);
                return $packed;
            }
        }

        return null;
    }

    private function getPrev(string $packed): ?string
    {
        for ($i = strlen($packed) - 1; $i >= 0; $i--) {
            if (ord($packed[$i]) == 0) {
                $packed[$i] = chr(255);
            } else {
                $packed[$i] = chr(ord($packed[$i]) - 1);
                return $packed;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return $this->network.'/'.$this->prefixlength;
    }

    private function applyMask(string $address, int $prefixLength, int $fill = 0): string
    {
        $return = '';
        for ($i = 0; $i < strlen($address); $i++) {
            if ($prefixLength >= 8) {
                $return .= $address[$i];
                $prefixLength -= 8;
            } elseif ($prefixLength == 0) {
                $return .= chr($fill ? 255 : 0);
            } else {
                // set to 0 by shifting left then right, then fill
                $return .= chr(
                    (ord($address[$i]) >> (8 - $prefixLength) << (8 - $prefixLength))
                    | ((pow(2, 8 - $prefixLength) - 1) * $fill)
                );
                $prefixLength = 0;
            }
        }

        return $return;
    }
}
