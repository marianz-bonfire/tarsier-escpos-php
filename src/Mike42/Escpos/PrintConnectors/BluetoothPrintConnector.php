<?php declare(strict_types=1);

/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-20 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 * 
 * Created by: marianz-bonfire
 * https://github.com/marianz-bonfire
 */

namespace Mike42\Escpos\PrintConnectors;

use Exception;
use BadMethodCallException;

/**
 * Connector for sending print jobs to bluetooth printers
 */
class BluetoothPrintConnector implements PrintConnector
{
    private $agentPath = __DIR__ . '/Agent/tarsier_bluetooth_agent.exe';
    /**
     * @var array $buffer
     *  Accumulated lines of output for later use.
     */
    private $buffer;

    /**
     * @var int $platform
     *  Platform we're running on, for selecting different commands. See PLATFORM_* constants.
     */
    private $platform;

    /**
     * @var string $printerName
     *  The name of the target printer (eg "Foo Printer").
     */
    private $printerName;

    /**
     * @var array $devices
     *  List of all scanned bluetooth devices.
     */
    private $devices = [];

    /**
     * Represents Linux
     */
    const PLATFORM_LINUX = 0;

    /**
     * Represents Mac
     */
    const PLATFORM_MAC = 1;

    /**
     * Represents Windows
     */
    const PLATFORM_WIN = 2;

    /**
     * Valid local ports.
     */
    const REGEX_LOCAL = "/^(LPT\d|COM\d)$/";

    /**
     * Valid printer name.
     */
    const REGEX_PRINTERNAME = "/^[\d\w-]+(\s[\d\w-]+)*$/";


    const SUCCESS = "success";
    /**
     * @param string $dest
     * @throws BadMethodCallException
     */
    public function __construct($dest)
    {
        $this -> platform = $this -> getCurrentPlatform();
        $this -> buffer = null;

        // Allowed only if we are actually on windows.
        if ($this -> platform !== self::PLATFORM_WIN) {
            throw new BadMethodCallException("Bluetooth PrintConnector can only be " .
                "used to print on a Windows computer.");
        }
        if (preg_match(self::REGEX_PRINTERNAME, $dest) == 1) {
            $this -> printerName = $dest;
        } else {
            throw new BadMethodCallException("Printer '" . $dest . "' is not a valid printer name.");
        }
        $this -> getBluetoothDevices();
        if (!$this -> check($this -> printerName)) {
            throw new BadMethodCallException("Printer '" . $dest . "' is not found in the list.");
        }
        $this -> buffer = [];
    }

    public function __destruct()
    {
        if ($this -> buffer !== null) {
            trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
        }
    }

    public function finalize()
    {
        $data = implode($this -> buffer);
        $this -> buffer = null;
        if ($this -> platform == self::PLATFORM_WIN) {
            $this -> finalizeWin($data);
        } elseif ($this -> platform == self::PLATFORM_LINUX) {
            $this -> finalizeLinux($data);
        } else {
            $this -> finalizeMac($data);
        }
    }

    /**
     * Send job to printer -- platform-specific Linux code.
     *
     * @param string $data Print data
     * @throws Exception
     */
    protected function finalizeLinux($data)
    {
        throw new Exception("Linux printing not implemented.");
    }

    /**
     * Send job to printer -- platform-specific Mac code.
     *
     * @param string $data Print data
     * @throws Exception
     */
    protected function finalizeMac($data)
    {
        throw new Exception("Mac printing not implemented.");
    }

    /**
     * Send data to printer -- platform-specific Windows code.
     *
     * @param string $data
     */
    protected function finalizeWin($data)
    {
        $filename = $this -> createTempFile($data);
        $command = sprintf(
            "{$this->agentPath} --print=%s --path=%s",
            escapeshellarg($this -> printerName),
            escapeshellarg($filename)
        );
        $redactedCommand = $command;

        $outputStr = '';
        $retval = $this -> runCommand($command, $outputStr, $errorStr);
        if ($retval != 0) {
            throw new Exception("Failed to print. Command \"$redactedCommand\" " .
                "failed with exit code $retval: " . trim($errorStr));
        }
        $res = (object) json_decode($outputStr, true);

        if ($res && isset($res->status)) {
            if ($res->status === self::SUCCESS) {
                print($res->message);
            } else {
                throw new Exception($res->message); // print error message from bluetooth agent
            }
        } else {
            throw new Exception('Unexpected response from Bluetooth agent: ' . json_encode($res));
        }
        unlink($filename);
    }

