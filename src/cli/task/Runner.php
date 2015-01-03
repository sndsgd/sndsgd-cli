<?php

namespace sndsgd\cli\task;

use \Exception;
use \sndsgd\Cli;
use \sndsgd\cli\ArgumentParser;
use \sndsgd\cli\debug\Writer;
use \sndsgd\cli\task\HelpGenerator;
use \sndsgd\Debug;
use \sndsgd\event\Event;
use \sndsgd\Field;
use \sndsgd\Task;
use \sndsgd\task\Collection;
use \sndsgd\util\File;
use \sndsgd\util\Str;


class Runner extends \sndsgd\task\Runner
{
   /**
    * An argument parser
    *
    * @var sndsgd\cli\ArgumentParser
    */
   protected $parser;

   /**
    * Update the task with standard cli options
    *
    * @param sndsgd\task\Single $task The task to update
    * @return sndsgd\task\Single
    */
   protected function setTask(Task $task)
   {
      $fc = $task->getFieldCollection();
      $fc->addFields(
         Field::boolean('help')
            ->addAliases('h')
            ->setDescription('show this help text')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', function(Event $ev) use ($task) {
               $help = new HelpGenerator($task);
               Debug::info($help->generate());
               exit(0);
            }),
         // Field::boolean('version')
         //    ->addAliases('V')
         //    ->setDescription('print the current version and exit')
         //    ->setExportHandler(Field::EXPORT_SKIP)
         //    ->on('parse', function(Event $ev) {
         //       $app = $ev->getData('collection');
         //       Console::log($app->getVersionInfo());
         //       exit(0);
         //    }),
         Field::boolean('verbose')
            ->addAliases('v', 'vv', 'vvv')
            ->setDescription('set the verbosity of output')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', function(Event $ev) {
               $values = [
                 'verbose' => Debug::VERBOSE_1,
                 'v' => Debug::VERBOSE_1,
                 'vv' => Debug::VERBOSE_2,
                 'vvv' => Debug::VERBOSE_3,
               ];

               Debug::getWriter()->setVerboseLevel($values[$ev->getData('name')]);
            }),
         Field::boolean('stats')
            ->setDescription('show execution time and memory usage on quit')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', function(Event $ev) {
               # register a func that will register the func to output stats
               # this ensures that the stats output gets executed last
               register_shutdown_function(function() {
            register_shutdown_function(function() {
               $memory = File::formatSize(memory_get_peak_usage(), 2);
               $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
               $time = number_format($time, 4);
               echo "completed in $time seconds using $memory of memory\n";
            });
               });
            })
      );
      parent::setTask($task);
   }

   /**
    * {@inheritdoc}
    */
   public function run($task, $data)
   {
      if (($task instanceof Task) === false) {
         throw new InvalidArgumentException(
            "invalid value provided for 'task'; expecting an instance of ".
            "sndsgd\\Task or an instance of sndsgd\\task\\Collection"
         );
      }

      if (($writer = Debug::getWriter()) === null) {
         $writer = new Writer;
         $writer->setStream(Writer::STDERR);
         Debug::setWriter($writer);
      }

      try {
         $this->setTask($task);
         $fc = $task->getFieldCollection();
         $parser = new ArgumentParser(array_slice($data, 1));
         $parser->parseInto($fc);
         if ($fc->validate() === false) {
            $msg = $this->formatValidationErrors($fc->getValidationErrors());
            Debug::error($msg);
         }
      }
      catch (Exception $ex) {
         $msg = $ex->getMessage();
         (Str::endsWith($msg, PHP_EOL) === false) && $msg .= "\n";
         Debug::error($msg);
      }

      return $this->task->run($fc->exportValues());
   }

   /**
    * {@inheritdoc}
    */
   protected function getTaskFromCollection(Collection $collection, $data)
   {
      $tasks = $collection->getTasks();
      $taskname = $this->parser->extractTask($tasks);
      if ($taskname === null) {
         Debug::error("unknown command '$taskname'\n");
      }
      return $collection->getTask($taskname);
   }

   /**
    * {@inheritdoc}
    */
   public function formatValidationErrors(array $errors)
   {
      $tmp = [];
      $len = count($errors);
      if ($len === 0) {
         throw new InvalidArgumentException(
            "invalid value provided for 'errors'; expecting an array that ".
            "contains at least one instance of sndsgd\\field\\ValidationError"
         );
      }

      $noun = ($len === 1) ? 'option' : 'options';
      $tmp = ["failed to process $noun"];
      foreach ($errors as $error) {
         $name = $error->getName();
         $message = $error->getMessage();
         $tmp[] = " @[bold]{$name}@[reset] ← {$message}";
      }
      return implode(PHP_EOL, array_unique($tmp)).PHP_EOL;
   }
}
