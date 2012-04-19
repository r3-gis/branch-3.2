<?php
include_once('printDocument.php');
class mapImage {

	protected $WMSMergeUrl = 'services/gcWMSMerge.php';
	protected $tiles = array();
	protected $extent = array();
	protected $wmsList = array();
	protected $imageSize = array();
	protected $mapSize = array();
	protected $db = null;
	protected $options = array();
	protected $imageFileName = null;
	protected $srid = null;
	
	function __construct($tiles, $imageSize, $srid, $options) {
		$defaultOptions = array(
			'scale_mode'=>'auto', //'auto' calculate extent from bbox, if 'user', calculate extent from center/scale (requires pixels_distance)
			'extent'=>array(),
			'center'=>array(),
			'image_format'=>'png', // or gtiff
			'pixels_distance'=>null, //define the scale (how many meters are represented in the current viewport)
			'auth_name'=>'EPSG',
			'request_type'=>'get-map',
			'fixed_size'=>$imageSize,
            'TMP_PATH' => GC_WEB_TMP_DIR,
			'TMP_URL' => GC_WEB_TMP_URL,
			'dpi' => 72
		);
		$this->options = array_merge($defaultOptions, $options);
		
		$this->tiles = $tiles;
		$this->imageSize = $imageSize;
		$this->db = GCApp::getDB();
		$this->srid = $srid;
		$this->WMSMergeUrl = printDocument::addPrefixToRelativeUrl(PUBLIC_URL.$this->WMSMergeUrl);
		
		if($this->options['scale_mode'] == 'user') {
			if(empty($this->options['center']) || empty($this->options['pixels_distance'])) {
				throw new Exception('Missing center or pixels_distance');
			}
			$this->extent = $this->calculateExtent($this->options['center'], $this->options['pixels_distance']);
		} else {
			if(empty($this->options['extent'])) throw new Exception('Missing extent');
			$this->extent = $this->adaptExtentToSize($this->options['extent']);
		}
		if($this->options['request_type'] == 'get-map') {
			$this->buildWmsList();
		}
	}
	
	public function getWmsList() {
		return $this->wmsList;
	}
	
	public function getExtent() {
		return $this->extent;
	}
	
	public function getImageUrl() {
		if(empty($this->imageFileName)) $this->getMapImage();
		return $this->options['TMP_URL'].$this->imageFileName;
	}
	
	public function getImageFileName() {
		if(empty($this->imageFileName)) $this->getMapImage();
		return $this->imageFileName;
	}
	
	public function getScale($imageWidth) { //imageWidth in meters, get the ratio between the represented meters and the image meters in the horizontal dimension
		return round($this->mapSize[0] / $imageWidth);
	}
	
	protected function buildWmsList() {
		foreach($this->tiles as $key => $tile) {
			$pos = strpos($tile['url'], '?');
			if($pos == false) throw new Exception('URL without query string');
			
			$queryString = substr($tile['url'], ($pos+1));
			$parameters = array();
            $tmpParameters = array();
			parse_str($queryString, $tmpParameters);
            foreach($tmpParameters as $parameterName => $parameterValue) {
                $parameters[strtoupper($parameterName)] = $parameterValue;
            }
			if(isset($tile['opacity']) && !isset($parameters['OPACITY'])) $parameters['OPACITY'] = $tile['opacity'];
            
			$url = substr($tile['url'], 0, $pos);
			$url = printDocument::addPrefixToRelativeUrl($url);
			
			$wms = array('URL'=>$url,'PARAMETERS'=>$parameters);
			array_push($this->wmsList, $wms);
		}
	}
	
