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

namespace Mike42\Escpos;

use InvalidArgumentException;
use Mike42\Escpos\PrintBuffers\TSPLPrintBuffer;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\PrintBuffers\TSPLBuffer;

class SizeUnit
{
    /*
    *  Metric system (mm)
    */
    const MILIMETER = 'mm';
    /*
    * Dot measurement
    * 200 DPI : 1 mm = 8 dots
    * 300 DPI : 1mm = 12 dots
    */
    const DOT = "dot";

    /*
    * English system (inch)
    */
    const INCH = '';
}

class Command
{
    const DPI200 = 8;
    const DPI300 = 12;

    const LINE_BREAK = "\r\n";
    const SEPARATOR = ",";
    const SPACE = " ";

    //Configuration related
    const SIZE = "SIZE";
    const GAP = "GAP";
    const SPEED = "SPEED";
    const REFERENCE = "REFERENCE";
    const DIRECTION = "DIRECTION";
    const OFFSET = "OFFSET";
    const SHIFT = "SHIFT";
    const SET_TEAR = "SET TEAR";

    //Action related command
    const TEXT = "TEXT";
    const BARCODE = "BARCODE";
    const QRCODE = "QRCODE";
    const BEEP = "BEEP";
    const SOUND = "SOUND";
    const BITMAP = "BITMAP";
    const PRINT = "PRINT";
    const CUT = "CUT";

    //Single word command
    const CLS = "CLS";
    const EOP = "EOP";
    const HOME = "HOME";
}

class Alignment
{
    /**
     * Align text to the left, when used with Printer::setJustification
     */
    const JUSTIFY_LEFT = 1;

    /**
     * Center text, when used with Printer::setJustification
     */
    const JUSTIFY_CENTER = 2;

    /**
     * Align text to the right, when used with Printer::setJustification
     */
    const JUSTIFY_RIGHT = 3;
}

class BarcodeType
{
    /**
     * Barcode Type 25
     */
    const TYPE_25 = "25";

    /**
     * Barcode Type 128
     */
    const TYPE_39 = "39";

    /**
     * Barcode Type 128
     */
    const  TYPE_128 = "128";
}

class QrCodeCorrectionLevel
{
    // ECC level Error correction recovery level
    const L = 'L'; // : 7%
    const M = 'M'; //  : 15%
    const Q = 'Q'; //  : 25%
    const H = 'H'; //  : 30%
}

class QrCodeMode
{
    const A = 'A'; // : Auto
    const M = 'M'; // : Manual
}

class TSPLPrinter
{
    /**
     * @var TSPLBuffer $buffer
     *  The printer's output buffer.
     */
    protected $buffer;

    /**
     * @var PrintConnector $connector
     *  Connector showing how to print to this printer
     */
    protected $connector;

    /**
     * Construct a new print object
     *
     * @param PrintConnector $connector The PrintConnector to send data to. If not set, output is sent to standard output.
     * @param int $width Width of the barcode label.
     * @param int $height Height of the barcode label
     * @param SizeUnit $unit Barcode label size unit
     */
    public function __construct(PrintConnector $connector)
    {
        /* Set connector */
        $this -> connector = $connector;

        /* Set buffer */
        $this -> buffer = null;
        $this -> setPrintBuffer(new TSPLPrintBuffer());
        $this -> initialize();
    }

    /**
     * Close the underlying buffer. With some connectors, the
     * job will not actually be sent to the printer until this is called.
     */
    public function close()
    {
        $this -> connector->finalize();
    }

    /**
     * @return TSPLBuffer
     */
    public function getPrintBuffer()
    {
        return $this -> buffer;
    }

    /**
     * @return PrintConnector
     */
    public function getPrintConnector()
    {
        return $this -> connector;
    }

    /**
     * Initialize printer. This resets formatting back to the defaults.
     */
    public function initialize($unit = SizeUnit::MILIMETER, $width = 35, $height = 25, $gapDistance = 5, $gapOffset = 0, $speed = 4, $referenceX = 0, $referenceY = 0, $direction = 1)
    {
        /* Set Size */
        $this -> setSize($width, $height, $unit);
        /* Set GAP */
        $this -> setGap($gapDistance, $gapOffset, $unit);

        $this -> connector->write(Command::SPEED . Command::SPACE . $speed . Command::LINE_BREAK);
        $this -> connector->write(Command::DIRECTION . Command::SPACE . $direction . Command::LINE_BREAK);
        $this -> connector->write(Command::REFERENCE . Command::SPACE . $referenceX . Command::SEPARATOR . $referenceY . Command::LINE_BREAK);
        $this -> connector->write(Command::CLS . Command::LINE_BREAK);
    }

