<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */
namespace archivist;

/**
 * Archive writer :
 * Enables to write an archive
 */
class Writer extends Reader
{

    /**
     * @var string The bootstrap code
     */
    protected $bootstrap;

    /**
     * @var array List of new files
     */
    protected $add = array();

    /**
     * @var string The output filename
     */
    protected $filename;

    /**
     * Initialize the specified archive file
     * @param string $filename
     */
    public function __construct($filename)
    {
        try {
            parent::__construct( $filename );
        } catch( \OutOfBoundsException $error ) {
            // the current archive does not contains data already
            echo 'Warn : No halt';
            $this->oHalt = filesize($filename);
        }
        $this->filename = $filename;
        // gets the bootstrap code
        $this->bootstrap = $this->extractString( 0, $this->oHalt );
    }

    /**
     * Flush the archive contents
     */
    public function __destruct()
    {
        $closeTag = strrpos($this->bootstrap, '?>');
        $openTag = strrpos($this->bootstrap, '<?');
        if ( $openTag === false || $closeTag > $openTag ) {
            var_dump($openTag, $closeTag);
            $this->bootstrap .= '<?php ';
        }
        $out = fopen( $this->filename . '.tmp', 'w+b' );
        fputs( $out, $this->bootstrap );
        fputs( $out, "\n".'__halt_compiler();');
        $dico = $this->data;
        // indexing data
        $offset = 0;
        foreach($dico as $name => $conf ) {
            $dico[$name][1] = $offset;
            $offset += $conf[2];
        }
        // including new data
        $buffers = array();
        foreach( $this->add as $name => $conf ) {
            $buffers[ $name ] = $conf[0] ?
                gzcompress(file_get_contents($conf[1]), 9) : 
                file_get_contents($conf[1])
            ;
            $dico[$name] = array(
                $conf[0],
                $offset,
                strlen($buffers[ $name ]),
                $conf[0] ? filesize($conf[1]) : null
            );
            $offset += $dico[$name][2];
        }
        // writing the dico
        $dicoDump = serialize( $dico );
        fputs( $out, pack('I', strlen($dicoDump) ) );
        fputs( $out, $dicoDump );
        // Writing data
        foreach($dico as $name => $conf ) {
            if ( isset( $buffers[$name] ) ) {
                fputs( $out, $buffers[ $name ] );
            } else {
                fseek($this->hFile, $conf[1]);
                fputs( $out, fread( $this->hFile, $conf[2] ) );
            }
        }
        // closing output
        fclose( $out );
        parent::__destruct();
        rename( $this->filename . '.tmp' , $this->filename );
    }

    /**
     * Creates a new archive with the default bootstrap
     * @param string $filename
     * @return Writer
     */
    public static function create( $filename ) {
        file_put_contents($filename, '<?php');
        $archive = new self( $filename );
        return $archive
            ->addBootstrap( __DIR__ . '/Reader.php', true )
            ->addBootstrap( __DIR__ . '/Bootstrap.php', true )
        ;
    }

    /**
     * Adds the specified file to the current archive
     * @param string $filename
     * @return Writer
     */
    public function addBootstrap( $filename, $compressed = false ) {
        $tokens = token_get_all(
            file_get_contents($filename)
        );
        if ($tokens[0][0] !== T_OPEN_TAG) {
            throw new Exception(
                'Bad ' . $file . ' format, expecting an OPEN_TAG'
            );
        }
        // check the php tags
        $closeTag = strrpos($this->bootstrap, '?>');
        $openTag = strrpos($this->bootstrap, '<?');
        if ( $openTag === false || $closeTag > $openTag ) {
            $this->bootstrap .= '<?php ';
        }
        $tsize = count( $tokens );
        for( $offset = 1; $offset < $tsize; $offset ++ ) {
            $token = $tokens[ $offset ];
            if ( $compressed ) {
                if ( $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                if ( $token[0] === T_WHITESPACE ) {
                    $this->bootstrap .= ' ';
                    continue;
                }
            }
            $this->bootstrap .= is_array( $token ) ? $token[1] : $token;
        }
        return $this;
    }

    /**
     * Attach the specified file to the current archive
     * @param string $target
     * @param boolean $compressed
     * @param string $basedir
     * @return Writer 
     */
    public function addFile( $target, $compressed = false, $basedir = './' ) {
        $name = $this->getName( $target, $basedir );
        if ( isset( $this->data[$name] ) ) {
            unset( $this->data[$name] );
        }
        $this->add[ $name ] = array(
            $compressed,
            $target
        );
        return $this;
    }

    /**
     * Gets the simplified file name
     * @param type $target
     * @param type $basedir
     * @return type 
     */
    protected function getName( $target, $basedir = './' ) {
        $path = realpath($basedir);
        $target = realpath($target);
        return strtr(substr($target, strlen($path) + 1 ), '\\', '/');
    }
    
    /**
     * Removes the specified file from the package
     * @param string $target
     * @param string $basedir 
     * @return Writer
     */
    public function removeFile( $target, $basedir = './' ) {
        $name = $this->getName( $target, $basedir );
        if ( isset( $this->add[$name] ) ) {
            unset( $this->add[$name] );
        }
        if ( isset( $this->data[$name] ) ) {
            unset( $this->data[$name] );
        }
        return $this;
    }
}
