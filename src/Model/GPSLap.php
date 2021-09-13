<?php

namespace App\Model;

class GPSLap {

	var $starting_track_point;
	var $ending_track_point;
	var $total_elapsed_time;
	var $total_distance;

	function __construct($starting_point, $ending_point)
	{
    $this->starting_track_point = $starting_point;
    $this->ending_track_point = $ending_point;
    $this->total_elapsed_time = $ending_point->timestamp - $starting_point->timestamp;
    $this->total_distance = $ending_point->distance - $starting_point->distance;
	}

	function distanceInMiles()
	{
	  return $this->total_distance * MILES_PER_METER;
	}

} // end class GPSLap
