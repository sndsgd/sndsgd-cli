<?php

namespace sndsgd\cli\task;

use \Exception;
use \InvalidArgumentException;
use \sndsgd\Cli;
use \sndsgd\cli\ArgumentParser;
use \sndsgd\cli\env\Controller as EnvController;
use \sndsgd\cli\task\HelpGenerator;
use \sndsgd\Env;
use \sndsgd\Event;
use \sndsgd\Field;
use \sndsgd\field\BooleanField;
use \sndsgd\field\UnknownFieldException;
use \sndsgd\Task;
use \sndsgd\File;
use \sndsgd\Str;


class Runner extends \sndsgd\task\Runner
{
   /**
    * Generate and show the help page for the current task and kill the script
    * 
    * @param Event $ev An event fired when the field was parsed
    * @return void
    */
   public static function showHelp(Event $ev)
   {
      $task = $ev->getData('task');
      $help = new HelpGenerator($task);
      $verboseLevel = Env::getVerboseLevel();
      Env::setVerboseLevel(Env::NORMAL);
      Env::log($help->generate());
      Env::setVerboseLevel($verboseLevel);
      Env::terminate(0);
   }

   /**
    * Show version info for the current task and kill the script
    * 
    * @param Event $ev An event fired when the field was parsed
    * @return void
    */
   public static function showVersionInformation(Event $ev)
   {
      $task = $ev->getData('task');
      $script = Cli::getScriptName();
      $version = $task->getVersion();
      Env::log("$script version $version\n");
      Env::terminate(0);
   }

   /**
    * Set the verbosity for debug messages
    * 
    * @param sndsgd\Event $ev An event fired when the field was parsed
    * @return void
    */
   public static function setVerboseLevel(Event $ev) {
      $flag = $ev->getData('name');
      $values = [
        'quiet' => Env::QUIET,
        'verbose' => Env::V,
        'v' => Env::V,
        'vv' => Env::VV,
        'vvv' => Env::VVV,
      ];
      Env::setVerboseLevel($values[$flag]);
   }

   /**
    * Disable the debug writer style output
    * 
    * @param sndsgd\Event $ev An event fired when the field was parsed
    * @return void
    */
   public static function disableStyledOutput(Event $ev)
   {
      Env::getController()->disableStyles();
   }

   /**
    * Register a function to show stats on shutdown
    * 
    * @param sndsgd\Event $ev An event fired when the field was parsed
    * @return void
    */
   public static function registerStatsShutdownFunction(Event $ev)
   {
      # register a func that will register the func to output stats
      # this ensures that the stats output gets executed last
      register_shutdown_function(function() {
         register_shutdown_function(__CLASS__.'::showUsageStats');
      });
   }

   /**
    * Show usage statistics
    *
    * @return void
    */
   public static function showUsageStats()
   {
      $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
      $time = number_format($time, 4);
      $memory = File::formatSize(memory_get_peak_usage(), 2);
      $message = "processed in $time seconds using $memory of memory\n";
      $verboseLevel = Env::getVerboseLevel();
      Env::setVerboseLevel(Env::NORMAL);
      Env::log($message);
      Env::setVerboseLevel($verboseLevel);
   }

   /**
    * Constructor
    * 
    * @param string $classname The name of a task class
    * @param array.<sndsgd\Field>|null $fields Fields to inject into the task
    */
   public function __construct($classname, array $fields = null)
   {
      if (($env = Env::getController()) === null) {
         $env = new EnvController;
         $env->setStream(EnvController::STDERR);
         Env::setController($env);
      }

      $fields = ($fields === null)
         ? $this->createFields()
         : array_merge($fields, $this->createRelevantFields());

      parent::__construct($classname, $fields);
   }

   /**
    * Create fields that are usefull for ALL tasks run via the command line
    * 
    * @return array.<sndsgd\Field>
    */
   private function createFields()
   {
      return [
         (new BooleanField('help'))
            ->addAliases('?')
            ->setDescription('show this help text')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::showHelp'),
         (new BooleanField('version'))
            ->setDescription('show the current version information')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::showVersionInformation'),
         (new BooleanField('quiet'))
            ->setDescription('silence all debug messages')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::setVerboseLevel'),
         (new BooleanField('verbose'))
            ->addAliases('verbose', 'v', 'vv', 'vvv')
            ->setDescription('set the verbosity of output')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::setVerboseLevel'),
         (new BooleanField('stats'))
            ->setDescription('show execution time and memory usage on quit')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::registerStatsShutdownFunction'),
         (new BooleanField('no-ansi'))
            ->setDescription('disable colors debug messages')
            ->setExportHandler(Field::EXPORT_SKIP)
            ->on('parse', __CLASS__.'::disableStyledOutput')
      ];
   }

   /**
    * {@inheritdoc}
    */
   public function run($data)
   {
      if (!is_array($data)) {
         throw new InvalidArgumentException(
            "invalid value provided for 'data'; expecting in indexed array ".
            "of command line options (use \$argv)"
         );
      }

      try {
         $parser = new ArgumentParser(array_slice($data, 1));         
         $parser->parseInto($this->task);
      }
      // UnknownFieldException: value for undefined field encountered
      // UnexpectedValueException: unexpected unnamed argument encountered
      catch (Exception $ex) {
         $cmd = Cli::getScriptName();
         Env::error(
            $ex->getMessage()."\n".
            "use '@[bold]$cmd --help@[reset]' for help\n\n"
         );
      }

      if ($this->task->validate() === false) {
         $errors = $this->task->getErrors();
         Env::error($this->formatErrors($errors));
      }
      return $this->task->run($this->task->exportValues());
   }
}