	protected function getMapImage() {
		try {
			$extension = 'png';
			if($this->options['image_format'] == 'gtiff') $extension = 'tif';
			
			$this->imageFileName = GCApp::getUniqueRandomTmpFilename($this->options['TMP_PATH'], 'gc_mapimage', $extension);
			
			$requestParameters = json_encode(array(
				'layers'=>$this->wmsList,
				'size'=>$this->imageSize,
				'extent'=>$this->extent,
				'srs'=>$this->options['auth_name'].':'.$this->srid,
				'scalebar' => true,
				'save_image'=>($this->options['image_format'] != 'gtiff'),
				'resolution'=>$this->options['dpi'],
				'file_name'=>$this->options['TMP_PATH'].$this->imageFileName,
				'format'=>$this->options['image_format'],
				'GC_SESSION_ID'=>session_id()
			));
			session_write_close();
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->WMSMergeUrl);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('options'=>$requestParameters));
			$mapImage = curl_exec($ch);
			if(!$requestParameters['save_image']) {
				file_put_contents($this->options['TMP_PATH'].$this->imageFileName, $mapImage);
			}
			curl_close($ch);	
		} catch(Exception $e) {
			throw $e;
		}
	}
	
	protected function adaptExtentToSize($extent) {
		$leftBottom = "st_geomfromtext('POINT(".$extent[0]." ".$extent[1].")', ".$this->srid.")";
		$rightBottom = "st_geomfromtext('POINT(".$extent[2]." ".$extent[1].")', ".$this->srid.")";
		$rightTop = "st_geomfromtext('POINT(".$extent[2]." ".$extent[3].")', ".$this->srid.")";
		$leftTop = "st_geomfromtext('POINT(".$extent[0]." ".$extent[3].")', ".$this->srid.")";
		$sql = "select st_length(st_makeline($leftBottom, $rightBottom)) as w, st_length(st_makeline($leftBottom, $leftTop)) as h";
		$measures = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
		
		if($measures['w'] > $measures['h']) {
			$longEdge = 'w';
			$height = ($measures['w']/$this->imageSize[0])*$this->imageSize[1];
			$buffer = ($height - $measures['h'])/2;
			$width = $measures['w'];
		} else {
			$longEdge = 'h';
			$width = ($measures['h']/$this->imageSize[1])*$this->imageSize[0];
			$buffer = ($width - $measures['w'])/2;
		}
		$this->mapSize = array($width, $height);

		$extentPolygon = "st_polygon(st_makeline(ARRAY[$leftBottom, $rightBottom, $rightTop, $leftTop, $leftBottom]), ".$this->srid.")";
		
		$sql = "select box2d(st_buffer((".$extentPolygon."), $buffer))";
		$box = $this->db->query($sql)->fetchColumn(0);
		$box = GCUtils::parseBox($box);
		
		if($longEdge == 'w') {
			return array($extent[0], $box[1], $extent[2], $box[3]);
		} else {
			return array($box[0], $extent[1], $box[2], $extent[3]);
		}
	}
	
	protected function calculateExtent($center, $pixelDistance) {
		if($this->options['fixed_size'][0] > $this->options['fixed_size'][1]) {
			$longEdge = 'w';
			$shortEdgeDividend = $this->options['fixed_size'][1];
			$longEdgeDividend = $this->options['fixed_size'][0];
		} else {
			$longEdge = 'h';
			$shortEdgeDividend = $this->options['fixed_size'][0];
			$longEdgeDividend = $this->options['fixed_size'][1];
		}

		$shortBuffer = ($shortEdgeDividend / $pixelDistance)/2;
		$longBuffer = ($longEdgeDividend / $pixelDistance)/2;
		$center = "st_geomfromtext('POINT(".$center[0]." ".$center[1].")', ".$this->srid.")";
		$sql = "select box2d(st_buffer($center, $shortBuffer)) as short, box2d(st_buffer($center, $longBuffer)) as long";
		$boxes = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
		$boxes = $this->parseBoxes($boxes);
		
		if($longEdge == 'w') {
			return array($boxes['long'][0], $boxes['short'][1], $boxes['long'][2], $boxes['short'][3]);
		} else {
			return array($boxes['short'][0], $boxes['long'][1], $boxes['short'][2], $boxes['long'][3]);
		}
	}
	
	protected function parseBoxes($boxes) {
		$parsedBoxes = array();
		foreach(array('long', 'short') as $type) {
			$parsedBoxes[$type] = GCUtils::parseBox($boxes[$type]);
		}
		return $parsedBoxes;
	}
}