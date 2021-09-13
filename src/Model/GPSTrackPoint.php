<?php

namespace App\Model;

define('MILES_PER_METER', 0.000621371);
define('FEET_PER_METER', 3.28084);

class GPSTrackPoint {

	var $timestamp; // unix
	var $coord;
	var $altitude; // meters
	var $heart_rate;
	var $cadence;
	var $temperature; // deg C
	var $speed; // m/s
	var $power; // watts

	var $elapsed_time;
	var $distance; // meters

	function initFromXML($track_point_xml, $track_start_time)
	{
		$this->timestamp 	= strtotime($track_point_xml->Time);
		$this->altitude 	= (int)$track_point_xml->AltitudeMeters;
		$this->heart_rate = (int)$track_point_xml->HeartRateBpm->Value;
		$this->cadence 		= (int)$track_point_xml->Cadence;
		$this->distance 	= (float)$track_point_xml->DistanceMeters;

    $this->coord     	= new GPSCoord(
      (float)$track_point_xml->Position->LatitudeDegrees,
      (float)$track_point_xml->Position->LongitudeDegrees
    );

		if($track_start_time) {
			$this->elapsed_time = strtotime($track_point_xml->Time) - $track_start_time;
		}

	}

	function initFromFITArray($record, $track_start_time)
	{
		if(array_key_exists('record.timestamp[s]', $record))    			$this->timestamp 	  = $record['record.timestamp[s]'] + (20*365.25*24*60*60)-(24*60*60);
		if(array_key_exists('record.altitude[m]', $record))		  			$this->altitude 	  = (int)$record['record.altitude[m]'];
		if(array_key_exists('record.enhanced_altitude[m]', $record))	$this->altitude 	  = (int)$record['record.enhanced_altitude[m]'];
		if(array_key_exists('record.heart_rate[bpm]', $record))				$this->heart_rate   = (int)$record['record.heart_rate[bpm]'];
		if(array_key_exists('record.cadence[rpm]', $record))					$this->cadence 		  = (int)$record['record.cadence[rpm]'];
		if(array_key_exists('record.distance[m]', $record))		  			$this->distance 	  = (float)$record['record.distance[m]'];
		if(array_key_exists('record.speed[m/s]', $record))      			$this->speed        = (float)$record['record.speed[m/s]'];
		if(array_key_exists('record.power[watts]', $record))    			$this->power        = (float)$record['record.power[watts]'];
		if(array_key_exists('record.temperature[C]', $record))  			$this->temperature  = (float)$record['record.temperature[C]'];

		if(array_key_exists('record.position_lat', $record)){
	    $this->coord     	= new GPSCoord(
	      (float)$record['record.position_lat[semicircles]'],
	      (float)$record['record.position_long[semicircles]'],
	      'semicircles'
	    );
		}

		if($track_start_time) {
			$this->elapsed_time = $this->timestamp - $track_start_time;
		}
	}

	public function initFromCSVArray($record)
	{
	  if(array_key_exists('Date', $record) && array_key_exists('Time', $record)){
	    $this->timestamp = strtotime($record['Date'].' '.$record['Time']);
	  }
    if(array_key_exists('Altitude', $record))		  $this->altitude 	  = (int)$record['Altitude'];
		if(array_key_exists('Heartrate', $record))	  $this->heart_rate   = (int)$record['Heartrate'];
		if(array_key_exists('Cadence', $record))		  $this->cadence 		  = (int)$record['Cadence'];
		if(array_key_exists('Speed', $record))        $this->speed        = (float)$record['Speed'];
		if(array_key_exists('Power', $record))        $this->power        = (float)$record['Power'];
		if(array_key_exists('Temperature', $record))  $this->temperature  = (float)$record['Temperature'];

		$this->coord     	= new GPSCoord(
      (float)$record['Latitude'],
      (float)$record['Longitude']
    );

	}

	function getLatitude($format = 'degrees'){
		if($this->coord){
			return $this->coord->getLatitude($format);
		} else {
			return null;
		}
	}

	function getLongitude($format = 'degrees'){
		if($this->coord){
			return $this->coord->getLongitude($format);
		} else {
			return null;
		}
  }

  function getElevation(){
    return $this->altitude;
  }

  function getElevationInFeet(){
    return round($this->altitude * FEET_PER_METER, 1);
  }

  function getDistanceInMiles(){
    return round($this->distance * MILES_PER_METER, 3);
  }

