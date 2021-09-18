<?php

namespace App\Service;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use App\Service\FITParser;
use App\Model\GPSTrack;
use App\Model\GPSTrackPoint;

class GPSTrackFactory
{

  protected $options;
  protected $cache;
  protected $fit_parser;

  public function __construct(array $options = [], CacheInterface $cacheApp, FITParser $fit_parser)
  {
    $resolver = new OptionsResolver();
    $this->configureOptions($resolver);
    $this->options = $resolver->resolve($options);

    $this->cache = $cacheApp;
    $this->fit_parser = $fit_parser;
  }

  public function disableCaching()
  {
    $this->options['use_cache'] = false;
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'use_cache'                   => true,
      'set_distance_from_coords'    => false,
    ]);
  }

  public function buildTrackFromFile($path)
  {
    $cache_key = GPSTrackFactory::cacheKeyFromFilePath($path);

    if($this->options['use_cache']){
      return $this->cache->get($cache_key, function(){
        return $this->_buildTrackFromFile($path);
      });
    }
    return $this->_buildTrackFromFile($path);
  }

  private function _buildTrackFromFile($path)
  {
    $file_type = $this->detectFileType($path);

    switch ($file_type) {
      case 'unicsv':
        $track = $this->buildTrackFromCSVFile($path);
        break;

      case 'garmin_fit':
        $track = $this->buildTrackFromFITFile($path);
        break;

      case 'gpx':
        $track = $this->buildTrackFromGPXFile($path);
        break;

      default:
        throw new \Exception('File type '.$file_type.' is not supported');
        break;
    }
    return $track;
    // TODO: the original factory sets distance and speed for all track points
    // as well as several totals for the track. Feels out of place here.
  }

  public function buildTrackFromFITFile($path)
  {
    $this->fit_parser->setFitPath($path);
    $track = new GPSTrack();
    $session_data = $this->fit_parser->activitySessionData();

    // TODO: clean this up to assign dynamically - create $_properties structure
    // TODO: figure out why dates are off on fit files

    // TODO: should consider just adding track points and letting that calculate track summary info
    if(array_key_exists('session.start_time', $session_data))             $track->start_time         = $session_data['session.start_time'] + (20*365.25*24*60*60)-(24*60*60);
    if(array_key_exists('session.num_laps', $session_data))               $track->num_laps           = $session_data['session.num_laps'];
    if(array_key_exists('session.sport', $session_data))                  $track->sport              = $this->fitEnumData('sport', $session_data['session.sport']);
    if(array_key_exists('session.total_timer_time[s]', $session_data))    $track->total_timer_time   = $session_data['session.total_timer_time[s]'];
    if(array_key_exists('session.total_elapsed_time[s]', $session_data))  $track->total_elapsed_time = $session_data['session.total_elapsed_time[s]'];
    if(array_key_exists('session.total_distance[m]', $session_data))      $track->total_distance     = $session_data['session.total_distance[m]'];
    if(array_key_exists('session.total_calories[kcal]', $session_data))   $track->total_calories     = $session_data['session.total_calories[kcal]'];
    if(array_key_exists('session.total_ascent[m]', $session_data))        $track->total_ascent       = $session_data['session.total_ascent[m]'];
    if(array_key_exists('session.total_descent[m]', $session_data))       $track->total_descent      = $session_data['session.total_descent[m]'];
    if(array_key_exists('session.avg_speed[m/s]', $session_data))         $track->average_speed      = $session_data['session.avg_speed[m/s]'];
    if(array_key_exists('session.max_speed[m/s]', $session_data))         $track->max_speed          = $session_data['session.max_speed[m/s]'];
    if(array_key_exists('session.avg_heart_rate[bpm]', $session_data))    $track->average_heart_rate = $session_data['session.avg_heart_rate[bpm]'];
    if(array_key_exists('session.max_heart_rate[bpm]', $session_data))    $track->max_heart_rate     = $session_data['session.max_heart_rate[bpm]'];

    $records = $this->fit_parser->activityRecordsData();
    $max_cadence = 0;
    $cadence_sum = 0;
    $cadence_count = 0;

    foreach($records as $record){

      if(!$track->start_time) {
        $track->start_time = $record['record.timestamp[s]'];
      }

      $track_point = new GPSTrackPoint();
      $track_point->initFromFITArray($record, $track->start_time);
      $track->addTrackPoint($track_point);

      if(array_key_exists('record.cadence[rpm]', $record)){
        $cadence = (int)$record['record.cadence[rpm]'];
        $cadence_sum += $cadence;
        $cadence_count++;
        if($cadence > $max_cadence){ $max_cadence = $cadence; }
      }
    }
    if($cadence_sum > 0) $track->average_cadence  = round($cadence_sum/$cadence_count);
    $track->max_cadence      = $max_cadence;

    return $track;
  }

  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  public static function detectFileType($path)
  {
    $extension_map = GPSTrackFactory::extensionMap();
    $path_parts = pathinfo($path);
    $extension = $path_parts['extension'];
    if(!array_key_exists($extension, $extension_map)){
      throw new \Exception('GPSTrackFactory:detectFileType does not support the '.$extension.' extension');
    }
    return $extension_map[$extension];
  }

  public static function extensionMap()
  {
    return [
      'fit' => 'garmin_fit',
      'csv' => 'unicsv',
      'gpx' => 'gpx'
    ];
  }

  public function fitEnumData($type, $value)
  {
    $enum_data = [
      'sport' => [  // Have capitalised and replaced underscores with spaces.
        0 => 'Generic',
        1 => 'Running',
        2 => 'Cycling',
        3 => 'Transition',
        4 => 'Fitness equipment',
        5 => 'Swimming',
        6 => 'Basketball',
        7 => 'Soccer',
        8 => 'Tennis',
        9 => 'American football',
        10 => 'Training',
        11 => 'Walking',
        12 => 'Cross country skiing',
        13 => 'Alpine skiing',
        14 => 'Snowboarding',
        15 => 'Rowing',
        16 => 'Mountaineering',
        17 => 'Hiking',
        18 => 'Multisport',
        19 => 'Paddling',
        254 => 'All'
      ]
    ];
    return isset($enum_data[$type][$value]) ? $enum_data[$type][$value] : 'unknown';
  }
}