    /**
     * @return string Current platform. Separated out for testing purposes.
     */
    protected function getCurrentPlatform()
    {
        if (PHP_OS == "WINNT") {
            return self::PLATFORM_WIN;
        }
        if (PHP_OS == "Darwin") {
            return self::PLATFORM_MAC;
        }
        return self::PLATFORM_LINUX;
    }

    /**
     * Get the list of bluetooth devices -- platform-specific Windows code.
     * This scanner does not filter a printer device type, it scanned all bluetooth
     * registered in Windows system regardless of the type such AUDIO, MOBILE, etc.
     *
     */
    protected function getBluetoothDevices()
    {
        $command = "{$this->agentPath} --list";
        $outputStr = '';
        $retval = $this -> runCommand($command, $outputStr, $errorStr);
        if ($retval != 0) {
            throw new Exception("Failed to print. Command \"$command\" " .
                "failed with exit code $retval: " . trim($errorStr));
        }
        $res = (object) json_decode($outputStr, true);

        if ($res && isset($res->status)) {
            if ($res->status === self::SUCCESS) {
                $this -> devices = isset($res->devices) ? json_decode($res->devices) : [];
            } else {
                throw new Exception($res->message); // print error message from bluetooth agent
            }
        } else {
            throw new Exception('Unexpected response from Bluetooth agent: ' . json_encode($res));
        }
    }
    
    /**
     * Check the printer if exists in the list of scanned devices
     *
     * @param string $printerName
     * @return string device object, otherwise false if not found.
     */
    protected function check($printerName)
    {
        foreach ($this -> devices as $device) {
            if (isset($device['name']) && $device['name'] === $printerName) {
                return $device; // Return the printer details if found
            }
        }
        return false; // Return false if not found
    }

    protected function createTempFile($data)
    {
        try {
            // Create a temporary file
            $tempDir = sys_get_temp_dir();
            $tempFileName = uniqid('bluetooth_', true) . '.txt';
            $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $tempFileName;

            // Write the content to the file
            file_put_contents($tempFilePath, $data);

            return $tempFilePath;
        } catch (Exception $e) {
            throw new Exception("Unable to create temp file: " . $e);
        }
    }

    /* (non-PHPdoc)
     * @see PrintConnector::read()
     */
    public function read($len)
    {
        /* Two-way communication is not supported */
        return false;
    }

    /**
     * Run a command, pass it data, and retrieve its return value, standard output, and standard error.
     *
     * @param string $command the command to run.
     * @param string $outputStr variable to fill with standard output.
     * @param string $errorStr variable to fill with standard error.
     * @param string $inputStr text to pass to the command's standard input (optional).
     * @return number
     */
    protected function runCommand($command, &$outputStr, &$errorStr, $inputStr = null)
    {
        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];
        $process = proc_open($command, $descriptors, $fd);
        if (is_resource($process)) {
            /* Write to input */
            if ($inputStr !== null) {
                fwrite($fd[0], $inputStr);
            }
            fclose($fd[0]);
            /* Read stdout */
            $outputStr = stream_get_contents($fd[1]);
            fclose($fd[1]);
            /* Read stderr */
            $errorStr = stream_get_contents($fd[2]);
            fclose($fd[2]);
            /* Finish up */
            $retval = proc_close($process);
            return $retval;
        } else {
            /* Method calling this should notice a non-zero exit and print an error */
            return -1;
        }
    }

    /**
     * Copy a file. Separated out so that nothing is actually printed during test runs.
     *
     * @param string $from Source file
     * @param string $to Destination file
     * @return boolean True if copy was successful, false otherwise
     */
    protected function runCopy($from, $to)
    {
        return copy($from, $to);
    }

    /**
     * Write data to a file. Separated out so that nothing is actually printed during test runs.
     *
     * @param string $data Data to print
     * @param string $filename Destination file
     * @return boolean True if write was successful, false otherwise
     */
    protected function runWrite($data, $filename)
    {
        return file_put_contents($filename, $data) !== false;
    }

    public function write($data)
    {
        $this -> buffer[] = $data;
    }
}
