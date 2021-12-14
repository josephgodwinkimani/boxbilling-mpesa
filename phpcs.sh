#!/bin/sh

# Download PHP_CodeSniffer using curl
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar

# Download PHP Mess Detector using wget
wget -c https://phpmd.org/static/latest/phpmd.phar