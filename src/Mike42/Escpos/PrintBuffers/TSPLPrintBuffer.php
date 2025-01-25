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

namespace Mike42\Escpos\PrintBuffers;

use LogicException;
use Mike42\Escpos\TSPLPrinter;

class TSPLPrintBuffer implements TSPLBuffer {

    /**
     * @var Printer $printer
     *  Printer for output
     */
    private $printer;

    /**
     * Empty print buffer.
     */
    public function __construct()
    {
        $this -> printer = null;
    }

    /**
     * Cause the buffer to send any partial input and wait on a newline.
     * If the printer is already on a new line, this does nothing.
     */
    public function flush()
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
    }

    /**
     * Used by Escpos to check if a printer is set.
     */
    public function getPrinter()
    {
        return $this -> printer;
    }

    /**
     * Used by Escpos to hook up one-to-one link between buffers and printers.
     * @param TSPLPrinter $printer New printer
     */
    public function setPrinter(TSPLPrinter $printer = null)
    {
        $this -> printer = $printer;
    }

    /**
     * Accept UTF-8 text for printing.
     *
     * @param string $text Text to print
     */
    public function writeText(string $text)
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        if ($text == null) {
            return;
        }
    }

    /**
     * Write data to the underlying printer.
     *
     * @param string $data
     */
    private function write(string $data)
    {
        $this -> printer -> getPrintConnector() -> write($data);
    }

}