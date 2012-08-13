<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
// DEFINES THE DEFAULT BOOTSTRAP SCRIPT
$GLOBALS['package'] = new Reader( __FILE__ );
$GLOBALS['includes'] = array();
function includeFile( $target ) {
    if ( !isset($GLOBALS['includes'][$target]) ) {
        if ( !is_dir( __DIR__ . '/tmp/' )) {
            mkdir(__DIR__ . '/tmp/');
        }
        $file = strtr( $target, '\\/', '__' );
        $GLOBALS['includes'][$target] =  __DIR__ . '/tmp/' . $file;
        if ( !file_exists($GLOBALS['includes'][$target]) ) {
            $GLOBALS['package']->extractFileAs(
                $target, $GLOBALS['includes'][$target]
            );
        }
    }
    return include($GLOBALS['includes'][$target]);
}
// Handling a commandline boostrap
if ( isset( $_SERVER['argv'][1] ) ) {
    includeFile( $_SERVER['argv'][1] );
} else {
    includeFile( 'index.php' );
}