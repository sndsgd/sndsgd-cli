<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\cli\Command;


class CommandTest extends PHPUnit_Framework_TestCase
{
   public static function setUpBeforeClass()
   {
      $root = vfsStream::setup('root');
      vfsStream::newFile('sndsgd-valid-binary', 0775)->at($root);
      vfsStream::newFile('sndsgd-invalid-binary', 0664)->at($root);
      vfsStream::newDirectory('readable-dir', 0775)->at($root);
      vfsStream::newDirectory('non-readable-dir', 0700)
         ->at($root)
         ->chgrp(vfsStream::GROUP_ROOT)
         ->chown(vfsStream::OWNER_ROOT);
   }

   public function testAddDir()
   {
      Command::addSearchDir(vfsStream::url('root'), true);
      Command::addSearchDir(vfsStream::url('root/readable-dir'));
   }

   public function testConstructor()
   {
      $cmd = new Command;
   }

   public function testGetPath()
   {
      $expect = vfsStream::url('root/sndsgd-valid-binary');
      $this->assertEquals($expect, Command::getPath('sndsgd-valid-binary'));
      # call it again to test loading from cache
      $this->assertEquals($expect, Command::getPath('sndsgd-valid-binary'));
   }

   public function testGetPathNotFound()
   {
      $this->assertNull(Command::getPath('this-binary-most-not-exist'));
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testAddDirException()
   {
      $dir = vfsStream::url('root/non-readable-dir');
      Command::addSearchDir($dir);
   }

   public function testSetPath()
   {
      $path = vfsStream::url('root/sndsgd-valid-binary');
      Command::setPath('sndsgd-valid-binary', $path);
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testSetPathException()
   {
      $path = vfsStream::url('root/sndsgd-invalid-binary');
      Command::setPath('sndsgd-invalid-binary', $path);
   }
}
