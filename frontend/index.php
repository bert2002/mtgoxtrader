<?php

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('../mtgoxtrader.db');
    }
}

$db = new MyDB();

/* Design funny... */
print "<meta http-equiv=refresh content=\"30; URL=index.php\">";
print "<h1>MtGox Trader <> units in integer</h1>";
print "<iframe src='pchart480.php' style='border:0px; width: 920px; height:250px;'></iframe><br>";
print '<div style="float: left;">';

/* Graph */
print "<iframe src='pchart.php' style='border:0px; width: 720px; height:250px;'></iframe>";

/* BOUGHT */

print "<br><b>Bought:</b>";
    echo "<table>";
    echo "<thead>
      <tr>
      <th>ID</th>
      <th>Timestamp</th>
      <th>Amount</th>
      <th>Price</th>
      <th>Oid</th>
      <th>Status</th>
      </tr>
    </thead>";

$result = $db->query('SELECT * FROM bought ORDER BY id DESC LIMIT 7');
$row = array();
$i = 0; 
while($res = $result->fetchArray(SQLITE3_ASSOC)){ 

	if(!isset($res['id'])) continue; 

	$id = $res['id'];
	$amount = $res['amount'];
	$price = $res['price'];
	$timestamp = $res['timestamp'];
	$oid = $res['oid'];
	$status = $res['status'];

	echo "<tr>
    <td>$id</td>
    <td>$timestamp</td>
    <td>$amount</td>
    <td>$price</td>
    <td>$oid</td>
    <td>$status</td>
  </tr>";

	$i++;
}
echo "</table>";


/* SOLD */

print "<br><b>Sold:</b>";
    echo "<table>";
    echo "<thead>
      <tr>
      <th>ID</th>
      <th>Timestamp</th>
      <th>Amount</th>
      <th>Price</th>
      <th>Oid</th>
      <th>Status</th>
      </tr>
    </thead>";

$result = $db->query('SELECT * FROM sold ORDER BY id DESC LIMIT 7');
$row = array();
$i = 0;
while($res = $result->fetchArray(SQLITE3_ASSOC)){

  if(!isset($res['id'])) continue;

  $id = $res['id'];
  $amount = $res['amount'];
  $price = $res['price'];
  $timestamp = $res['timestamp'];
  $oid = $res['oid'];
  $status = $res['status'];

  echo "<tr>
    <td>$id</td>
    <td>$timestamp</td>
    <td>$amount</td>
    <td>$price</td>
    <td>$oid</td>
    <td>$status</td>
  </tr>";

  $i++;
}
echo "</table>";

print '</div><div style="overflow: hidden;">';

/* DATA */
print "<br><b>Data:</b>";

echo "<table>";
echo "<thead>
	<tr>
  <th>ID</th>
  <th>Timestamp</th>
  <th>Buy</th>
  <th>Sell</th>
  </tr>
</thead>";

$result = $db->query('SELECT * FROM data ORDER BY id DESC LIMIT 20');
$row = array();
$i = 0;
while($res = $result->fetchArray(SQLITE3_ASSOC)){

  if(!isset($res['id'])) continue;

  $id = $res['id'];
  $buy = $res['buy'];
  $sell = $res['sell'];
  $timestamp = $res['timestamp'];

  echo "<tr>
    <td>$id</td>
    <td>$timestamp</td>
    <td>$sell</td>
    <td>$buy</td>
  </tr>";

  $i++;
}
echo "</table></div>";

$db = null; 

?>
