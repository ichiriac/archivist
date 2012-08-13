<?php
/**
 * Reads automatically arguments options
 * @return array 
 * @see https://gist.github.com/3342610
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