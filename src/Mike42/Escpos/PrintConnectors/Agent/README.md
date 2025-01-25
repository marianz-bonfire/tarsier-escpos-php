<p align="center">  
    <img height="260" src="https://raw.githubusercontent.com/marianz-bonfire/php-bluetooth-windows/master/php-bluetooth.png">
    <h1 align="center">Bluetooth Printer Management with PHP</h1>
</p>


A simple example of PHP-based web interface for printing via Bluetooth printers on Windows. It includes the following key features:

### ✨ Features
#### 1. Scan for Bluetooth Devices

- A web interface with a "**Scan Bluetooth Devices**" button that dynamically fetches available Bluetooth devices.
- Displays the list of discovered devices in a dropdown combobox for selection.
#### 2. Print Text to Bluetooth Printers

- A textarea to input the content to be printed.
- A "**Print**" button that creates a temporary file from the textarea content and sends it to the selected Bluetooth printer.
#### 3. Backend Integration

- Uses a backend (`tarsier_bluetooth_agent.exe`) to handle Bluetooth device scanning and printing.
Communicates between PHP via API endpoints (`scan.php` and `print.php`).

### 🚀 Usage of Tarsier Bluetooth Agent
#### Commands
```
usage: tarsier_bluetooth_agent.exe [--help] [--list] [--print=PRINT] [--path=PATH]

options:
  --help         show this help message and exit
  --list         List available Bluetooth devices.
  --print PRINT  Printer name to send data.
  --path PATH    Path to the file to be printed.
  ```
  #### 1. Get the list of bluetooth devices
```
tarsier_bluetooth_agent.exe --list
```
Sample Response
```json
{
    "status": "success",
    "devices": [
        {
            "address": "00:11:22:33:44:55",
            "name": "Printer1",
            "class": 1664
        },
        {
            "address": "66:77:88:99:AA:BB",
            "name": "Printer2",
            "class": 1664
        }
    ]
}
```

  #### 2. Send data to be printed
```
tarsier_bluetooth_agent.exe --print="Printer1" --path="/path/to/file.txt"
```
Sample Response
```json
{
    "status": "success",
    "message": "Data sent to printer successfully."
}
```

### 📸 Screenshots
<img src="https://raw.githubusercontent.com/marianz-bonfire/php-bluetooth-windows/master/screenshot.png">


## 🎖️ License
This project is licensed under the [MIT License](https://mit-license.org/). See the LICENSE file for details.
## 🐞 Contributing
Contributions are welcome! Please submit a pull request or file an issue for any bugs or feature requests
on [GitHub](https://github.com/marianz-bonfire/php-bluetooth-windows).