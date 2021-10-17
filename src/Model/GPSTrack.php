<?php

namespace App\Model;

use Twig\Environment;
use App\Model\FIT\GlobalProfile;
use function Symfony\Component\String\u;

class GPSTrack {

	var $track_points = array();    // an array of GPSTrackPoints
	var $input_file_path;           // the path to a file containing the GPS track
	var $start_time;          // timestamp of the first track point
	var $num_laps;
	var $sport;
	var $total_timer_time;
	var $total_elapsed_time;
	var $total_distance;
	var $total_calories;
	var $total_ascent;
	var $total_descent;
	var $average_speed;
	var $max_speed;
	var $average_heart_rate;
	var $max_heart_rate;
	var $average_cadence;
	var $max_cadence;
	var $name;
	var $type;

	// TODO: These constants should be defined someplace more universal
	const MILES_PER_METER = 0.000621371;
  const FEET_PER_METER  = 3.28084;
	const FIT_EPOCH_DELTA = 631065600;

	// TODO: I think we'll want to move all rendering into a TrackRenderer service or something like that.
	protected $twig;

	public function getTotalDistanceInMiles()
	{
    return round($this->total_distance * self::MILES_PER_METER, 3);
  }

	/**
   * Get properly formatted string for the track start time
	 *
	 * @return string
	 */
	public function getStartTimeString(){
		return gmdate('Y-m-d\TH:i:s.v\Z', $this->start_time);
	}

  // set the distance property of each track point based on the calculated distance
  // between two track points
  public function setTrackPointsDistanceFromCoords()
  {
    // TODO: create distance unit constants (meters, kilometers)
    $this->track_points[0]->distance = 0;
    for ($i=1; $i < count($this->track_points); $i++) {
      $this->track_points[$i]->distance =
        $this->track_points[$i-1]->distance
        + $this->track_points[$i-1]->distanceToTrackPoint($this->track_points[$i], 'm');
	  }
  }

	/** TREADMILL FUNCTIONS */

	/**
   * Set corrected distance values for a portion of the track
	 *
	 * The goal is to provide inputs that describe what the distance or pace should
	 * have been for some portion of the original track (which portion is described
	 * by either time or distance). The track is then adjusted to meet the provided
	 * parameters
	 *
	 * @param array $pace_scheme an array of arrays, each top level element describes
	 *		the desired pace for a section of the track through an array of the form
	 * 		['start_time', 'end_time', 'pace'] where each is given in seconds. Pace
	 * 		is specifically seconds per mile.
	 * @param float $percent_perfect value between 0 and 1 to determine what $percentage
	 * 		of the adjusted distance is determined by the recorded distance vs. the
	 * 		"perfect" distance that would be dictated by the pace.
   */
  public function setTrackPointsDistanceFromPaceScheme($pace_scheme, $percent_perfect = 0)
  {
    // TODO validate the $pace_scheme
		foreach ($pace_scheme as $pace_scheme_element) {
			$start_time = $pace_scheme_element['start_time'];
			$end_time   = $pace_scheme_element['end_time'];
			$pace 			= $pace_scheme_element['pace'];

			// figure out which track points will be modified to the desired pace
			$starting_index = $this->getIndexForTrackPointAtElapsedSeconds($start_time);
			$ending_index 	= $this->getIndexForTrackPointAtElapsedSeconds($end_time);
			if($starting_index < 0 || $ending_index < 0){
				// TODO handle out of bounds
				echo "starting index: $starting_index, ending_index: $ending_index\n";
			}
			$original_start_distance = $this->getTrackPoint($starting_index)->distance;
			$original_end_distance   = $this->getTrackPoint($ending_index)->distance;

			// figure out what the distance should have been for the segment (in meters)
			$segment_duration = $end_time - $start_time;

			// if $pace == 0, set the corrected distance to 0
			$corrected_distance = ($pace == 0) ? 0 : $segment_duration/$pace/MILES_PER_METER;

			// figure out the recorded distance for the segment (in meters)
			$recorded_distance = $original_end_distance - $original_start_distance;

			// calculate adjustment factor
			$adjustment_factor = $corrected_distance/$recorded_distance;
			$adjustment_distance = $corrected_distance - $recorded_distance;

			// get the timestamp for the first trackpoing in the segment
			$starting_timestamp = $this->getTrackPoint($starting_index)->timestamp;

			for ($i=$starting_index; $i < $this->numberOfTrackPoints(); $i++) {
				if($i <= $ending_index){

					if($pace == 0) {
						$new_distance = $original_start_distance;
					} else {
						$calculated_distance = ($this->getTrackPoint($i)->distance - $original_start_distance) * $adjustment_factor;
						$perfect_distance = ($this->getTrackPoint($i)->timestamp - $starting_timestamp) / ($pace * MILES_PER_METER);
						$new_distance = $calculated_distance * (1 - $percent_perfect) + $perfect_distance * $percent_perfect  + $original_start_distance;
					}
				} else {
					$new_distance = $this->getTrackPoint($i)->distance + $adjustment_distance;
				}
				$this->track_points[$i]->distance = $new_distance;
			}
		}
  }

