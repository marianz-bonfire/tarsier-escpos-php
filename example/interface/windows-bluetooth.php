<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\PrintConnectors\BluetoothPrintConnector;
use Mike42\Escpos\SizeUnit;
use Mike42\Escpos\TSPLPrinter;

/**
 * Assuming your printer is available at LPT1,
 * simpy instantiate a WindowsPrintConnector to it.
 *
 * When troubleshooting, make sure you can send it
 * data from the command-line first:
 *  echo "Hello World" > LPT1
 */
try {
    $connector = new BluetoothPrintConnector("Printer001");
    
    /* You can call `getBluetoothDevices` to get the list of Bluetooth devices */
    $devices = $connector->getBluetoothDevices();
    
    foreach ($devices as $device) {
        //echo $device['name'].'<br>';
    }

    /* Print a "Hello world" receipt" */
    $printer = new TSPLPrinter($connector);
    $printer -> initialize(SizeUnit::MILIMETER, 35, 25);
    $printer -> beep();
    $printer -> text("Hello World!", 2, 10);
    $printer -> qrcode("123456", 2, 30);
    $printer -> print();

    /* Close printer */
    $printer -> close();
} catch (Exception $e) {
    echo $e -> getMessage();
}
