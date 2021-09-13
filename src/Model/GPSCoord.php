<?php

namespace App\Model;

define ('R', 6371);
define('DEGREES_PER_SEMICIRCLE', 0.00000008381903);

class GPSCoord {

	var $latitude; // stored in degrees
	var $longitude;

	function __construct($lat, $lon, $units = 'degrees'){
    if($units == 'semicircles'){
      $lat *= DEGREES_PER_SEMICIRCLE;
      $lon *= DEGREES_PER_SEMICIRCLE;
    }

	  $this->latitude  = floatval($lat);
	  $this->longitude = floatval($lon);
	}

	function getLatitude($format = 'degrees'){
	  if($format == 'degrees') return $this->latitude;
	  if($format == 'radians') return deg2rad($this->latitude);
	}

	function getLongitude($format = 'degrees'){
	  if($format == 'degrees') return $this->longitude;
	  if($format == 'radians') return deg2rad($this->longitude);
	}

	function toString(){
	  return $this->latitude.','.$this->longitude;
	}

	function distanceToCoord($coord, $units = 'km') { // returned in kilometers
    $lat1 = $coord->getLatitude('radians');
    $lon1 = $coord->getLongitude('radians');
    $lat2 = $this->getLatitude('radians');
    $lon2 = $this->getLongitude('radians');

	  $x = ($lon2-$lon1) * cos(($lat1+$lat2)/2);
    $y = ($lat2-$lat1);
    $d = sqrt($x*$x + $y*$y) * R;
    if($units == 'm') return $d * 1000;
    return $d;
	}

	public static function interpolatedCoord($coord1, $coord2, $percentage)
	{

	  $new_coord = new GPSCoord(
	   (($coord2->latitude - $coord1->latitude) * $percentage) + $coord1->latitude,
	   (($coord2->longitude - $coord1->longitude) * $percentage) + $coord1->longitude
	  );

	  // echo '{'.$coord1->toString().'} and ';
	  // echo '{'.$coord2->toString().'} '.$percentage.'% -> ';
	  // echo '{'.$new_coord->toString().'}'."\n";

	  return $new_coord;
	}

} // end class GPSCoord

// http://www.movable-type.co.uk/scripts/latlong.html
