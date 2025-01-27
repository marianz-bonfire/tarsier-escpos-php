<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\PrintConnectors\BluetoothPrintConnector;
use Mike42\Escpos\Printer;

/**
 * Assuming your printer is available at Printer001,
 * simpy instantiate a BluetoothPrintConnector to it.
 *
 * When troubleshooting, make sure you can send it
 * data from the command-line first:
 * 
 *  echo "Hello World" > testfile.txt
 *  tarsier_bluetooth_agent.exe --print="Printer001" --path="testfile.txt"
 */
try {
    $connector = new BluetoothPrintConnector("Printer001");
    
    /* You can call `getBluetoothDevices` to get the list of Bluetooth devices */
    $devices = $connector->getBluetoothDevices();
    
    /* Print a "Hello world" receipt" */
    $printer = new Printer($connector);
    $printer -> text("Hello World!\n");
    $printer -> text("Bluetooth Devices\n");
    $printer -> feed();
    foreach ($devices as $device) {
        //echo $device['name'].'<br>';
        $printer -> text("  {$device['name']}\n");
    }
    $printer -> feed();
    $printer -> cut();
    
    /* Close printer */
    $printer -> close();
} catch (Exception $e) {
    echo $e -> getMessage();
}