    /**
     * Attach a different print buffer to the printer. Buffers are responsible for handling text output to the printer.
     *
     * @param TSPLBuffer $buffer The buffer to use.
     * @throws InvalidArgumentException Where the buffer is already attached to a different printer.
     */
    public function setPrintBuffer(TSPLBuffer $buffer)
    {
        if ($buffer === $this -> buffer) {
            return;
        }
        if ($buffer->getPrinter() != null) {
            throw new InvalidArgumentException("This buffer is already attached to a printer.");
        }
        if ($this -> buffer !== null) {
            $this -> buffer->setPrinter(null);
        }
        $this -> buffer = $buffer;
        $this -> buffer->setPrinter($this);
    }

    /**
     * Set Gap between two labels
     * By default printer treat as continuous label
     * Note:
     *  200 DPI : 1 mm = 8 dots
     *  300 DPI : 1mm = 12 dots
     *  For metric and dot systems, there must be a space between parameter and mm.
     *  When the sensor type is changed from "Black Mark" to "GAP", please send the "GAP" command to the printer first.

     * @param int $gapDistance
     * @param int $gapOffset
     * @param SizeUnit $unit
     */
    public function setGap(int $gapDistance = 0, int $gapOffset = 0, $unit = SizeUnit::MILIMETER)
    {
        if ($unit == SizeUnit::MILIMETER) {
            self::validateInteger($gapDistance, 0, 127, __FUNCTION__);
            self::validateInteger($gapOffset, 0, 255, __FUNCTION__);
        } else {
            self::validateInteger($gapDistance, 0, 5, __FUNCTION__);
            self::validateInteger($gapOffset, 0, 255, __FUNCTION__);
        }
        $str = Command::GAP;
        $str .= Command::SPACE;
        $str .= $gapDistance;
        $str .= Command::SPACE;
        $str .= $unit;
        $str .= Command::SEPARATOR;
        $str .= $gapOffset;
        $str .= Command::SPACE;
        $str .= $unit;
        $this -> connector->write($str . Command::LINE_BREAK);
    }


    /**
     * Attach a size of the label which can be used to print a label on it.
     * @param int $width - Horizontal length of label
     * @param int $height - Vertical length of label
     * @param SizeUnit $unit - Vertical length of label
     */
    public function setSize(float $width, float $height, $unit = SizeUnit::MILIMETER)
    {
        self::validateFloat($width, 1, 100, __FUNCTION__);
        self::validateFloat($height, 1, 100, __FUNCTION__);
        self::validateEnumMulti($unit, SizeUnit::class, __FUNCTION__);

        $str = Command::SIZE;
        $str .= Command::SPACE;
        $str .= $width;
        $str .= Command::SPACE;
        $str .= $unit;
        if (isset($this -> sizeHeight)) {
            $str .= Command::SEPARATOR;
            $str .= $height;
            $str .= Command::SPACE;
            $str .= $unit;
        }
        $this -> connector->write($str . Command::LINE_BREAK);
    }

    /**
     * This command prints text on label.
     * 
     * @param $text
     * @param int $x The x-coordinate of the text
     * @param int $y The y-coordinate of the text
     * @param int $font
     * @param int $rotation
     * @param int $horizontalMultiplication
     * @param int $verticalMultiplication
     * @param Alignment $alignment
     */
    public function text(string $text, int $x = 10, int $y = 10, int $font = 1, int $rotation = 0, int $horizontalMultiplication = 1, int $verticalMultiplication = 1, int $alignment = Alignment::JUSTIFY_LEFT)
    {
        self::validateIntegerMulti($rotation, [0, 90, 180, 270], __FUNCTION__);
        self::validateInteger($horizontalMultiplication, 1, 10, __FUNCTION__);
        self::validateInteger($verticalMultiplication, 1, 10, __FUNCTION__);
        self::validateEnumMulti($alignment, Alignment::class, __FUNCTION__);

        $str = Command::TEXT;
        $str .= Command::SPACE;
        $str .= $x;
        $str .= Command::SEPARATOR;
        $str .= $y;
        $str .= Command::SEPARATOR;
        $str .= '"' . strval($font) . '"';
        $str .= Command::SEPARATOR;
        $str .= $rotation;
        $str .= Command::SEPARATOR;
        $str .= $horizontalMultiplication;
        $str .= Command::SEPARATOR;
        $str .= $verticalMultiplication;
        if (isset($alignment)) {
            $str .= Command::SEPARATOR;
            $str .= $alignment;
        }
        $str .= Command::SEPARATOR;
        $str .= '"' . strval($text) . '"';

        $this -> connector->write($str . Command::LINE_BREAK);
    }

