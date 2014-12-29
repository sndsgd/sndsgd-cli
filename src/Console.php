<?php

namespace sndsgd\cli;

use \InvalidArgumentException;
use \sndsgd\cli\console\Writer;


class Console
{
   // output streams
   const STDOUT = 'php://stdout';
   const STDERR = 'php://stderr';

   // for use with sndsgd\cli\Console::setVerboseLevel()
   const VERBOSE_QUIET = 0;
   const VERBOSE_SOME = 1;
   const VERBOSE_MORE = 2;
   const VERBOSE_MOST = 3;

   /**
    * The level of verbosity to exercise when using verbose write methods
    * 
    * @var integer
    */
   private static $verboseLevel = 0;

   /**
    * The stream to send verbose output to (stdout/stderr)
    * 
    * use \sndsgd\cli\Console::STDOUT or \sndsgd\cli\Console::STDERR
    * @var string
    */
   private static $verboseOutputStream = self::STDOUT;

   /**
    * Set the output stream for all verbose messages
    * 
    * Example:
    * <code>
    * # send all output to stdout
    * self::setVerboseOutputStream(Output::STDOUT);
    * # send all output to stderr
    * self::setVerboseOutputStream(Output::STDERR);
    * </code>
    * 
    * @param string $stream The stream to write verbose output to
    * @return void
    */
   public static function setVerboseOutputStream($stream)
   {
      self::$verboseOutputStream = $stream;
   }

   /**
    * Set the verbose level
    * 
    * @param integer $level One of self::VERBOSE_*
    * @return void
    */
   public static function setVerboseLevel($level)
   {
      if (
         !is_int($level) || 
         $level < self::VERBOSE_QUIET || 
         $level > self::VERBOSE_MOST
      ) {
         throw new InvalidArgumentException(
            "invalid value provided for 'level'; ".
            "expecting a valid verbose level as an integer"
         );
      }
      self::$verboseLevel = $level;
      self::vvv("verbose level set to @[bold+yellow]$level@[reset]\n");
   }

   /**
    * Handle writing a verbose message to the verbose output stream
    *
    * @param integer $minLevel
    * @param string|callable $message
    * @return void
    */
   private static function writeVerboseMessage($minLevel, $message)
   {
      if (self::$verboseLevel > $minLevel) {
         if (is_callable($message)) {
            $message = $message();
         }
         if (!is_string($message)) {
            throw new InvalidArgumentException(
               "invalid message provided for 'message'; ".
               "expecting a message as string, ".
               "or a function that returns a string"
            );
         }
         Writer::write($message, self::$verboseOutputStream);
      }
   }

   /**
    * Write a low level verbose message to the verbose output stream
    * 
    * @param string $message
    * @return void
    */
   public static function v($message)
   {
      self::writeVerboseMessage(self::VERBOSE_QUIET, $message);
   }

   /**
    * Write a medium level verbose message to the verbose output stream
    * 
    * @param string $message
    * @return void
    */
   public static function vv($message)
   {
      self::writeVerboseMessage(self::VERBOSE_SOME, $message);
   }

   /**
    * Write a debug level verbose message to the verbose output stream
    * 
    * @param string $message
    * @return void
    */
   public static function vvv($message)
   {
      self::writeVerboseMessage(self::VERBOSE_MORE, $message);
   }

   /**
    * Write a message to the console
    *
    * @param string|array.<string> $message
    * @param string|null $stream
    * @return void
    */
   public static function log($message, $stream = self::STDOUT)
   {
      Writer::write($message, $stream);
   }

   /**
    * Write a message to a stream and optionally kill the script
    * 
    * @param string $message The message to display before killing the script
    * @param integer|null $exitcode An optional exitcode
    * @return void
    */
   public static function error($msg, $exitcode = 1, $stream = self::STDERR)
   {
      Writer::write("@[bg:red+white] Error @[reset] $msg", $stream);
      ($exitcode !== null) && exit($exitcode);
   }

   /**
    * Get the number of columns in the terminal
    * 
    * @return integer
    */
   public static function getWidth()
   {
      return intval(shell_exec('tput cols'));
   }

   /**
    * Get the number of lines in the terminal
    * 
    * @return integer
    */
   public static function getHeight()
   {
      return intval(shell_exec('tput lines'));
   }
}