	/**
   * Get the index of the first track point at or after elapsed seconds
	 *
	 * @param int $seconds number of seconds since the start of the track
	 * @return int index of the desired track point or -1 if not found
   */
	public function getIndexForTrackPointAtElapsedSeconds($seconds)
	{
		$t = $this->start_time + $seconds;
		for($i=0; $i < $this->numberOfTrackPoints(); $i++){
			if($this->track_points[$i]->timestamp >= $t){
				return $i;
			}
    }
		return -1;
	}

	/**
   * Get the nth trackpoint (zero based)
   */
  public function getTrackPoint($i)
  {
    return $this->track_points[$i];
  }

  /**
   * Set the speed of each track point based on time and distance between track points
   */
  public function setTrackPointsSpeedFromCoords()
  {
    $this->track_points[0]->speed = 0;
    for ($i=1; $i < count($this->track_points); $i++) {
      $this->track_points[$i]->speed =
        $this->track_points[$i]->speedBetweenHereAndTrackPoint($this->track_points[$i-1]);
	  }
  }

  /**
   * Check to see if distance is greater than zero on more than half of the track points
   */
  public function trackPointsHaveDistance()
  {
    $num_track_points_w_distance = 0;
    foreach($this->track_points as $track_point){
      if($track_point->distance > 0){
        $num_track_points_w_distance++;
      }
    }
    return (bool) ($num_track_points_w_distance/count($this->track_points) > 0.5);
  }

  /**
   * Check to see if speed is greater than zero on more than half of the track points
   */
  public function trackPointsHaveSpeed()
  {
    $num_track_points_w_speed = 0;
    foreach($this->track_points as $track_point){
      if($track_point->speed > 0){
        $num_track_points_w_speed++;
      }
    }
    return (bool) ($num_track_points_w_speed/count($this->track_points) > 0.5);
  }

  public function setTotalDistanceFromLastTrackPoint()
  {
    $this->total_distance = $this->getLastTrackPoint()->distance;
  }

	function coordinates()
	{
	  $coords = array();
	  foreach ($this->track_points as $track_point) {
	    if($track_point->coord->latitude && $track_point->coord->longitude) {
  	    $coords[] = array($track_point->coord->latitude, $track_point->coord->longitude);
  	  }
	  }
	  return $coords;
	}

	function timeForDistanceFromOffset($distance, $offset = 0)
	{
    $starting_track_point = null;
    $ending_track_point = null;
    foreach ($this->track_points as $track_point){
      if(!$starting_track_point && $track_point->distance >= $offset){
        $starting_track_point = $track_point;
      }
      if(isset($starting_track_point) && ($track_point->distance - $starting_track_point->distance) >= $distance) {
        $ending_track_point = $track_point;
        break;
      }
    }
    return $ending_track_point->timestamp - $starting_track_point->timestamp;
	}

	function setTrackStartTimeFromTrackPoint($track_point)
	{
	  $this->start_time = $track_point->timestamp;
	}

