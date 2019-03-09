<?php

/**

 * Please view attribution.php for detailed licensing info on all files within this directory

 **/
class OW_Log
{
    const TYPE = 'type';
    const KEY = 'key';
    const TIME_STAMP = 'timeStamp';
    const MESSAGE = 'message';

    /**
     * Class instances.
     *
     * @var array
     */
    private static $classInstances;

    /**
     * Returns logger object.
     *
     * @param string $type
     * @return OW_Log
     */
    public static function getInstance( $type )
    {
        if ( self::$classInstances === null )
        {
            self::$classInstances = array();
        }

        if ( empty(self::$classInstances[$type]) )
        {
            self::$classInstances[$type] = new self($type);
        }

        return self::$classInstances[$type];
    }
    /**
     * Log type.
     *
     * @var string
     */
    private $type;
    /**
     * Log entries.
     *
     * @var array
     */
    private $entries = array();
    /**
     * @var OW_LogWriter
     */
    private $logWriter;

    /**
     * Constructor.
     *
     * @param string $type
     * @param OW_LogWriter $logWriter
     */
    private function __construct( $type )
    {
        $this->type = $type;
        $this->logWriter = new BASE_CLASS_DbLogWriter();
        OW::getEventManager()->bind('core.exit', array($this, 'writeLog'));
        OW::getEventManager()->bind('core.emergency_exit', array($this, 'writeLog'));
    }

    /**
     * Adds log entry.
     *
     * @param string $message
     * @param string $key
     */
    public function addEntry( $message, $key = null )
    {
        $this->entries[] = array(self::TYPE => $this->type, self::KEY => $key, self::MESSAGE => $message, self::TIME_STAMP => time());        
    }

    /**
     * Returns all log entries.
     * 
     * @return array
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Sets log writer.
     *
     * @param OW_LogWriter $logWriter
     */
    public function setLogWriter( OW_LogWriter $logWriter )
    {
        $this->logWriter = $logWriter;
    }

    /**
     * 
     */
    public function writeLog()
    {
        if ( $this->logWriter !== null && !empty($this->entries))
        {
            $this->logWriter->processEntries($this->entries);
            $this->entries = array();
        }
    }
}
