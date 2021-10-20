<?php

namespace App\Service;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use App\Service\FITCSVParser;
use App\Model\GPSTrack;
use App\Model\GPSTrackPoint;

class GPSTrackFactory
{

  protected $options;
  protected $cache;
  protected $fit_parser;

  public function __construct(array $options = [], CacheInterface $cacheApp, FITCSVParser $fit_parser)
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

    $track->setPropertiesFromSessionData($session_data);

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

}
