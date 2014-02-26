<?php
include("pchart/class/pData.class.php");
include("pchart/class/pDraw.class.php");
include("pchart/class/pImage.class.php");

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('../mtgoxtrader.db');
    }
}

$db = new MyDB();

$result = $db->query('SELECT * FROM data ORDER BY id DESC LIMIT 480');
$row = array();
$i = 0;
while($res = $result->fetchArray(SQLITE3_ASSOC)){

  if(!isset($res['id'])) continue;

	$timestamp[] = $res["timestamp"];
	$buy[] = $res["buy"];
	$sell[] = $res["sell"];

	$i++;

}

$buy = array_reverse($buy);
$sell = array_reverse($sell);
$timestamp = array_reverse($timestamp);

$myData = new pData();
$myData->addPoints($sell,"Serie1");
$myData->setSerieDescription("Serie1","Buy");
$myData->setSerieOnAxis("Serie1",0);

$myData->addPoints($buy,"Serie2");
$myData->setSerieDescription("Serie2","Sell");
$myData->setSerieOnAxis("Serie2",0);

//$myData->addPoints(array(-9,16,-8,18,13,-34,37,35),"Serie3");
//$myData->setSerieDescription("Serie3","Trade");
//$myData->setSerieOnAxis("Serie3",0);

$myData->addPoints($timestamp,"Absissa");
$myData->setAbscissa("Absissa");

$myData->setAxisPosition(0,AXIS_POSITION_RIGHT);
$myData->setAxisName(0,"1st axis");
$myData->setAxisUnit(0,"");

$myPicture = new pImage(900,230,$myData);
$Settings = array("R"=>255, "G"=>255, "B"=>255, "Dash"=>1, "DashR"=>275, "DashG"=>275, "DashB"=>275);
$myPicture->drawFilledRectangle(0,0,700,230,$Settings);

$Settings = array("StartR"=>0, "StartG"=>0, "StartB"=>0, "EndR"=>0, "EndG"=>0, "EndB"=>0, "Alpha"=>50);
$myPicture->drawGradientArea(0,0,900,230,DIRECTION_VERTICAL,$Settings);

$myPicture->drawRectangle(0,0,899,229,array("R"=>0,"G"=>0,"B"=>0));

$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>20));

$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/verdana.ttf","FontSize"=>12));
$TextSettings = array("Align"=>TEXT_ALIGN_MIDDLEMIDDLE
, "R"=>0, "G"=>0, "B"=>0);
$myPicture->drawText(300,25,"Bot Chart 480",$TextSettings);

$myPicture->setShadow(FALSE);
$myPicture->setGraphArea(10,40,850,190);
$myPicture->setFontProperties(array("R"=>0,"G"=>0,"B"=>0,"FontName"=>"pchart/fonts/verdana.ttf","FontSize"=>6));

$Settings = array("Pos"=>SCALE_POS_LEFTRIGHT
, "Mode"=>SCALE_MODE_FLOATING
, "LabelingMethod"=>LABELING_ALL
, "LabelSkip"=>"100"
, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
$myPicture->drawScale($Settings);

$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>10));

$Config = array("DisplayValues"=>0);
$myPicture->drawSplineChart($Config);

$Config = array("FontR"=>0, "FontG"=>0, "FontB"=>0, "FontName"=>"pchart/fonts/verdana.ttf", "FontSize"=>6, "Margin"=>6, "Alpha"=>30, "BoxSize"=>5, "Style"=>LEGEND_NOBORDER
, "Mode"=>LEGEND_HORIZONTAL
, "Family"=>LEGEND_FAMILY_LINE
);
$myPicture->drawLegend(786,205,$Config);

$myPicture->stroke();
?>
