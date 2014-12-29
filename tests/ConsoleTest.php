<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\cli\Console;


class ConsoleTest extends PHPUnit_Framework_TestCase
{
   protected $stream;

   public function setUp()
   {
      # since the test is writing to a file, only the last value 
      # written will be present in the file
      $root = vfsStream::setup('root');
      vfsStream::newFile('test', 0664)->at($root);
      $this->stream = vfsStream::url('root/test');
      Console::setVerboseOutputStream($this->stream);
   }

   /**
    * @covers \sndsgd\cli\Console::setVerboseLevel
    * @covers \sndsgd\cli\Console::v
    * @covers \sndsgd\cli\Console::vv
    * @covers \sndsgd\cli\Console::vvv
    */
   public function testSetVerboseOutputStream()
   {
      Console::setVerboseLevel(Console::VERBOSE_QUIET);
      Console::v('1');
      Console::vv('2');
      Console::vvv('3');
      $this->assertEquals('', file_get_contents($this->stream));
      
      Console::setVerboseLevel(Console::VERBOSE_SOME);
      Console::v('1');
      Console::vv('2');
      Console::vvv('3');
      $this->assertEquals('1', file_get_contents($this->stream));
      
      Console::setVerboseLevel(Console::VERBOSE_MORE);
      Console::v('1');
      Console::vv('2');
      Console::vvv('3');
      
      $this->assertEquals('2', file_get_contents($this->stream));
      Console::setVerboseLevel(Console::VERBOSE_MOST);
      Console::v('1');
      Console::vv('2');
      Console::vvv('3');
      $this->assertEquals('3', file_get_contents($this->stream));
   }

   /**
    * @covers \sndsgd\cli\Console::v
    * @covers \sndsgd\cli\Console::vv
    * @covers \sndsgd\cli\Console::vvv
    */
   public function testVerboseClosure()
   {
      Console::setVerboseLevel(Console::VERBOSE_MOST);
      $msg = 'test message';
      Console::v(function() use ($msg) {
         return $msg;
      });
      $this->assertEquals($msg, file_get_contents($this->stream));
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testVerboseException()
   {
      Console::setVerboseLevel(Console::VERBOSE_MOST);
      Console::v(function() {
         return new \StdClass;
      });
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testSetVerboseLevelException()
   {
      Console::setVerboseLevel('nope');
   }

   /**
    * @covers \sndsgd\cli\Console::log
    */
   public function testLog()
   {
      $msg = 'test message';
      Console::log($msg, $this->stream);
      $contents = file_get_contents($this->stream);
      $this->assertEquals($msg, $contents);
   }

   /**
    * @covers \sndsgd\cli\Console::error
    */
   public function testError()
   {
      $msg = 'test error message';
      Console::error($msg, null, $this->stream);
      $contents = file_get_contents($this->stream);
      $this->assertEquals(1, preg_match("/{$msg}\$/", $msg));
   }

   /**
    * @covers \sndsgd\cli\Console::getWidth
    */
   public function testGetWidth()
   {
      $this->assertTrue(is_int(Console::getWidth()));
   }

   /**
    * @covers \sndsgd\cli\Console::getHeight
    */
   public function testGetHeight()
   {
      $this->assertTrue(is_int(Console::getHeight()));
   }
}

