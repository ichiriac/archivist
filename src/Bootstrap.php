<?php

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */

// DEFINES THE DEFAULT BOOTSTRAP SCRIPT
$GLOBALS['package'] = new Reader( __FILE__ );
function includeFile( $target ) {
    return eval(chr(63).'>'.$GLOBALS['package']->getFile( $target ));
}

// Handling a commandline boostrap
if ( isset( $_SERVER['argv'][1] ) ) {
    includeFile( $_SERVER['argv'][1] );
} else {
    includeFile( 'index.php' );
}