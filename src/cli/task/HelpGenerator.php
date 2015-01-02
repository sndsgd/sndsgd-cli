<?php

namespace sndsgd\cli\task;

use \sndsgd\Cli;
use \sndsgd\Field;
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
    * @var array.<sndsgd\field\Field>
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
      $fc = $task->getFieldCollection();
      $this->fields = $fc->getFields();
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
      return $this->createHeader('name', " @[bold]$cmd@[reset]")." $description\n";
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
      $opts = [];
      $fields = $this->fields;
      foreach ($fields as $field) {
	 $opts[] = $this->createFieldUsage($field);
      }

      $cmd = Cli::getScriptName(true);
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
    * @param sndsgd\field\Field $field
    * @return string
    */
   private function createFieldUsage(Field $field)
   {
      $name = $field->getName();
      $opts = array_merge(["--$name"], $field->getAliases());
      $isBoolean = $field instanceof \sndsgd\field\BooleanField;
      $exportName = $field->getExportName();
      array_walk($opts, function(&$v) use ($isBoolean, $exportName) {
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
	    $v .= "<$exportName>";
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
	    $flags = array_merge(['-'.$field->getName()], $field->getAliases());
	    array_walk($flags, function(&$v) { $v = "@[bold]-$v@[reset]"; });
	    $tmp .= ' '.implode(', ', $flags);
	    $tmp .= "\n   ".$field->getDescription()."@[reset]\n";
	 }
      }
      return $tmp;
   }
}
