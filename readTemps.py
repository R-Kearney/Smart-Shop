import os
import time
import MySQLdb
import requests

os.system('modprobe w1-gpio')
os.system('modprobe w1-therm')

#temp_sensor = '/sys/bus/w1/devices/SERIALNUMBER/w1_slave'
tempSensors = {'Upper Freezer': '28-0316360119ff', 'Milk Fridge': '28-031636b962ff', 'Lower Freezer': '28-031636b6b2ff'}
objectArray = {}

class Device(object):
    rootLocation = '/sys/bus/w1/devices/'
    slaveLocaton = '/w1_slave'
    readLocation = ''
    deviceName = ''
    lastTemp = 0
    temp = 0
    alertSent = 0

    def __init__(self, name, serial):
        self.readLocation = self.rootLocation + serial + self.slaveLocaton
        self.deviceName = name


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
        if ((self.temp > 0) and (self.alertSent == 0)):
            timeNow = time.time()
            self.alertSent = 1
            report = ("Location: %s, Temp: %d, Time: %s") % (self.deviceName, self.temp, timeNow)
            try:
                requests.post("https://maker.ifttt.com/trigger/button_pressed/with/key/bY70sf0_iym1J6GPy_gRAK", data=report)
                print("Alert Sent!!!")
            except Exception:
                print("*Failed to send Alert*")
        elif ((self.temp < 0) and (self.alertSent == 1)):
            self.alertSent = 0


for key in tempSensors.keys():
    objectArray[key] = Device(key, tempSensors[key])

while True:
    for key in objectArray.keys():
        objectArray.get(key).update()
    time.sleep(300) #sleep for 1 second * 5min
    print("\n\n")
