<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\Cli;


/**
 * @coversDefaultClass \sndsgd\Cli
 */
class CliTest extends PHPUnit_Framework_TestCase
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
