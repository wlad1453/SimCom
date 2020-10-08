# SimCom
This project realizes an ultrasonic measurement and determines an object presence time. These data are being send to the server via GPRS/HTTP technology.
On the server side this data are analized and an intermediate data file (sim800truck_data.txt) is created/updated.
The visualization of the actual data is being fullfilled via WEB interface (sim800data.php). 
With help of a JS and AJAX method the data file sim800truck_data.txt is being read every 500 mS and the current data are
being presented on the WEB inteface. 
