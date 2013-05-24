<?php

namespace Faker\Provider;
use Laravel\Log;
require_once("laravel/log.php");
class Image extends \Faker\Provider\Base{
	
	// Configuration
	public static $defaultNumberOfImages = 128;
	
	// Pre data
	// -- $colors will contain 256 randomly created color as a color palate for imagecreate
	protected static $colors = array();
	
	// State
	// -- $imageSets will contain links to $images with different resoloutions in keys with width:height format
	protected static $imageSets = array();
	private static $virgin = true;
	
	public function __construct($generator){
		parent::__construct($generator);
		
		if( array_get(static::$colors, 255, null) == null ){
			for($i = 0; $i<255; $i++){
				$red = mt_rand(0,255);
				$green = mt_rand(0,255);
				$blue = mt_rand(0,255);
				$color = array(
					0 => $red,
					1 => $green,
					2 => $blue
				);
				array_set(static::$colors, $i, $color);
			}
		}
	}
	
	// Create a random image
	public function image($width, $height, $location){
		$image = imagecreate($width, $height);
		for($row = 1; $row <= $height; $row++){
			for($column = 1; $column <= $width; $column++){
				$color_raw = static::randomElement(static::$colors);
				$color = imagecolorallocate($image, $color_raw[0], $color_raw[1], $color_raw[2]);
				imagesetpixel($image, $column -1, $row -1, $color);
			}
		}
		
		// Validating location variable
		$location = str_replace('/', '\\', $location);
		Log::debug('loc: ' . $location);
		$dir = dirname($location);
		Log::debug('dir: ' . $dir);
		$file_name = str_replace($dir . '\\', '', $location);
		Log::debug('fn1: ' . $file_name);
		$file_name = substr($file_name, 0, 255);
		Log::debug('fn2: ' . $file_name);
		$location = $dir . '\\' . $file_name;
		Log::debug($location);
		
		$success = imagejpeg($image, $location, 65);
		$image = null; // free memory
		if($success){ // if file saved successfuly
			$images = array_get(static::$imageSets, "$width:$height", array() );
			array_push($images, $location);
			array_set(static::$imageSets, "$width:$height", $images);
			
			return true; // hoorraa! :D
		}
		else{
			return false; // Something's comming :(
		}
	}
	
	/**
	 * Creates and caches images on disk
	 * 
	 * @author Pooyan Khosravi
	 * 
	 * @param $num number of cached images
	 * @param $width width of images
	 * @param $height height of images
	 * 
	 * @return true on success, false on failure
	 */ 
	public function cacheImages($num, $width, $height, $baseLocation){
		$images = array_get(static::$imageSets, "$width:$height", array());
		$i = count($images);
		
		for($i; $i<=$num; $i++){
			$this->image($width, $height, $baseLocation . md5(mt_rand()));
		}
	}
	
	public function getImage($width, $height, $location){
		if(static::$virgin){
			$this->cacheImages(static::$defaultNumberOfImages, $width, $height, dirname($location) . '/');
			Log::debug('VIRGINALITY: ' . static::$virgin);
			static::$virgin = false;
		}
		$images = static::$imageSets["$width:$height"];
		return static::randomElement($images);
	}
}