	function appendTrackPoint($track_point, $distance_offset = 0)
	{
      if(!$this->start_time) {
				$this->start_time = $track_point->timestamp;
			}

			if($track_point->timestamp && !$track_point->elapsed_time){
  	    $track_point->elapsed_time = $track_point->timestamp - $this->start_time;
  	  }

  	  if(!$track_point->distance){
  	    if($last_track_point = $this->getLastTrackPoint()){
    	    $track_point->distance = $last_track_point->distance
    	      + $last_track_point->distanceToTrackPoint($track_point, 'm')
    	    ;
    	  } else {
    	    $track_point->distance = 0;
    	  }
  	  } else {
    	  $track_point->distance -= $distance_offset;
    	}

    	if($track_point->distance > $this->total_distance){
    	  $this->total_distance = $track_point->distance;
    	}

    	if($track_point->elapsed_time > $this->total_elapsed_time){
    	  $this->total_elapsed_time = $track_point->elapsed_time;
    	}

  	  $this->track_points[] = $track_point;
	}

	/**
	 * Adds a track point
	 *
	 * Unlike appendTrackPoint, addTrackPoint simply inserts the point into the
	 * track_points array without updating any track info
	 *
	 * @param GPSTrackPoint $track_point
	 */
	public function addTrackPoint($track_point)
	{
      $this->track_points[] = $track_point;
	}

	public function getFirstTrackPoint()
	{
	  return $this->track_points[0];
	}

	public function getLastTrackPoint()
	{
	  return end($this->track_points);
	}

	public function getInterpolatedPointAtDistance($distance)
	{
	  $real_points = $this->getTrackPointsNearestDistance($distance);
	  $percentage = ($distance - $real_points[0]->distance)
	    / ($real_points[1]->distance - $real_points[0]->distance);

	  $new_point = new GPSTrackPoint();
	  $new_point->coord = GPSCoord::interpolatedCoord(
	    $real_points[0]->coord,
	    $real_points[1]->coord,
	    $percentage
	  );
	  return $new_point;
	}


	// TODO: optimize to guess approximate location in array
	public function getTrackPointsNearestDistance($distance)
	{
    for($i=0; $i < $this->numberOfTrackPoints(); $i++){
      if($this->track_points[$i]->distance > $distance){
        return array(
          $this->track_points[$i-1],
          $this->track_points[$i]
        );
      }
    }
	}

	// If a timestamp matches exactly, return the trackpoint in an array of 1
	// If it doesn't, return the points before and after
	// This is sloppy
	public function getTrackPointsNearestTimestamp($t)
	{
    for($i=0; $i < $this->numberOfTrackPoints(); $i++){
			if($this->track_points[$i]->timestamp == $t){
				return array(
					$this->track_points[$i]
				);
			}
			if($this->track_points[$i]->timestamp > $t){
        return array(
          $this->track_points[$i-1],
          $this->track_points[$i]
        );
      }
    }
	}

	/**
	 * Calcule the total_ascent and total_descent from the track points
	 *
	 * @param bool $set_track_values  If true, the properties of the track will be set
	 *
	 * @return array An array containing the calculated total_ascent and total_descent
	 */
	public function calculateTotalAscentAndDescent($set_track_values = true)
	{
	  $total_ascent   = 0;
	  $total_descent  = 0;
	  $prev_elevation = $this->getFirstTrackPoint()->getElevation();

	  foreach ($this->track_points as $track_point){
	    if($track_point->getElevation() > $prev_elevation) {
	      $total_ascent += $track_point->getElevation() - $prev_elevation;
	    }
	    if($track_point->getElevation() < $prev_elevation) {
	      $total_descent += $prev_elevation - $track_point->getElevation();
	    }
	    $prev_elevation = $track_point->getElevation();
	  }

	  if($set_track_values){
	    $this->total_ascent  = $total_ascent;
	    $this->total_descent = $total_descent;
	  }
	  return array("total_ascent" => $total_ascent, "total_descent" => $total_descent);
	}

	/**
	 * Calcule the average speed
	 *
	 * @param bool $set_track_value  If true, the avg speed for the track will be set
	 *
	 * @return float average speed of the track
	 */
	public function calculateAverageSpeed($set_track_values = true)
	{
	  // TODO: total_elapsed_time should really be total_moving_time (which is total_timer_time for now)
	  if($this->total_elapsed_time == 0) $average_speed = 0;
    else $average_speed = $this->total_distance/$this->total_elapsed_time;
	  if($set_track_values){
	    $this->average_speed = $average_speed;
	  }
	  return $average_speed;
	}

