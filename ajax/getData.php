<?php

require_once("../Netatmo-API/NAApiClient.php");
require_once("../Netatmo-API/Config.php");

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
if(isset($_POST['begin']) && ($_POST['begin'] !='') )
	$date_begin = $_POST['begin'];
else
	$date_begin = $user['date_creation']['sec'];

if(isset($_POST['end']) && ($_POST['end'] !=''))
	$date_end = $_POST['end'];
else	
	$date_end = time();

if(isset($_POST['scale']))
	$scale = $_POST['scale'];
else	
	$scale      = '30min';

if(isset($_POST['type']))
	$type = $_POST['type'];
else	
	$type   = 'Temperature';


if($scale != 'max')
{
	if($type == 'Temperature')
		$type_other='_temp';
	elseif($type == 'Humidity')
		$type_other='_hum';
	elseif($type=='Pression')
		$type_other='_pressure';
	elseif($type =='Noise')
		$type_other='_noise';
	else
		$type_other='';
}

//$measure_type = array('Temperature','CO2','Humidity','Pressure','Noise');
$str_data = array();

$pos = strpos($type,'module');
if ($pos === false)
{
	$measures = $helper->GetMeasures($client,$device["_id"],$date_begin,
														$date_end,null,$scale);
	$str_data[$type] = $helper->SimplifyMeasureForJS($measures,$type);
/*
	if($type_other != '')
	{
		$str_data['min'] = $helper->SimplifyMeasureForJS($measures,'min'.$type_other);
		$str_data['max'] = $helper->SimplifyMeasureForJS($measures,'max'.$type_other);
	}
*/	
	$measures = $helper->GetMeasures($client,$device["_id"],
												$user['date_creation']['sec'],time(),null,"3hours");
	$str_data['overview'] = $helper->SimplifyMeasureForJS($measures,$type);
	
}
else
{
	$measures_module = $helper->GetMeasures($client, $device["_id"], $date_begin,
																$date_end, $module["_id"], $scale);
	$str_data[$type] = $helper->SimplifyMeasureForJS($measures_module,substr($type, 0, -7));
	/*
	if($type_other != '')
	{
		$str_data['min'] = $helper->SimplifyMeasureForJS($measures_module,'min'.$type_other);
		$str_data['max'] = $helper->SimplifyMeasureForJS($measures_module,'max'.$type_other);
	}
	*/
	$measures_module = $helper->GetMeasures($client,$device["_id"],
												$user['date_creation']['sec'],time(),$module["_id"],"3hours");
	$str_data['overview'] = $helper->SimplifyMeasureForJS($measures_module,substr($type, 0, -7));
}

echo json_encode($str_data);
?>
