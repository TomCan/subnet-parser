# TomCan\SubnetParser

TomCan's subnet parser and merger library can parse, merge/aggregate IP subnets into a structured `Subnet` object.

## Usage

Add the library to your project using composer
```
composer require tomcan/subnet-parser
```
Then just instantiate it
```php
$parser = new TomCan\SubnetParser\SubnetParser();
// Parse ranges into subnets
$subnets = $parser->parseRanges(['192.168.1.0/24', '192.168.2.0/24', '192.168.0.0/24']);
// Merge subnets
$subnets = $parser->mergeSubnets($subnets);
foreach ($subnets as $subnet) {
    echo 'Network: '.$subnet->network.'/'.$subnet->prefixlenght.PHP_EOL;
}
```

### Parsing

This library allows you to parse subnets in various notations. The library will try and figure it out.

```
192.168.0.1
-> 192.168.0.1/32
192.168.1.1/24
-> 192.168.1.0/24
192.168.2.1/255.255.255.0
-> 192.168.2.0/24
192.168.3.0-192.168.3.255
-> 192.168.3.0/24
192.168.4.0-192.168.5.128
-> 192.168.4.0/24 + 192.168.5.0/25 + 192.168.5.128/32
```

### Merging

It can also merge an array of subnets into the least possible amount of subnets.

```
192.168.0.0/24
192.168.1.0/24
-> 192.168.0.0/23
192.168.0.0/24
192.168.1.0/24
192.168.2.0/24
192.168.3.0/25
192.168.3.128/25
-> 192.168.0.0/22
```

### The `Subnet` object

The `Subnet` object is a very simple but structured object containing the network address and prefix length. 
It also contains the last IP of the range, as well as the preceding IP (last IP of previous subnet) and the next IP (first IP of the next subnet).
IPs are available in their textual representation, as well as in their packed format.
