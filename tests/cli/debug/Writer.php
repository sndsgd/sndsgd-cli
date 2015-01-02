<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\cli\debug\Writer;
use \sndsgd\Debug;
use \sndsgd\util\Str;


/**
 * @coversDefaultClass \sndsgd\cli\debug\Writer
 */
class WriterTest extends PHPUnit_Framework_TestCase
{
   protected $stream;

   public function setUp()
   {
      # since the test is writing to a file, only the last value
      # written will be present in the file
      $root = vfsStream::setup('root');
      vfsStream::newFile('test', 0664)->at($root);
      $this->stream = vfsStream::url('root/test');

      $this->writer = new Writer;
      $this->writer->setStream($this->stream);
      $this->writer->setVerboseLevel(Debug::VERBOSE_1);
      Debug::setWriter($this->writer);
   }

   /**
    * @covers ::setVerboseLevel
    * @covers ::formatMessage
    */
   public function testVerboseLevelWriting()
   {
      # nothing will be written
      $this->writer->setVerboseLevel(Debug::QUIET);
      Debug::info('test1', Debug::VERBOSE_1);
      $this->assertEquals('', file_get_contents($this->stream));
      Debug::info('test2', Debug::VERBOSE_2);
      $this->assertEquals('', file_get_contents($this->stream));
      Debug::info('test3', Debug::VERBOSE_3);
      $this->assertEquals('', file_get_contents($this->stream));

      # 1 will be written
      $this->writer->setVerboseLevel(Debug::VERBOSE_1);
      Debug::info('test1', Debug::VERBOSE_1);
      $this->assertEquals('test1', file_get_contents($this->stream));
      Debug::info('test2', Debug::VERBOSE_2);
      $this->assertEquals('test1', file_get_contents($this->stream));
      Debug::info('test3', Debug::VERBOSE_3);
      $this->assertEquals('test1', file_get_contents($this->stream));

      # 1 & 2 will be written
      $this->writer->setVerboseLevel(Debug::VERBOSE_2);
      Debug::info('test1', Debug::VERBOSE_1);
      $this->assertEquals('test1', file_get_contents($this->stream));
      Debug::info('test2', Debug::VERBOSE_2);
      $this->assertEquals('test2', file_get_contents($this->stream));
      Debug::info('test3', Debug::VERBOSE_3);
      $this->assertEquals('test2', file_get_contents($this->stream));

      # 1, 2, and 3 will be written
      $this->writer->setVerboseLevel(Debug::VERBOSE_3);
      Debug::info('test1', Debug::VERBOSE_1);
      $this->assertEquals('test1', file_get_contents($this->stream));
      Debug::info('test2', Debug::VERBOSE_2);
      $this->assertEquals('test2', file_get_contents($this->stream));
      Debug::info('test3', Debug::VERBOSE_3);
      $this->assertEquals('test3', file_get_contents($this->stream));
   }

   /**
    * @covers \sndsgd\cli\debug\Writer::write
    */
   public function testVerboseClosure()
   {
      $msg = 'test message';
      Debug::info(function() use ($msg) {
	 return $msg;
      });
      $this->assertEquals($msg, file_get_contents($this->stream));
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testVerboseException()
   {
      Debug::info(function() {
	 return new \StdClass;
      });
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testSetVerboseLevelException()
   {
      $this->writer->setVerboseLevel('nope');
   }

   /**
    * @covers ::warn
    */
   public function testWarn()
   {
      $msg = 'test message';
      Debug::warn($msg, null);
      $contents = file_get_contents($this->stream);
      $this->assertTrue(Str::endsWith($contents, $msg));
   }

   /**
    * @covers ::error
    */
   public function testError()
   {
      $msg = 'test error message';
      Debug::error($msg, null);
      $contents = file_get_contents($this->stream);
      $this->assertEquals(1, preg_match("/{$msg}\$/", $msg));
   }
}
