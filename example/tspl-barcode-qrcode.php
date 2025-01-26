<?php
/* Demonstration of upside-down printing */
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\TSPLPrinter;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\SizeUnit;

$connector = new FilePrintConnector("php://stdout");

 $printer = new TSPLPrinter($connector);
 
 $printer -> initialize(SizeUnit::MILIMETER, 35, 25);
 $printer -> beep();

 $printer -> text("QR TEXT", 2, 10);
 $printer -> qrcode("123456", 2, 30);
 $printer -> print();

 $printer -> text("BAR TEXT", 2, 10);
 $printer -> barcode("123456", 2, 30);
 $printer -> print();

 /* Close printer */
 $printer -> close();