# Ricky Kearney 
# Creates a table in the mysql database.
# Database name is 'TempSensors'
# Table name is 'Temps'

import MySQLdb

# Open database connection
db = MySQLdb.connect("localhost","root","raspbian","TempSensors" )

# prepare a cursor object using cursor() method
cursor = db.cursor()

# Drop table if it already exist using execute() method.
cursor.execute("DROP TABLE IF EXISTS Temps")

# Create table as per requirement
sql = """CREATE TABLE Temps (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        Sensor VARCHAR(30) NOT NULL,
        Temp INT(6) NOT NULL,
        Time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP )"""

try:
	cursor.execute(sql)
	db.commit()
	print("Successfully created DB")
except Exception,e:
	db.rollback()
	print("Failed to create DB")
	print str(e)

# disconnect from server
db.close()
