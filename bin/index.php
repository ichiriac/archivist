<?php

// REQUIRE THE OPTIONS HELPER
includeFile( 'GetOpts.php' );

// THE ARCHIVIST BOOTSTAP SCRIPT
echo <<<EOT
Archivist Script - Version 0.1.0
This script will help you to deploy any archive from an archivist repository.

EOT;

$options = get_opts();

var_dump( $options );