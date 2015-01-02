<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\cli\task\Runner;
use \sndsgd\Debug;
use \sndsgd\cli\debug\Writer;
use \sndsgd\Field;
use \sndsgd\field\rule\Required;


class ExampleTask extends \sndsgd\Task
{
   public function __construct()
   {
      parent::__construct();
      $fc = $this->getFieldCollection();
      $fc->addFields(
	 Field::float('value')
	    ->setExportHandler(Field::EXPORT_ARRAY)
	    ->addRules(new Required)
      );
   }

   public function getDescription()
   {
      return "a task for testing sndsgd\\cli\\task\\Runner";
   }

   public function run(array $options)
   {
      $ret = 0;
      foreach ($options['value'] as $value) {
	 $ret += $value;
      }
      return $ret;
   }
}


class RunnerTest extends PHPUnit_Framework_TestCase
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

   public function testPass()
   {
      $args = ['cmdname', '-vvv', '-stats', '-value', '1', '-value', '2'];

      $task = new ExampleTask;
      $runner = new Runner;
      $result = $runner->run($task, $args);
      $this->assertEquals(3, $result);
   }

}