  function distanceToCoord($coord, $units = 'km')
  {
    return $this->coord->distanceToCoord($coord, $units);
  }

  function distanceToTrackPoint($tp, $units = 'km')
  {
    return $this->coord->distanceToCoord($tp->coord, $units);
  }

  function distanceAlongTrackToTrackPoint($track_point)
  {
    return $this->distance - $track_point->distance;
  }

	/**
	 * Determine whether two trackpoints have the same coordinates
	 *
	 * @param GPSTrackPoint
	 *
	 * @return bool
	 */
	public function hasSameCoordinatesAsTrackPoint($track_point)
	{
		return (
			$this->getLatitude() == $track_point->getLatitude()
			&& $this->getLongitude() == $track_point->getLongitude()
		);
	}

  /**
   * Calculate time difference between track points
   *
   * A positive value means $this track_point happened after passed in $track_point
   *
   * @param GPSTrackPoint
   *
   * @return int time in seconds
   */
  public function timeAfterTrackPoint($track_point)
  {
    return $this->timestamp - $track_point->timestamp;
  }

  /**
   * Calculate speed between track points
   *
   * A positive value means $this track_point happened after passed in $track_point
   *
   * @param GPSTrackPoint
   *
   * @return int speed in m/s
   */
  public function speedBetweenHereAndTrackPoint($track_point)
  {
    if($this->distance && $track_point->distance){
      $d = $this->distanceAlongTrackToTrackPoint($track_point);
    } else {
      $d = $this->distanceToTrackPoint($track_point);
    }
    $t = $this->timeAfterTrackPoint($track_point);
    if($t == 0) return 0;
    return $d/$t;
  }

  function json()
  {
    return json_encode($this);
  }

	function toCSVString(){
		return
			$this->timestamp	      ."\t".
			$this->elapsed_time	    ."\t".
			$this->getLatitude()		."\t".
			$this->getLongitude() 	."\t".
			$this->altitude		      ."\t".
			$this->distance		      ."\t".
			$this->heart_rate	      ."\t".
			$this->cadence		      ."\t".
			$this->temperature	    ."\t".
			$this->speed            ."\t".
			$this->power		        ."\t"
		;

	}

	public static function getCSVHeader(){
		// TODO: make this more dynamic
		return
			"Time Stamp\t".
			"Seconds Elapsed\t".
			"Latitude\t".
			"Longitude\t".
			"Altitude\t".
			"Distance\t".
			"Heart Rate\t".
			"Cadence\t".
			"Temperature\t".
			"Speed\t".
			"Power\t"
		;
	}

	public function getTimeString(){
		return gmdate('Y-m-d\TH:i:s.v\Z', $this->timestamp);
	}

	// this echos the timestring... I don't know why.
	public function getModifiedTimeString()
	{
	  // I don't remember what the point of the modified timestamp is. This was the origianal modifier.
		// echo gmdate('Y-m-d\TH:i:s\Z', $this->timestamp+(20*365.25*24*60*60)-(24*60*60));
		echo gmdate('Y-m-d\TH:i:s.v\Z', $this->timestamp);
	}

	// get interpoloted point between this point and the passed in point
	public function getInterpolatedPointByTime($t, $next_point)
	{
		// echo "+++++++ We're going to interpolate a point +++++++++\n";
		$percentage = ($t - $this->timestamp) / ($next_point->timestamp - $this->timestamp);

		$new_point = new GPSTrackPoint();
		$new_point->timestamp = $t;
		$new_point->altitude 		= 			GPSTrackPoint::interpolate($this->altitude, 		$next_point->altitude, 		$percentage);
		$new_point->heart_rate	= (int) GPSTrackPoint::interpolate($this->heart_rate, 	$next_point->heart_rate, 	$percentage);
		$new_point->cadence			= (int) GPSTrackPoint::interpolate($this->cadence, 			$next_point->cadence, 		$percentage);
		$new_point->temperature	= (int) GPSTrackPoint::interpolate($this->temperature,	$next_point->temperature, $percentage);
		// $new_point->speed;
		// $new_point->power;

		// print_r($new_point);
		// echo "+++++++             DONE                  +++++++++\n";
		return $new_point;
	}

	public static function interpolate($a, $b, $percentage)
	{
		return (($b - $a) * $percentage) + $a;
	}

} // end class GPSTrackPoint
