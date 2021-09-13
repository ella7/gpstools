<?php

namespace App\Service;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use App\Service\FITParser;

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
    echo "file type is: $file_type\n";
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
    // $fit_parser = new FITParser($path);
    // $track = new GPSTrack;
    // $session_data = $fit_parser->activitySessionData();
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
