<?php

namespace sndsgd\cli\env;

use \ReflectionClass;
use \org\bovigo\vfs\vfsStream;
use \sndsgd\Env;


/**
 * @coversDefaultClass \sndsgd\cli\env\Controller
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @coversNothing
    */
   public function setUp()
   {
      vfsStream::setup('root');
      $this->stdout = vfsStream::url('root/stdout.txt');
      $this->stderr = vfsStream::url('root/stderr.txt');

      $this->controller = new Controller;
      $this->controller->setStream($this->stdout);
      Env::setController($this->controller);
   }

   /**
    * @coversNothing
    */
   public function tearDown()
   {
      Env::setController(null);
   }

   /**
    * @coversNothing
    */
   private function getAndResetStreamContents($stream)
   {
      $contents = file_get_contents($stream);
      file_put_contents($stream, '');
      return $contents;  
   }

   /**
    * @coversNothing
    */
   private function getStdout()
   {
      return $this->getAndResetStreamContents($this->stdout);
   }

   /**
    * @coversNothing
    */
   private function getStderr()
   {
      return $this->getAndResetStreamContents($this->stderr);
   }

   /**
    * @covers ::setStream
    */
   public function testSetStream()
   {
      $class = new ReflectionClass('sndsgd\\cli\\env\\Controller');
      $property = $class->getProperty('stream');
      $property->setAccessible(true);

      $this->controller->setStream($this->stdout);
      $this->assertEquals($this->stdout, $property->getValue($this->controller));
      $this->controller->setStream($this->stderr);
      $this->assertEquals($this->stderr, $property->getValue($this->controller));
   }

   /**
    * @covers ::write
    */
   public function testWrite()
   {
      $message = 'test';
      $this->controller->write($message);
      $this->assertEquals($message, $this->getStdout());

      $this->controller->write("@[bg:red] $message @[reset]");
      $expect = "\033[41m $message \033[0m";
      $this->assertEquals($expect, $this->getStdout());
   }

   /**
    * @covers ::error
    */
   public function testError()
   {
      $message = 'test';
      $this->controller->error($message);
      $expect = "\033[41;1;97m Error \033[0m $message";
      $this->assertEquals($expect, $this->getStdout());
   }

   /**
    * @covers ::applyStyles
    */
   public function testStyles()
   {
      $reflection = new ReflectionClass('sndsgd\\cli\\env\\Controller');
      $property = $reflection->getProperty('styleCodes');
      $property->setAccessible(true);
      $codes = $property->getValue($this->controller);

      $this->controller->setStream(Controller::STDERR);
      $this->controller->log("\n");
      foreach ($codes as $code => $int) {
         $name = str_pad($code, 20, ' ');
         $this->controller->log("@[ $code ] $name @[reset] ($code)\n");
      }
   }
}

