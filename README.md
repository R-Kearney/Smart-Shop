# Smart - Supermarket

###Devices to make running a shop easier!

Mainly coded in Python, PHP and JavaScript.

###Current functionality:
- Measures fridge & freezer temps every 5 minutes.
- Plots them on a mobile friendly graph, with adjustable range
- If the temperature goes above the limit for more than 100 minutes an IFTT recipe is called and alerts the user on their phone & smart watch.



###Future functionality:
- Buttons on the site open/close shutters



## Requirements:
-Raspberry Pi - Raspbian with OneWire enabled
-DS18b20 OneWire temperature Sensors
-Apache, PHP, Mysql, Mysqli and Python
-IFTT account with Maker recipe.



## To USE:
Copy Web Server files to WWW directory.

Copy python files to the Pi.

Make a mysql database called TempSensors and then run makeDB.py

Connect the sensors to the pi




| DS18b20 | Signal | Raspberry Pi          |
| ------- |:------:| ---------------------:|
| GND     | Ground | GND                   |
| DQ      | Data   | GPIO 4 (*Pulled high) |
| VDQ     | +3.3v  | +3.3v                 |

(4.7k ohm resistor between DQ and +3.3v)




Change the sensor serial numbers in readTemps.py to your own. (found by opening the OneWire devices folder)

Add in the key for IFTT maker.

run readTemps.py and ensure it collects the temperatures correctly.

Open the website and enjoy the data.
