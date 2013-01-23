<?php

require_once("Netatmo-API/NAApiClient.php");
require_once("Netatmo-API/Config.php");

$client = new NAApiClient($config);

$client->setVariable("username", $test_username);
$client->setVariable("password", $test_password);

$helper = new NAApiHelper();
try {
    $tokens = $client->getAccessToken();        
    
} catch(NAClientException $ex) {
    echo "An error happend while trying to retrieve your tokens : \n";
    die();
}


// Retrieve User Info :
$user = $client->api("getuser", "POST");

$devicelist = $client->api("devicelist", "POST");
$devicelist = $helper->SimplifyDeviceList($devicelist);

$last_mesures = $helper->GetLastMeasures($client,$devicelist);


$device=$devicelist["devices"][0];
$module=$device["modules"][0];

// since begining : scale = 30min 3hours
$date_begin = $user['date_creation']['sec'];
$date_end   = time();
$scale      = "30min";
$measures   = $helper->GetMeasures(
								$client,
								$device["_id"],
								$date_begin,
								$date_end,
								null,
								$scale
							);

$measure_type = array('Temperature','CO2','Humidity','Pressure','Noise','Temperature_module','Humidity_module');

$str_data = array();
$str_data['Temperature'] = $helper->SimplifyMeasureForJS($measures,'Temperature');
//
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Netatmo</title>
    <script language="javascript" type="text/javascript" src="js/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
    <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.navigate.js"></script>
    <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.selection.js"></script>
    <script language="javascript" type="text/javascript" src="js/jquery-ui.js"></script>
    <script id="source">
    	var date_begin=<?php echo $date_begin ?>*1000;
    </script>
    <script language="javascript" type="text/javascript" src="js/myNetatmo.js"></script>
    <link rel="stylesheet" href="css/jquery-ui.css" />
		<style>
		  .ui-tabs-vertical { width: 55em; }
		  .ui-tabs-vertical .ui-tabs-nav { padding: .2em .1em .2em .2em; float: left; width: 12em; }
		  .ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0; }
		  .ui-tabs-vertical .ui-tabs-nav li a { display:block; }
		  .ui-tabs-vertical .ui-tabs-nav li.ui-tabs-active { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; border-right-width: 1px; }
		  .ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: right; width: 40em;}
		</style>
	</head>
	<body>
	<div style="border:solid 1px black; border-radius: 10px; width:600px; padding:10px; margin:auto; text-align:left;">
		<h2 style='text-align:center;' > Dernière mesure de la station  <?php echo $last_mesures[0]['station_name'] ?> </h2>
		<div id="accordion" style='width:600px; margin:auto;'>
		<?php
			foreach($last_mesures[0]['modules'] as $module)
			{
				echo "<h3> Module  " . $module['module_name'] ." </h3>";
				echo "<div> Le " . date( 'd M Y à H:i:s',$module['time']) ." :";
				echo "<ul>";
				echo "<li>  Température : " . $module['Temperature'] ."° C </li> ";
				echo "<li>  Humidité : " . $module['Humidity'] ." % </li> ";
				if(isset($module['CO2']))
					echo "<li>  CO2 : " . $module['CO2'] ." ppm </li> ";
				if(isset($module['Pressure']))
					echo "<li>  Pression : " . $module['Pressure'] ." mbar </li> ";
				if(isset($module['Noise']))
					echo "<li>  Bruit : " . $module['Noise'] ." dB </li> ";
				echo "</ul></div>";
			}
		?>
			</div>
		</div>
		<div style="border:solid 1px black; border-radius: 10px; width:1000px; padding:10px; margin-top:10px; margin-left:auto; margin-right:auto; text-align:left;">
			<h2 style='text-align:center;'> Historique de la station  <?php echo $last_mesures[0]['station_name'] ?> </h2>

			
		<form>
    	<div id="radio" style="text-align:center;">
<?php

    foreach($measure_type as $type)
		{
			echo '<input type="radio" id="'.$type.'" name="radio" onclick="changePlot(\''.$type.'\')" /><label for="'.$type.'"> '.$type.' </label>';
		}
?>
    	</div>
		</form>
		<div id='placeholder' style='margin:auto;width:600px;height:300px;'></div>
		<div id='overview' style='margin:auto;margin-top:20px;width:400px;height:50px'></div>
		<form>
			<div id="radioPeriod" style="text-align:center;">
				  <input type="radio" id="whole" name="radioPeriod" checked="checked" /><label for="whole">Whole</label>
				  <input type="radio" id="lastWeek" name="radioPeriod"  /><label for="lastWeek">Week</label>
				  <input type="radio" id="lastDay" name="radioPeriod"  /><label for="lastDay">Day</label>
				  <input type="radio" id="lastH" name="radioPeriod"  /><label for="lastH">3h</label>
				  <input type='checkbox' id='lissage' /><label for="lissage">Lissage</label>
			</div>
		</form>
		<p id="nbMesure"> Il y a actuellement <?php echo count($measures); ?> mesures affichées. </p>
	</div>
	</body>
</html>
