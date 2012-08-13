<?php

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */

require_once __DIR__ . '/../src/Reader.php';
require_once __DIR__ . '/../src/Writer.php';

echo <<<EOT
Archivist Builder Script - Version 0.1.0
This script will help you to build php archives with the archivist boostrap.

EOT;

/**
 * Reads automatically arguments options
 * @return array 
 */
function get_opts() {
    $result = array();
    $key = null;
    $value = null;
    $asize = count($_SERVER['argv']);
    for( $i = 1; $i < $asize; $i++ ) {
        $arg = $_SERVER['argv'][$i];
        if ( $arg[0] === '-' ) {
            if ( $key ) {
                $result[ $key ] = is_null($value) ? false : $value;
            }
            $key = ltrim( $arg, '-' );
            $value = null;
        } else {
            if ( $key ) {
                if ( !$value ) {
                    $value = $arg;
                } else {
                    if ( !is_array($value) ) {
                        $value = array($value);
                    }
                    $value[] = $arg;
                }
            } else {
                $result[] = $arg;
            }
        }
    }
    if ( $key ) {
        $result[ $key ] = is_null($value) ? false : $value;
    }
    return $result;
}

$options = get_opts();

// check the target
if ( !isset( $options['h'] ) ) {
    $target = $_SERVER['argv'][1];
    if ( $options[0] === '-' ) {
        echo "\n".'ERROR : Bad file target : ' . $options[0];
        $options = null;
    }
    if ( 
        empty($options['e']) 
        && empty($options['d']) 
    ) {
        echo "\n".'ERROR : The base-dir parameter is mandatory';
        $options = null;
    }
}

// show the help screen
if ( empty($options) || isset( $options['h'] )) {
    echo <<<EOT

Usage :

  php build.php target-archive.php -r -v -d=../../base-dir/

This example will create a file "target-archive.php" by recursivelly scanning
the specified -d path.

Options : 

    -r      Use a recursive scan over the specified base-path
    -v      Show the package details (verbose)
    -d      Defines the base-path
    -c      Enables the compression
    -e      Extract files to the specified target

EOT;
    exit(0);
}

if ( isset($options['l']) ) {
    // LISTING THE ARCHIVE CONTENTS
    $archive = new archivist\Reader( $options[0] );
    echo 'Reading ' . $options[0] . " contents : \n";
    foreach( $archive->getFiles() as $name ) {
        echo $name . "\n";
    }
} elseif ( isset($options['e']) ) {
    // LISTING THE ARCHIVE CONTENTS
    $archive = new archivist\Reader( $options[0] );
    echo 'Extracting ' . $options[0] . " contents\n";
    $archive->extractFiles( $options['e'] );
} else {
    // create the archive
    $target = archivist\Writer::create( $options[0] );

    // add files
    $it = new RecursiveDirectoryIterator( $options['d'] );
    foreach(new RecursiveIteratorIterator($it) as $file) {
        if ( isset( $options['v'] )) {
            echo 'Adding : ' . $file . "\n";
        }
        $target->addFile(
            $file, isset($options['c']), $options['d']
        );
    }

    echo "\n".'Writing : ' . $options[0] . "...\n";
}