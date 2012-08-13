<?php

/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace archivist;

/**
 * Package reader
 * Reads a package script
 */
class Reader
{

    /**
     * @var array List of files into this package
     * 
     * {
     *      filename : {
     *          0 => compressed
     *          1 => offset
     *          2 => size
     *          3 => original file size (if was compressed)
     *      }
     * }
     * 
     */
    protected $data = array();

    /**
     * Current file handle
     * @var ressource
     */
    protected $hFile;

    /**
     * The halt token offset 
     */
    protected $oHalt;
    /**
     * The data offset 
     */
    protected $oData;

    /**
     * Initialize the specified archive file
     * @param string $filename
     */
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new \Exception(
                'Unable to find file : ' . $filename
            );
        }
        $this->hFile = fopen( $filename, 'rb' );
        if ( !$this->hFile ) {
            throw new \Exception(
                'Unable to read archive : ' . $filename 
            );
        }
        $this->oHalt = $this->findPos( '__halt_' . 'compiler();' );
        if ( $this->oHalt === false ) {
            throw new \OutOfBoundsException(
                'Unable to locate the halt_compiler statement'
            );
        }
        $oDico = $this->oHalt + 22;
        $size = $this->extractString( $oDico - 4, 4 );
        $contents = unpack('I', $size);
        $this->oData = $oDico + $contents[1];
        $this->data = unserialize(
            $this->extractString(
                $oDico, 
                $this->oData - $oDico
            )
        );
    }

    /**
     * Close the current archive
     */
    public function __destruct() {
        if ( $this->hFile ) {
            fclose( $this->hFile );
        }
    }

    /**
     * Find a position in a file
     * @param string $text
     * @param float $offset
     * @return mixed
     */
    protected function findPos($text, $offset = 0)
    {
        $pos = $offset;
        fseek($this->hFile, $pos);
        while (!feof($this->hFile)) {
            $line = fread($this->hFile, 1024);
            $found = strpos($line, $text);
            if ($found !== false) {
                return $pos + $found;
           }
           $pos += strlen($line);
       }
       return false;
    }
    
    /**
     * Extract the specified portion of text
     * @param float $offset
     * @param float $size 
     * @return string
     */
    protected function extractString( $offset, $size ) 
    {
        fseek($this->hFile, $offset);
        return fread( $this->hFile, $size );
    }
    
    /**
     * Returns a list of file names
     * @return array
     */
    public function getFiles( ) {
        return array_keys( $this->data );
    }
    
    /**
     * Retrieves the specified file contents
     * @param string $name 
     * @return string
     * @throws \OutOfRangeException
     */
    public function getFile( $name ) {
        if ( isset( $this->data[$name] ) ) {
            $data = $this->data[$name];
            if ( $data[0] !== true ) {
                return $this->extractString(
                    $this->oData + $data[1], $data[2]
                );
            } else {
                return gzuncompress(
                    $this->extractString(
                        $this->oData + $data[1], $data[2]
                    ),
                    $data[3]
                );
            }
        } else {
            throw new \OutOfRangeException(
                'Undefined file : ' . $name
            );
        }
    }
    
    /**
     * Write the specified file to the specified target
     * @param string $name
     * @param string $to 
     * @return Archive
     */
    public function extractFile( $name, $to = './' ) {
        if (substr($to, -1) != '/' ) {
            $to .= '/';
        }
        $target = dirname($to.$name);
        if ( !is_dir($target) ) {
            mkdir($target, 0777, true);
        }
        file_put_contents(
            $to.$name,
            $this->getFile($name)
        );
        return $this;
    }
    
    /**
     * Extract all files to the specified target
     * @param string $to 
     */
    public function extractFiles( $to = './' ) {
        foreach( $this->getFiles() as $name ) {
            $this->extractFile($name, $to);
        }
    }

}