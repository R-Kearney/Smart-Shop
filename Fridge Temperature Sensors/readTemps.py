# Ricky Kearney
# Smart Supermarket - Temperature Sensors
#
# Reads the temperature from ds18b20 temp sensors over one wire.
# Every 5 minutes the temp is checked to be valid and sends an alert over IFTT if its too high
# the temp is then sent to a mysql database
# To add the sensors replace the Serial number in tempSensors and tempLimit arrays with your own.
#

import os
import time
import MySQLdb
import requests

os.system('modprobe w1-gpio')
os.system('modprobe w1-therm')

#temp_sensor = '/sys/bus/w1/devices/SERIALNUMBER/w1_slave'
# Below array holds serial number for he temp sensors
tempSensors = {'28-0316360119ff': 'Upper Freezer', '28-031636b962ff': 'Milk Fridge', '28-031636b6b2ff': 'Lower Freezer'}
objectArray = {}
tempLimit = {'28-0316360119ff': -10, '28-031636b962ff': 5, '28-031636b6b2ff': -10}

class Device(object):
    rootLocation = '/sys/bus/w1/devices/'
    slaveLocaton = '/w1_slave'
    readLocation = ''
    deviceName = ''
    lastTemp = 0
    temp = 0
    alertSent = 0
    tempLimit = 0
    timeTempHitLimit = 0

    def __init__(self, name, serial, tempLimit):
        self.readLocation = self.rootLocation + serial + self.slaveLocaton
        self.deviceName = name
        self.tempLimit = tempLimit

    def update(self):
        try:
            temp = self.read_temp()
            if temp != 0:
                self.temp = temp
                self.sendToDB()
                self.checkForAlert()
                print("Device: %s ---> Temp: %dC") % (self.deviceName, self.temp)
        except Exception,e:
            print("Failed to locate File %s") % (self.readLocation)
            print(e)

    def temp_raw(self):
        f = open(self.readLocation, 'r')
        lines = f.readlines()
        f.close()
        return lines

    def read_temp(self):
        lines = self.temp_raw()

        while lines[0].strip()[-3:] != 'YES':
            time.sleep(0.2)
            lines = self.temp_raw()
        temp_output = lines[1].find('t=')

        #Check if the value is ok (within 5oc of last temp)
        if temp_output != -1:
            temp_string = lines[1].strip()[temp_output+2:]
            temp_c = float(temp_string) / 1000.0
            if self.lastTemp == 0:
                self.lastTemp = temp_c
                return temp_c
            elif ((self.lastTemp + 5) > temp_c):
                self.lastTemp = temp_c
                return temp_c
            elif ((self.lastTemp - 5) < temp_c):
                self.lastTemp = temp_c
                return temp_c
            else:
                return 0

    def sendToDB(self):
        db = MySQLdb.connect("localhost","root","raspbian","TempSensors" )
        cursor = db.cursor()
        timeNow = time.time()
        devData = "INSERT INTO Temps \
        		(Sensor, Temp) \
        	VALUES \
        		('%s', '%d')" % \
        		(self.deviceName, self.temp)
        try:
        	cursor.execute(devData)
     		db.commit()
        except Exception,e:
     		db.rollback()
        db.close()

    def checkForAlert(self):
        if ((self.temp >= self.tempLimit) and (self.alertSent == 0)):
            if (self.timeTempHitLimit == 0):
                self.timeTempHitLimit = time.time()
            if ((time.time() - self.timeTempHitLimit) >= 6000 ): # 6000 seconds == 100 minutes
                self.alertSent = 1
                report = ("Location: %s, Temp: %d, Time: %s") % (self.deviceName, self.temp, time.time())
                try:
                    requests.post("https://maker.ifttt.com/trigger/tempSensor/with/key/XXXXX", data=report) # Replace 'XXXXX' with your generated maker key.
                    print("Alert Sent!!!")
                except Exception:
                    print("*Failed to send Alert*")
        elif ((self.temp < self.tempLimit) and (self.alertSent == 1)):
            self.alertSent = 0
            self.timeTempHitLimit = 0

# Creates a device instance for each sensor
for key in tempSensors.keys():
    objectArray[key] = Device(tempSensors[key], key, tempLimit[key])

while True:
    for key in objectArray.keys():
        objectArray.get(key).update() # updates temp
    time.sleep(300) #sleep for 1 second * 5min = 300 seconds
    print("\n\n")
