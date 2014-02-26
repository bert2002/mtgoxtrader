
MtGox Bitcoin Trader
=============

Easy Bitcoin trading bot for MtGox. The bot only sells Bitcoins if a revenue is expected. Basically you can not loose money, but therefore it is really slow.

Features
-------

* Usage of MtGox API
* Only selling/buying if configured margin is matched
* Easy Webinterface and log file
* Email notification
* Force buying and selling after the last transaction was not made within x seconds. 

Configuration
-------

Please configure the MtGox and user configuration in mtgoxtrader.pl. Before starting the script you need to add the trades you want to do in the sqlite database.
Currently there is no interface to do it in a easy way. If you want to buy bitcoins you need to insert the values in the bought table and vice verca. 

Using the webinterface can be done by configuring a vhost to "fronted/".

Known Problems
-------

* Slow trading

Screenshots
-------

* http://github.com/bert2002/frontend/blob/master/screenshots/frontend.png