	/**
	 * Calcule the max speed
	 *
	 * @param bool $set_track_value  If true, the max speed for the track will be set
	 *
	 * @return float max speed of the track
	 */
	public function calculateMaxSpeed($set_track_values = true)
	{
	  $max_speed_track_point = $this->maxSpeedTrackPoint();
	  if($set_track_values){
	    $this->max_speed = $max_speed_track_point->speed;
	  }
	  return $max_speed_track_point->speed;
	}

	/**
	 * Find the track point that had the max speed for the ride
	 */
  public function maxSpeedTrackPoint()
  {
    $max_speed = 0;
	  $max_speed_track_point = $this->track_points[0];
	  foreach ($this->track_points as $track_point){
	    if($track_point->speed > $max_speed) {
	      $max_speed = $track_point->speed;
	      $max_speed_track_point = $track_point;
	    }
	  }
	  return $max_speed_track_point;
  }

	function maxElevationTrackPoint()
	{
	  $max_elevation = 0;
	  $max_elevation_track_point = null;
	  foreach ($this->track_points as $track_point){
	    if($track_point->getElevation() > $max_elevation) {
	      $max_elevation = $track_point->getElevation();
	      $max_elevation_track_point = $track_point;
	    }
	  }
	  return $max_elevation_track_point;
	}

	function minElevationTrackPoint()
	{
	  $min_elevation = 100000;
	  $min_elevation_track_point = null;
	  foreach ($this->track_points as $track_point){
	    if($track_point->getElevation() < $min_elevation) {
	      $min_elevation = $track_point->getElevation();
	      $min_elevation_track_point = $track_point;
	    }
	  }
	  return $min_elevation_track_point;
	}

	function numberOfTrackPoints()
	{
	  return count($this->track_points);
	}

	/* OUTPUT FUNCTIONS */

	function toCSVString()
	{
		$csv = GPSTrackPoint::getCSVHeader()."\n";
		foreach($this->track_points as $track_point) {
			$csv .= $track_point->toCSVString()."\n";
		}
		return $csv;
	}

	function json()
	{
	  return json_encode($this);
	}

	function gmapsPolylinePathArray()
	{
	  $points = array();
	  foreach($this->track_points as $track_point){
	    $points[] = array(
	      'lat'=>$track_point->getLatitude(),
	      'lng'=>$track_point->getLongitude(),
	    );
	  }
	  return $points;
	}

	function elevationPoints()
	{
	  $elevation_points = array();
	  foreach ($this->track_points as $track_point){
      if(($elevation = $track_point->getElevation()) && ($distance = $track_point->getDistanceInMiles())){
        $elevation_points[] = array($distance, $elevation);
      }
    }
	  return $elevation_points;
	}

	function findTrackPointNearestLocation($search_coord)
	{
	  $nearest_track_point = $this->track_points[0];
	  $distance_to_nearest_trackpoint = $nearest_track_point->coord->distanceToCoord($search_coord);

	  foreach($this->track_points as $track_point) {
			$distance = $track_point->coord->distanceToCoord($search_coord);

			if($distance < $distance_to_nearest_trackpoint){
			  $nearest_track_point = $track_point;
        $distance_to_nearest_trackpoint = $distance;
			}
		}
		return $nearest_track_point;
	}

	public function findTrackPointsNearLocation($search_coord, $range)
	{
	  $track_points_inside_range = array();
	  foreach($this->track_points as $track_point) {
			if($track_point->coord->distanceToCoord($search_coord) <= $range){
			  $track_points_inside_range[] = $track_point;
			}
		}
		return $track_points_inside_range;
	}

