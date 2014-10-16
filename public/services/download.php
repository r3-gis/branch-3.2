<?php
require_once('../../config/config.php');
require_once 'include/mapImage.php';

$options = array('image_format'=>'gtiff', 'output_format'=>'geotiff');
if($_REQUEST['format'] == 'png') {
	$options['image_format'] = 'png';
	$options['output_format'] = 'png';
	$options['save_image'] = true;
	$options['file_name'] = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'gc_mapimage', 'png');
} else if($_REQUEST['format'] == 'jpeg') {
	$options['image_format'] = 'jpeg';
	$options['output_format'] = 'jpeg';
	$options['save_image'] = true;
	$options['file_name'] = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'gc_mapimage', 'jpeg');
}

if(!empty($_REQUEST['tiles']) && is_array($_REQUEST['tiles'])) {
	$tiles = $_REQUEST['tiles'];
} else {
	die(json_encode(array('result' => 'error', 'error' => 'No tiles')));
}

if(empty($_REQUEST['viewport_size']) || !is_array($_REQUEST['viewport_size']) || count($_REQUEST['viewport_size']) != 2) {
	die(json_encode(array('result' => 'error', 'error' => 'No size')));
}
$imageSize = $_REQUEST['viewport_size'];

if(empty($_REQUEST['srid'])) die(json_encode(array('result' => 'error', 'error' => 'No srid')));
$srid = $_REQUEST['srid'];
if(strpos($_REQUEST['srid'], ':') !== false) {
	list($options['auth_name'], $srid) = explode(':', $_REQUEST['srid']);
}

if(!empty($_REQUEST['scale_mode'])) $options['scale_mode'] = $_REQUEST['scale_mode'];
if(!empty($_REQUEST['fixed_size'])) $options['fixed_size'] = $_REQUEST['fixed_size'];
if(!empty($_REQUEST['scale'])) $options['scale'] = $_REQUEST['scale'];
if(!empty($_REQUEST['center'])) $options['center'] = $_REQUEST['center'];
if(!empty($_REQUEST['dpi']) && is_numeric($_REQUEST['dpi'])){
	$options['dpi'] = (int) $_REQUEST['dpi'];
	if($options['dpi'] !== 96){
		$multiply = $options['dpi']/96;
		$imageSize[0] =(int) ($imageSize[0] * $multiply);   //check if > 2048
		$imageSize[1] =(int) ($imageSize[1] * $multiply);
		$options['dpi'] = 96;
		//var_dump($imageSize);
	}
}
if(!empty($_REQUEST['extent'])) $options['extent'] = explode(',', $_REQUEST['extent']);
if(isset($_REQUEST['scalebar'])) $options['scalebar'] = $_REQUEST['scalebar'];
			
try {
	$mapImage = new mapImage($tiles, $imageSize, $srid, $options);
	$imageUrl = $mapImage->getImageUrl();
} catch (Exception $e) {
    die(json_encode(array('result' => 'error', 'error' => $e->getMessage())));
}
die(json_encode(array('result' => 'ok', 'file' => $imageUrl, 'format' => $options['output_format'])));