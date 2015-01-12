<?php

namespace sndsgd;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Cli;


/**
 * @coversDefaultClass \sndsgd\Cli
 */
class CliTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @covers ::getScriptName
    */
   public function testGetScriptName()
   {
      $result = Cli::getScriptName();
      $this->assertTrue(strpos($result, 'phpunit') !== false);
   }

   /**
    * @covers ::getScriptPath
    */
   public function testGetScriptPath()
   {
      $path = Cli::getScriptPath();
      $this->assertTrue(file_exists($path));
   }

   /**
    * @covers ::fork
    */
   public function testFork()
   {
      $path = Temp::file('test-fork.txt');
      Cli::fork('cat '.escapeshellarg(__FILE__), $path);
      usleep(100000);
      $expect = file_get_contents(__FILE__);
      $this->assertEquals($expect, file_get_contents($path));
   }

   /**
    * @covers ::getUser
    */
   public function testGetUser()
   {
      $user = trim(shell_exec('whoami'));
      $this->assertEquals($user, Cli::getUser());
   }

   /**
    * @covers ::getWidth
    */
   public function testGetWidth()
   {
      $this->assertTrue(is_int(Cli::getWidth()));
   }

   /**
    * @covers ::getHeight
    */
   public function testGetHeight()
   {
      $this->assertTrue(is_int(Cli::getHeight()));
   }
}
