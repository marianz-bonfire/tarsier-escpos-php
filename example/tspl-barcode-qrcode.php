<?php
/* Demonstration of upside-down printing */
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\TSPLPrinter;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\SizeUnit;

//$connector = new FilePrintConnector("php://stdout");
$connector = new WindowsPrintConnector('XP-420B');

 $printer = new TSPLPrinter($connector);
 
 $printer -> initialize(SizeUnit::MILIMETER, 38.1, 31.75);
 $printer -> beep();

 $printer -> text("QR TEXT", 2, 20);
 $printer -> qrcode("123456", 2, 40);
 $printer -> print();
 //$printer -> close();