	function findLapTrackPoints($lap_marker_coord, $lap_marker_threshold, $lap_distance, $lap_distance_threshold)
	{
    $n = $this->numberOfTrackPoints();
    $lap_track_points = array();
    $nearest_track_point = null;
    $distance_to_nearest_trackpoint = $lap_marker_threshold;

    for($i=0; $i<$n; $i++){
      $tp = $this->track_points[$i];
      $d = round($tp->distanceToCoord($lap_marker_coord, 'm'));

      if($d <= $lap_marker_threshold){
//        echo "distance from marker: $d | ";
//        echo "time: $tp->elapsed_time | distance: $tp->distance <br>";
        // the current trackpoint is less than the lap_marker_threshold from the lap_marker_coord
        if($d < $distance_to_nearest_trackpoint){
          $nearest_track_point = $tp;
          $distance_to_nearest_trackpoint = $d;
        }
      } else {
        if($nearest_track_point !== null){ // if a nearest_track_point has been set, but we're no longer inside the threshold, capture the trackpoint as a lap point

          $num_captured_lap_points = count($lap_track_points);
          if ( $num_captured_lap_points == 0 ) {
            $lap_track_points[] = $nearest_track_point;
//            echo " *** added track point | time: $nearest_track_point->elapsed_time | distance: $nearest_track_point->distance *** <br><br>";
          } else {
            $lap_start = $lap_track_points[$num_captured_lap_points - 1];
            $distance_along_track = $lap_start->distanceAlongTrackToTrackPoint($nearest_track_point);
//            echo "Distance from last lap point: $distance_along_track <br><br>";
            if(abs($distance_along_track - $lap_distance) <= $lap_distance_threshold ){
//              echo " *** added track point | time: $nearest_track_point->elapsed_time | distance: $nearest_track_point->distance*** <br><br>";
              $lap_track_points[] = $nearest_track_point;
            }
          }
          $nearest_track_point = null;
          $distance_to_nearest_trackpoint = $lap_marker_threshold;
        }
      }
    }
    return $lap_track_points;
	}

	function laps($lap_marker_coord, $lap_marker_threshold, $lap_distance, $lap_distance_threshold)
  {
    $laps = array();
    $ltps = $this->findLapTrackPoints($lap_marker_coord, $lap_marker_threshold, $lap_distance, $lap_distance_threshold);
    $n = count($ltps);
    for ( $i = 0; $i < $n - 1; $i++ ){
      $laps[] = new GPSLap($ltps[$i], $ltps[$i + 1]);
    }
    return $laps;
  }

  function getGPX($extended = true, $v2 = true)
  {
		if(!$this->twig){
			throw new \Exception("A twig environment must be set in order to render a GPX version of this track", 1);
		}

    $template = $extended ? 'extendedGPXTemplate.gpx.twig': 'basicGPXTemplate.gpx.twig';
		$template = $v2 			? 'extendedGPXTemplate2.gpx.twig': 'basicGPXTemplate.gpx.twig';

    return $this->twig->render($template, array(
      'creator'     => 'Custom GPX Writer',
      'track_name'  => $this->name,
			'track_type'  => $this->type,
      'track'       => $this
    ));

  }

  function getTCX()
  {
		if(!$this->twig){
			throw new \Exception("A twig environment must be set in order to render a GPX version of this track", 1);
		}

    return $this->twig->render('TCXTemplate.tcx.twig', array(
      'creator'     => 'Custom TCX Writer',
      'track_name'  => 'My TCX Track',
      'track'       => $this
    ));
  }

  function getUnicsv()
  {
    $csv = 'No,Latitude,Longitude,Altitude,Temperature,Speed,Cadence,Power,Date,Time'."\n";
    for($i=0; $i < $this->numberOfTrackPoints(); $i++){
      $csv .= ($i+1).','
        .$this->track_points[$i]->getLatitude().','
        .$this->track_points[$i]->getLongitude().','
        .$this->track_points[$i]->altitude.','
        .$this->track_points[$i]->temperature.','
        .$this->track_points[$i]->speed.','
        .$this->track_points[$i]->cadence.','
        .$this->track_points[$i]->power.','
        .date('Y/m/d,h:i:s', $this->track_points[$i]->timestamp)."\n"
      ;
    }
    return $csv;
  }

  public function reduceToSubSectionOfTrack($offset, $length)
  {
    $this->track_points = array_splice($this->track_points, $offset, $length);
  }

