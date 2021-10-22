<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\GPSTrackFactory;

final class GPSTrackFactoryTest extends KernelTestCase
{

  protected $factory;
  protected $project_dir;

  public function setUp() : void
  {
    self::bootKernel();
    $container    = static::getContainer();
    $this->factory   = $container->get(GPSTrackFactory::class);
    $this->project_dir = $container->get('kernel')->getProjectDir();
  }

  public function testBuildTrackFromFITFile()
  {
    // TODO: where should paths to test files and expected results be stored? Hardcoded for now.
    $fit_path = $this->project_dir . '/tests/Data/Sample_Boston_Run.fit';
    $track = $this->factory->buildTrackFromFITFile($fit_path);

    $this->assertEquals(15.02, $track->getTotalDistanceInMiles());
    $this->assertEquals('2021-10-09T12:39:57.000Z', $track->getStartTimeString());
    $this->assertEquals(5185, $track->getIndexForTrackPointAtElapsedSeconds(5400));
    $this->assertTrue($track->trackPointsHaveDistance(), 'failed to assert that track points have distance');
    $this->assertTrue($track->trackPointsHaveSpeed(), 'failed to assert that track points have speed');
    $this->assertEquals(6663, $track->numberOfTrackPoints());
  }
}
