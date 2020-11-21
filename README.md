# SimCom
This project realizes an ultrasonic measurement and determines an object presence time. These data are being send to the server via GPRS/HTTP technology.
On the server side this data are being analized. An intermediate data file (sim800truck_data.txt) as well as an DB entry is created/updated.
The visualization of the actual data is being fullfilled via WEB interface (sim800data.php). 
With the help of a JS and AJAX method the data file sim800truck_data.txt and the last db entry is being read every 500 mS and the current data are
being presented on the WEB inteface. 
