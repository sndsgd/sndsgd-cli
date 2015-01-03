<?php

namespace sndsgd\cli\task\phar;

use \InvalidArgumentException;
use \sndsgd\field\Collection;
use \sndsgd\field\ValidationError;
use \sndsgd\util\File;
use \sndsgd\util\Path;


class ValidatePhar extends \sndsgd\field\Rule
{
   /**
    * {@inheritdoc}
    */
   public function validate(
      $value, 
      $name = null, 
      $index = null, 
      Collection $collection = null
   )
   {
      $path = Path::normalize($value);
      if (($test = File::isReadable($path)) !== true) {
         return new ValidationError($test, $value, $name, $index);
      }

      $buffer = '';
      $fp = fopen($path, 'r');
      while (($pos = strpos($buffer, '__HALT_COMPILER()')) === false) {
         if (feof($fp)) {
            break;
         }
         $buffer = fread($fp, 8192);
      }
      if ($pos !== false) {
         return $path;
      }

      return new ValidationError(
         "'$path' is not a valid phar", 
         $value, 
         $name, 
         $index
      );
   }
}