  // this function will adjust all times from the start_point to the
  // end point such that the mid_point will be + the adjustment time (given in seconds)
  // and all other points will be fractionally adjusted
  function adjustTime($start_point, $mid_point, $end_point, $adjustment)
  {
    $elapsed_time_from_start_to_mid = $mid_point->timestamp - $start_point->timestamp;
    $elapsed_time_from_mid_to_end   = $end_point->timestamp - $mid_point->timestamp;

    $n = $this->numberOfTrackPoints();

    $start_point_index  = $n;
    $mid_point_index    = $n;
    $end_point_index    = $n;

    for($i=0; $i<$n; $i++){
      $current_track_point =& $this->track_points[$i];
      if($current_track_point == $start_point){
        $start_point_index = $i;
      }
      if($current_track_point == $mid_point){
        $mid_point_index = $i;
      }
      if($current_track_point == $end_point){
        $end_point_index = $i;
      }
    }

    for($i = $start_point_index; $i<$end_point_index; $i++){

      if($i < $mid_point_index){
        $percent_into_segment = ($current_track_point->timestamp - $start_point->timestamp)/$elapsed_time_from_start_to_mid;
        $current_track_point->timestamp += round($percent_into_segment * $adjustment);
      }
      if($i == $mid_point_index){
        $current_track_point->timestamp += $adjustment;
      }
      if($i > $mid_point_index){
        $percent_into_segment = ($current_track_point->timestamp - $end_point->timestamp)/$elapsed_time_from_mid_to_end;
        $current_track_point->timestamp += round($percent_into_segment * $adjustment);
      }
    }

  }

	function initFromFITFile($path)
	{
		// functionality moved to GPSTrackFactory - leaving signature and comment
		// for now in case this is called externally somewhere I didn't find.
	}

	function setTrackPointsFromTCXFile($path)
	{
		// functionality moved to GPSTrackFactory - leaving signature and comment
		// for now in case this is called externally somewhere I didn't find.
	}

	function setTwig(Environment $twig)
	{
		if('Twig\Environment' !== get_class($twig)){
			throw new \TypeError(sprintf('Argument $twig must be of type Twig\Environment, recieved "%s"', get_class($twig)));
		}
		$this->twig = $twig;
	}

  public function setPropertiesFromSessionData($session_data)
  {
    $map = self::propertySessionMap();
    foreach ($map as $property => $session_data_key) {
      if(array_key_exists($session_data_key, $session_data)){

				// First look for $this->setPropertyFromSessionData(), then $this->setProperty(), and then use $this->property =
				$property_camel = u($property)->camel()->title();
				if(method_exists($this, 'set'. $property_camel . 'FromSessionData')){
					$this->{'set' . $property_camel . 'FromSessionData'}($session_data[$session_data_key]);
				} else {
					if(method_exists($this, 'set'. $property_camel)){
						$this->{'set' . $property}($session_data[$session_data_key]);
					} else {
						$this->{$property} = $session_data[$session_data_key];
					}
				}
      }
    }
  }

	public function setStartTimeFromSessionData($start_time)
  {
		$this->start_time = $start_time + self::FIT_EPOCH_DELTA;
  }

	public function setSportFromSessionData($sport)
  {
		$this->sport = GlobalProfile::getFieldTypeValue('sport', $sport);
  }

	public static function propertySessionMap()
  {
    return [
      'start_time'          => 'session.start_time',
      'num_laps'            => 'session.num_laps',
      'sport'               => 'session.sport',
      'total_timer_time'    => 'session.total_timer_time[s]',
      'total_elapsed_time'  => 'session.total_elapsed_time[s]',
      'total_distance'      => 'session.total_distance[m]',
      'total_calories'      => 'session.total_calories[kcal]',
      'total_ascent'        => 'session.total_ascent[m]',
      'total_descent'       => 'session.total_descent[m]',
      'average_speed'       => 'session.avg_speed[m/s]',
      'max_speed'           => 'session.max_speed[m/s]',
      'average_heart_rate'  => 'session.avg_heart_rate[bpm]',
      'max_heart_rate'      => 'session.max_heart_rate[bpm]'
    ];
  }


} // end class GPSTrack
