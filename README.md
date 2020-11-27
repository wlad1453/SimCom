# SimCom
This project realizes an ultrasonic distance measurement and determines a presence time of a truck in the measurement field. These data are send to the server via GPRS/HTTP technology. On the server side this data are analized and an intermediate data file (sim800truck_data.txt) as well as an DB entry are created or updated.
The visualization of the actual data is done via WEB interface (sim800data.php). 
With the help of a JS scripts and AJAX method the data file sim800truck_data.txt and the last db entry is being read every 500 mS and the actual data are
being presented via WEB-inteface. 
