<?php


class Logger {
    private $folder;
    private $isStdout;

    public function __construct($folder, $isStdout = false)
    {
        $this->folder = $folder;
        $this->isStdout = $isStdout;
    }

    public static function getLogFilename() {
        return date('Ymd') . '.log';
    }

    public function write($data) {
        $type = gettype($data);
        if ($type == 'object' || $type == 'array') {
            $data = json_encode($data);
        }
        $time = date('c') . "\t";
        $line = $time . $data . "\n";
        file_put_contents($this->folder . '/' . self::getLogFilename(), $line, FILE_APPEND);
        if ($this->isStdout) {
            echo $line;
        }
    }
}
