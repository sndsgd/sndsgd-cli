<?php

namespace sndsgd\cli\task;

use \org\bovigo\vfs\vfsStream;
use \sndsgd\cli\env\Controller;
use \sndsgd\Env;
use \sndsgd\Event;
use \sndsgd\Field;
use \sndsgd\field\FloatField;
use \sndsgd\field\rule\RequiredRule;

/**
 * @codeCoverageIgnore
 */
class ExampleTask extends \sndsgd\Task
{
   const DESCRIPTION = "a task for testing sndsgd\\cli\\task\\Runner";

   public function __construct(array $fields = null)
   {
      parent::__construct($fields);
      $this->addFields([
         (new FloatField('value'))
            ->setExportHandler(Field::EXPORT_ARRAY)
            ->addRule(new RequiredRule)
      ]);
   }

   public function run()
   {
      $opts = $this->exportValues();
      $ret = 0;
      foreach ($opts['value'] as $value) {
         $ret += $value;
      }
      return $ret;
   }
}


/**
 * @coversDefaultClass \sndsgd\cli\task\Runner
 */
class RunnerTest extends \PHPUnit_Framework_TestCase
{
   protected $stream;

   /**
    * @coversNothing
    */
   public function setUp()
   {
      # since the test is writing to a file, only the last value
      # written will be present in the file
      $root = vfsStream::setup('root');
      vfsStream::newFile('test', 0664)->at($root);
      $this->stream = vfsStream::url('root/test');

      $this->controller = new Controller;
      $this->controller->setStream($this->stream);
      Env::setController($this->controller);
      Env::setVerboseLevel(Env::V);
   }

   /**
    * @coversNothing
    */
   private function getLoggedContents()
   {
      return file_get_contents($this->stream);
   }

   /**
    * @coversNothing
    */
   private function getParseEventObject($name)
   {
      $runner = $this->getMockBuilder('sndsgd\\cli\\task\\Runner')
         ->setConstructorArgs(['sndsgd\\cli\\task\\ExampleTask'])
         ->getMock();

      $reflection = new \ReflectionClass('sndsgd\\cli\\task\\Runner');
      $property = $reflection->getProperty('task');
      $property->setAccessible(true);
      $event = new Event('parse');
      $event->setData([ 
         'task' => $property->getValue($runner),
         'name' => $name
      ]);
      return $event;
   }

   /**
    * @coversNothing
    */
   private function getMockedController()
   {
      $class = 'sndsgd\\cli\\env\\Controller';
      $controller = $this->getMockBuilder($class)->getMock();
      $controller->method('terminate')->willReturn(true);
      return $controller;
   }

   /**
    * @covers ::showUsageStats
    */
   public function testGetStats()
   {
      Runner::showUsageStats();
      $regex = '/processed in (.*?) seconds using (.*?) of memory/';
      $this->assertRegExp($regex, $this->getLoggedContents());
   }

   /**
    * @covers ::showHelp
    */
   public function testShowHelp()
   {
      Env::setController($this->getMockedController());
      $ev = $this->getParseEventObject('help');
      Runner::showHelp($ev);
   }

   /**
    * @covers ::showVersionInformation
    */
   public function testShowVersionInformation()
   {
      Env::setController($this->getMockedController());
      $ev = $this->getParseEventObject('version');
      Runner::showVersionInformation($ev);
   }

   /**
    * @covers ::__construct
    */
   public function testConstructorCreateController()
   {
      Env::setController(null);
      $runner = new Runner('sndsgd\\cli\\task\\ExampleTask');
   }
   
   /**
    * @covers \sndsgd\cli\task\Runner
    */
   public function testPass()
   {
      $args = ['cmdname', '-vvv', '-stats', '-value', '1', '-value', '2'];
      $runner = new Runner('sndsgd\\cli\\task\\ExampleTask');
      $result = $runner->run($args);
      $this->assertEquals(3, $result);
   }

   public function testDisableStyledOutput()
   {
      $runner = new Runner('sndsgd\\cli\\task\\ExampleTask');
      $runner->run(['cmdname', '-no-ansi', '-value', '1', '-value', '2']);
   }

   /**
    * @expectedException InvalidArgumentException
    */
   public function testRunInvalidArgs()
   {
      $runner = new Runner('sndsgd\\cli\\task\\ExampleTask');
      $runner->run(42);
   }

   /**
    * @covers ::run
    */
   public function testRunInvalidParameter()
   {
      $controller = $this->getMockedController();
      $controller->setStream($this->stream);
      Env::setController($controller);
      $runner = new Runner('sndsgd\\cli\\task\\ExampleTask');
      $runner->run(['cmdname', '-unknown']);

      // $regex = "/use '(.*?)' for help/";
      // $this->assertRegExp($regex, $this->getLoggedContents());
   }
}