    /**
     * Add Barcode string in the file.
     * 
     * @param string $barcodeText
     * @param int $x
     * @param int $y
     * @param string $codeType
     * @param int $height
     * @param int $humanReadable
     * @param int $rotation
     * @param int $narrow
     * @param int $wide
     */
    public function barcode(string $barcodeText, int $x = 10, int $y = 10, string $codeType = BarcodeType::TYPE_128, int $height = 50, int $humanReadable = 0, int $rotation = 0, int $narrow = 1, int $wide = 1, $alignment = Alignment::JUSTIFY_LEFT)
    {
        self::validateInteger($x, 1, 350, __FUNCTION__);
        self::validateInteger($y, 1, 10000, __FUNCTION__);
        self::validateInteger($height, 1, 100, __FUNCTION__);
        self::validateIntegerMulti($rotation, [0, 90, 180, 270], __FUNCTION__);
        self::validateInteger($narrow, 1, 10, __FUNCTION__);
        self::validateInteger($wide, 1, 10, __FUNCTION__);
        self::validateEnumMulti($alignment, Alignment::class, __FUNCTION__);

        $str = Command::BARCODE;
        $str .= Command::SPACE;
        $str .= $x;
        $str .= Command::SEPARATOR;
        $str .= $y;
        $str .= Command::SEPARATOR;
        $str .= '"' . $codeType . '"';
        $str .= Command::SEPARATOR;
        $str .= $height;
        $str .= Command::SEPARATOR;
        $str .= $humanReadable;
        $str .= Command::SEPARATOR;
        $str .= $rotation;
        $str .= Command::SEPARATOR;
        $str .= $narrow;
        $str .= Command::SEPARATOR;
        $str .= $wide;
        if (isset($alignment)) {
            $str .= Command::SEPARATOR;
            $str .= $alignment;
        }
        $str .= Command::SEPARATOR;
        $str .= '"' . addslashes($barcodeText) . '"';

        $this -> connector->write($str . Command::LINE_BREAK);
    }

    /**
     * This command prints QR code
     * 
     * @param string $text
     * @param int $x
     * @param int $y
     * @param QrCodeCorrectionLevel $correction
     * @param int $cellWidth
     * @param QrCodeMode $mode
     * @param int $rotation
     */
    public function qrcode($text, $x, $y, $correction = QrCodeCorrectionLevel::H, $cellWidth = 4, $mode = QrCodeMode::A, $rotation = 0)
    {
        self::validateInteger($cellWidth, 1, 10, __FUNCTION__);
        self::validateEnumMulti($correction, QrCodeCorrectionLevel::class, __FUNCTION__);
        self::validateEnumMulti($mode, QrCodeMode::class, __FUNCTION__);
        self::validateIntegerMulti($rotation, [0, 90, 180, 270], __FUNCTION__);

        $str = Command::QRCODE;
        $str .= Command::SPACE;
        $str .= $x;
        $str .= Command::SEPARATOR;
        $str .= $y;
        $str .= Command::SEPARATOR;
        $str .= $correction;
        $str .= Command::SEPARATOR;
        $str .= $cellWidth;
        $str .= Command::SEPARATOR;
        $str .= $mode;
        $str .= Command::SEPARATOR;
        $str .= $rotation;
        $str .= Command::SEPARATOR;
        $str .= '"' . addslashes($text) . '"';

        $this -> connector->write($str . Command::LINE_BREAK);
    }
    /**
     * This command draws bitmap images (as opposed to BMP graphic files)
     * 
     * @paramEscposImage $image The image to print.
     * @param int $x X Specify the x-coordinate
     * @param int $y Y Specify the y-coordinate
     * @param int $mode Graphic modes listed below:
     *                     0: OVERWRITE
     *                     1: OR
     *                     2: XOR
     */
    public function image(EscposImage $image, $x, $y, $mode)
    {
        $this -> setBitmapCommand($x, $y, $image->getWidthBytes(), $image->getHeight(), $mode, $image->toRasterFormat());
    }

    /**
     * This command draws bitmap images (as opposed to BMP graphic files)
     * 
     * @param int $x X Specify the x-coordinate
     * @param int $y Y Specify the y-coordinate
     * @param int $withdBytes Image width (in bytes)
     * @param int $heightDots Image height (in dots)
     * @param int $mode Graphic modes listed below:
     *                     0: OVERWRITE
     *                     1: OR
     *                     2: XOR
     * @param String $bitmapData Bitmap data
     */
    protected function setBitmapCommand($x, $y, $withdBytes, $heightDots, $mode, $bitmapData)
    {
        $str = Command::BITMAP;
        $str .= Command::SPACE;
        $str .= $x;
        $str .= Command::SEPARATOR;
        $str .= $y;
        $str .= Command::SEPARATOR;
        $str .= $withdBytes;
        $str .= Command::SEPARATOR;
        $str .= $heightDots;
        $str .= Command::SEPARATOR;
        $str .= $mode;
        $str .= Command::SEPARATOR;
        $str .= $bitmapData;

        $this -> connector->write($str . Command::LINE_BREAK);
    }


