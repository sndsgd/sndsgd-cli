<?php

namespace sndsgd\cli;

use \Exception;
use \sndsgd\Cli;
use \sndsgd\cli\task\HelpGenerator;
use \sndsgd\Debug;
use \sndsgd\field\BooleanField;
use \sndsgd\field\Collection;


/**
 * A command line argument parser for setting values in a field collection
 */
class ArgumentParser
{
   const UNNAMED_ARGUMENT = 1;

   /**
    * The arguments to parse
    *
    * @var array.<string>
    */
   protected $args;

   /**
    * A collection of fields to add values to as they are parsed
    *
    * @var sndsgd\field\Collection
    */
   protected $collection;

   /**
    * Values that are not named arguments
    *
    * @var array.<string>
    */
   protected $unnamedArguments = [];

   /**
    * Parse from an array of arguments
    *
    * @param sndsgd\field\Collection $c Fields to populate with parsed values
    * @param array.<string> $indexedFields
    */
   public function __construct(array $args)
   {
      $this->args = $args;
   }

   /**
    * Get the arguments
    *
    * @return array.<string>
    */
   public function getArguments()
   {
      return $this->args;
   }

   /**
    * Set the collection of fields to parse values into
    *
    * @param sndsgd\field\Collection $collection
    *
    */
   public function setCollection(Collection $collection)
   {
      foreach ($collection->getFields() as $name => $field) {
         if ($field->getOption(self::UNNAMED_ARGUMENT) !== null) {
            $this->unnamedArguments[] = $name;
         }
      }
      $this->collection = $collection;
   }

   /**
    * Get the unnamed arguments
    *
    * @return array.<string>
    */
   public function getUnnamedArguments()
   {
      return $this->unnamedArguments;
   }

   /**
    * Find and remove the command from the argument array
    *
    * @param array.<string> Available task names
    */
   public function extractTask(array $tasks)
   {
      $tasks = array_flip($tasks);
      for ($i=0, $len=count($this->args); $i<$len; $i++) {
         $arg = $this->args[$i];
         if (array_key_exists($arg, $tasks)) {
            array_splice($this->args, $i, 1);
            return $arg;
         }
      }
      return null;
   }

   /**
    * Parse an array of values into the field collection
    *
    * @param array.<string> $args
    * @return array.<string> The unparsed portion of $args
    */
   public function parseInto(Collection $collection)
   {
      $this->setCollection($collection);

      $ret = 0;
      $len = count($this->args);
      for ($i=0; $i<$len; $i++) {
         $current = $this->args[$i];
         $next = ($i === $len - 1) ? null : $this->args[$i+1];

         if ($current{0} === '-') {
            $fieldName = substr($current, 1);
            $value = true;
            if ($fieldName{0} === '-') {
               $fieldName = substr($fieldName, 1);
               $pos = strpos($fieldName, '=');
               if ($pos !== false) {
	          $value = substr($fieldName, $pos+1);
	          $fieldName = substr($fieldName, 0, $pos);
               }
            }

            $i += $this->processNamedArgument($fieldName, $value, $next);
         }
         else {
            $this->processUnnamedArgument($current);
         }
      }
   }

   /**
    * Add a value to the appropriate field
    *
    * @param string $argName The name of the argument
    * @param string $value The perceived value of the argument
    * @param string $next The next argument
    * @return integer The number to advance the counter
    */
   private function processNamedArgument($argName, $value, $next)
   {
      $field = $this->collection->getField($argName);

      # no field was found
      if ($field === null) {
         $cmd = Cli::getScriptName(true);
         throw new Exception(
            "unknown option '$argName'\n".
            "use '@[bold]$cmd --help@[reset]' for help\n\n"
         );
      }
      else {
         $eventData = [
            'collection' => $this->collection,
            'field' => $field,
            'name' => $argName
         ];

         if (
            $field instanceof BooleanField ||
            //$value !== true ||
            $next === null ||
            $next{0} === '-'
         ) {
            $field->addValue($value);
            $field->fire('parse', $eventData);
         }
         else {
            $field->addValue($next);
            $field->fire('parse', $eventData);
            return 1;
         }
      }

      return 0;
   }

   /**
    * Attempt to apply an unnamed argument value to a field
    *
    * @param string $value The value of the unnamed argument
    */
   private function processUnnamedArgument($value)
   {
      if (count($this->unnamedArguments) === 0) {
         if (strlen($value) > 100) {
            $value = substr($value, 0, 96).'...';
         }
         Debug::error("unexpected unnamed argument '$value'\n");
      }
      $fieldName = array_shift($this->unnamedArguments);
      var_dump($fieldName);
      $field = $this->collection->getField($fieldName);
      $field->addValue($value);
   }
}
