<?php

namespace sndsgd\cli\task;

use \sndsgd\Cli;
use \sndsgd\Field;
use \sndsgd\field\BooleanField;
use \sndsgd\Task;


class HelpGenerator
{
   /**
    * The task to generate help text for
    *
    * @var sndsgd\Task
    */
   protected $task;

   /**
    * The tasks fields are copied here in the constructor
    *
    * @var array.<sndsgd\Field>
    */
   protected $fields;

   /**
    * Constructor
    *
    * @param sndsgd\Task $task A task instance to generate a help page for
    */
   public function __construct(Task $task)
   {
      $this->task = $task;
      $this->fields = $task->getFields();
   }

   /**
    * Generate the help page contents
    *
    * @return string
    */
   public function generate()
   {
      return implode("\n", [
         $this->createDescription(),
         $this->createUsage(true)."\n",
         $this->createOptions()
      ])."\n";
   }

   private function createDescription()
   {
      $cmd = Cli::getScriptName();
      $description = $this->task->getDescription();
      $version = 'version '.$this->task->getVersion();
      $header = $this->createHeader('name', " @[bold]{$cmd}@[reset] $version");
      return "$header $description\n";
   }

   /**
    * Create a formatted header
    *
    * @param string $txt The header contents
    * @param string $right Contents to add immediately after the header
    * @return string
    */
   private function createHeader($txt, $right = null)
   {
      $ret = "@[reverse+bold] ".strtoupper($txt)." @[reset]";
      if ($right) {
         $ret .= $right;
      }
      return $ret."\n";
   }

   /**
    * Create a compact version of basic usage instructions
    *
    * @param boolean $header Whether or not to include a help header
    * @return string
    */
   public function createUsage($header = false)
   {
      $ret = '';
      $bools = [];
      $others = [];
      foreach ($this->fields as $field) {
         $name = $field->getName();
         $usage = $this->createFieldUsage($field);
         if ($field instanceof BooleanField) {
            $bools[$name] = $usage;
         }
         else {
            $others[$name] = $usage;
         }
      }

      $opts = array_merge(array_values($bools), array_values($others));

      $cmd = Cli::getScriptName();
      $maxWidth = min(Cli::getWidth(), 78);
      if ($header === true) {
         $ret .= $this->createHeader('usage');
         $tmp = " $cmd";
      }
      else {
         $tmp = "usage: $cmd";
      }

      $indent = strlen($tmp);
      foreach ($opts as $opt) {
         if (strlen($tmp) + strlen($opt) >= $maxWidth) {
            $ret .= "$tmp\n";
            $tmp = str_pad('', $indent, ' ')." $opt";
         }
         else {
            $tmp .= " $opt";
         }
      }
      $ret .= $tmp;
      return $ret;
   }

   /**
    * Create usage instructions for a field
    *
    * @param sndsgd\Field $field
    * @return string
    */
   private function createFieldUsage(Field $field)
   {
      $name = $field->getName();
      $opts = array_merge(["--$name"], $field->getAliases());
      $isBoolean = $field instanceof BooleanField;
      $hint = $field->getData('short-hint', $name);
      array_walk($opts, function(&$v) use ($isBoolean, $hint) {
         $isName = substr($v, 0, 2) === '--';
         if ($isName) {
            if (!$isBoolean) {
               $v .= '=';
            }
         }
         else if (!$isName) {
            $v = "-$v ";
         }
         if (!$isBoolean) {
            $v .= "<$hint>";
         }
         $v = trim($v);
      });

      $opts = implode('|', $opts);
      $isRequired = $field->hasRule('sndsgd\\field\\rule\\Required');
      if (!$isRequired) {
         $opts = "[$opts]";
      }
      return $opts;
   }

   /**
    * Create the options section
    *
    * @return string
    */
   private function createOptions()
   {
      $tmp = '';
      $fields = $this->fields;
      if (count($fields)) {
         $tmp = $this->createHeader("options");
         ksort($fields);
         foreach ($fields as $field) {
            $name = $field->getName();
            $flags = array_merge(["-$name"], $field->getAliases());
            array_walk($flags, function(&$v) { $v = "@[bold]-{$v}@[reset]"; });
            $tmp .= ' '.array_shift($flags).' ';
            if (count($flags)) {
               $tmp .= '('.implode('|', $flags).')';
            }
            if (($field instanceof BooleanField) == false) {
               $hint = $field->getData('short-hint', $name);
               $tmp .= " @[dim]<{$hint}>@[reset]";
            }
            $tmp .= "\n   ".$field->getDescription()."@[reset]\n";
         }
      }
      return $tmp;
   }
}