    /**
     * Print command will print the label format currently stored in the image buffer.
     * 
     * @param int $noOfSet
     * @param int $noOfCopy
     */
    protected function setPrint(int $noOfSet = 1, int $noOfCopy = 1)
    {
        self::validateInteger($noOfSet, 1, 10, __FUNCTION__);
        self::validateInteger($noOfCopy, 1, 10, __FUNCTION__);

        $str = Command::PRINT;
        $str .= Command::SPACE;
        $str .= $noOfSet;
        if (isset($noOfCopy)) {
            $str .= Command::SEPARATOR;
            $str .= $noOfCopy;
        }
        $this -> connector->write($str . Command::LINE_BREAK);
        $this -> connector->write(Command::EOP . Command::LINE_BREAK);
    }

    /**
     * Print command will print the label format currently stored in the image buffer.
     * with the default offset and copies
     * 
     * @param int $autoClose If true you don't need to call $printer->close();
     * @param int $copy Number of copies to be printed
     */
    public function print($autoClose = true, $copy = 1)
    {
        $this -> setPrint(1, $copy);
        if ($autoClose) {
            $this -> close();
            $this -> buffer = null;
        }
    }

    /**
     * This command controls the sound frequency of the beeper. There are 10 levels of sounds.
     * The timing control can be set by the "interval" parameter.
     * 
     * @param int $level Sound level
     * @param int $interval Sound interval
     */
    public function beep($level = 5, $interval = 100)
    {
        self::validateInteger($level, 1, 9, __FUNCTION__);
        self::validateInteger($interval, 1, 4095, __FUNCTION__);

        $str = Command::SOUND;
        $str .= Command::SPACE;
        $str .= $level;
        $str .= Command::SEPARATOR;
        $str .= $interval;
        $this -> connector->write($str . Command::LINE_BREAK);
    }

    protected static function validateInteger(int $test, int $min, int $max, string $source, string $argument = "Argument")
    {
        self::validateIntegerMulti($test, [[$min, $max]], $source, $argument);
    }

    protected static function validateFloat(float $test, float $min, float $max, string $source, string $argument = "Argument")
    {
        self::validateIntegerMulti(intval($test), [[intval($min), intval($max)]], $source, $argument);
    }

    /**
     * Throw an exception if the argument given is not an integer within one of the specified ranges
     *
     * @param int $test the input to test
     * @param array $ranges array of two-item min/max ranges.
     * @param string $source the name of the function calling this
     * @param string $source the name of the function calling this
     * @param string $argument the name of the invalid parameter
     */
    protected static function validateIntegerMulti(int $test, array $ranges, string $source, string $argument = "Argument")
    {
        if (!is_integer($test)) {
            throw new InvalidArgumentException("$argument given to $source must be a number, but '$test' was given.");
        }
        $match = false;
        foreach ($ranges as $range) {
            $match |= $test >= $range[0] && $test <= $range[1];
        }
        if (!$match) {
            // Put together a good error "range 1-2 or 4-6"
            $rangeStr = "range ";
            for ($i = 0; $i < count($ranges); $i++) {
                $rangeStr .= $ranges[$i][0] . "-" . $ranges[$i][1];
                if ($i == count($ranges) - 1) {
                    continue;
                } elseif ($i == count($ranges) - 2) {
                    $rangeStr .= " or ";
                } else {
                    $rangeStr .= ", ";
                }
            }
            throw new InvalidArgumentException("$argument given to $source must be in $rangeStr, but $test was given.");
        }
    }

    /**
     * Throw an exception if the argument given is not a valid value in the specified enum class
     *
     * @param mixed $value The value to validate
     * @param string $enumClass The fully qualified class name of the enum
     * @param string $source The name of the calling function
     * @param string $argument The name of the invalid parameter
     *
     * @throws InvalidArgumentException
     */
    protected static function validateEnumMulti($value, string $enumClass, string $source, string $argument = "Argument")
    {
        // Ensure the enum class exists
        if (!class_exists($enumClass)) {
            throw new InvalidArgumentException("Enum class '$enumClass' does not exist.");
        }

        // Get all constants of the enum class
        $validValues = (new \ReflectionClass($enumClass))->getConstants();

        // Check if the value is in the valid values
        if (!in_array($value, $validValues, true)) {
            $validValuesStr = implode(", ", $validValues);
            throw new InvalidArgumentException("$argument given to $source must be one of [$validValuesStr], but '$value' was given.");
        }
    }
}
