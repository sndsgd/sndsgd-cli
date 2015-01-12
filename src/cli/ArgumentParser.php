<?php

namespace sndsgd\cli;

use \Exception;
use \UnexpectedValueException;
use \sndsgd\Cli;
use \sndsgd\cli\task\HelpGenerator;
use \sndsgd\Debug;
use \sndsgd\field\BooleanField;
use \sndsgd\field\Collection;
use \sndsgd\field\UnknownFieldException;


/**
 * A command line argument parser for setting values in a field collection
 */
class ArgumentParser
{
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
   protected $fieldCollection;

   /**
    * Constructor
    *
    * @param array.<string> $args The arguments array to parse
    */
   public function __construct(array $args)
   {
      $this->args = $args;
   }

   /**
    * Parse an array of values into the field collection
    *
    * @param array.<string> $args
    * @return array.<string> The unparsed portion of $args
    */
   public function parseInto(Collection $collection)
   {
      $this->fieldCollection = $collection;

      $ret = 0;
      $len = count($this->args);
      for ($i=0; $i<$len; $i++) {
         $current = $this->args[$i];
         $next = ($i === $len - 1) ? null : $this->args[$i+1];

         if ($current{0} !== '-') {
            throw new UnexpectedValueException("unexpected argument '$current'");
         }

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
   }

   /**
    * Add a value to the appropriate field
    *
    * @param string $name The name of the argument
    * @param string $value The perceived value of the argument
    * @param string $next The next argument
    * @return integer The number to advance the counter
    */
   private function processNamedArgument($name, $value, $next)
   {
      if (($field = $this->fieldCollection->getField($name)) === null) {
         throw new UnknownFieldException("unknown option '$name'");
      }

      if (
         $field instanceof BooleanField ||
         $next === null ||
         $next{0} === '-'
      ) {
         $field->addValue($value);
         $ret = 0;
      }
      else {
         $field->addValue($next);
         $ret = 1;
      }

      $dataKey = constant(get_class($this->fieldCollection).'::EVENT_DATA_KEY');
      $field->fire('parse', [
         $dataKey => $this->fieldCollection,
         'field' => $field,
         'name' => $name
      ]);

      return $ret;
   }
}
