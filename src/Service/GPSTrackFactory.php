<?php

namespace App\Service;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;

class GPSTrackFactory
{

  private   $msg;
  protected $options;
  protected $cache;

  public function __construct(array $options = [], CacheInterface $cacheApp)
  {
    $this->msg = 'I am a track factory';

    $resolver = new OptionsResolver();
    $this->configureOptions($resolver);
    $this->options = $resolver->resolve($options);

    $this->cache = $cacheApp;
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

    // TODO: clean up - don't like the multiple returns - structure isn't clean
    $cache_key = GPSTrackFactory::cacheKeyFromFilePath($path);

    if($this->options['use_cache']){
      return $this->cache->get($cache_key, function(){
        return 'The string shouldn\'t change';
      });
    }

    // if($this->options['use_cache']){
    //   return $this->getGPSTrackFromCache($cache_key);
    // } else {
    //   $file_type = $this->detectFileType($path);
    //   if($file_type == 'unicsv'){
    //     $track = $this->buildTrackFromCSVFile($path);
    //   }
    //   if($file_type == 'garmin_fit'){
    //     $track = $this->buildTrackFromFITFile($path);
    //   }
    //   if($file_type == 'gpx'){
    //     $track = $this->buildTrackFromGPXFile($path, $options);
    //   }
    //
    //   if($track){
    //     if(!$track->trackPointsHaveDistance() && $this->options['set_distance_from_coords']){
    //       $track->setTrackPointsDistanceFromCoords();
    //     }
    //
    //     if(!$track->trackPointsHaveSpeed()){
    //       // something seems to have changed and this function is getting called even on treadmill workouts when that didn't seem to be a problem before.
    //       // don't have time to debug now. To make this work for treadmill fit file, I just commented out the line below.
    //       // $track->setTrackPointsSpeedFromCoords();
    //     }
    //
    //     if(!$track->total_distance){
    //       $track->setTotalDistanceFromLastTrackPoint();
    //     }
    //
    //     if(!$track->total_ascent){
    //       $track->calculateTotalAscentAndDescent();
    //     }
    //
    //     if(!$track->max_speed){
    //       $track->calculateMaxSpeed();
    //     }
    //
    //     if(!$track->average_speed){
    //       $track->calculateAverageSpeed();
    //     }
    //
    //     if($this->auto_cache){
    //       $this->cache->save($cache_key, serialize($track));
    //     }
    //     return $track;
    //   } else {
    //     throw new \Exception('File type '.$file_type.' is not supported');
    //     return null;
    //   }
    // }
  }

  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  public function getGPSTrackFromCache($cache_key)
  {
    if($this->cache){
      if($hit = $this->cache->fetch($cache_key)){
        return unserialize($hit);
      }
    }
    throw new \Exception('Cannot find track using cache key '.$cache_key);
    return null;
  }

  public function getMsg()
  {
    return $this->msg;
  }

}
