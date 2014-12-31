<?php

namespace sndsgd\cli\console;

use \sndsgd\cli\Console;


class Writer
{ 
   /**
    * Codes for various styles
    * 
    * @see http://misc.flogisoft.com/bash/tip_colors_and_formatting
    * @var array.<string,number>
    */
   private static $styleCodes = [

      # reset
      'reset' => 0,
      'reset-bold' => 21,
      'reset-dim' => 22,
      'reset-underline' => 24,
      'reset-blink' => 25,
      'reset-reverse' => 27,
      'reset-hidden' => 28,
      'reset-fg' => 39,
      'reset-bg' => 49,

      # style
      'bold' => 1,
      'dim' => 2,
      'underline' => 4,
      'blink' => 5,
      'reverse' => 7,
      'hidden' => 8,

      # foreground
      'default' => 39,
      'fg:' => 39,
      'black' => 30,
      'red' => 31,
      'green' => 32,
      'yellow' => 33,
      'blue' => 34,
      'magenta' => 35,
      'cyan' => 36,
      'light-gray' => 37,
      'dark-gray' => 90,
      'light-red' => 91,
      'light-green' => 92,
      'light-yellow' => 93,
      'light-blue' => 94,
      'light-magenta' => 95,
      'light-cyan' => 96,
      'white' => 97,

      # background
      'bg:default' => 49,
      'bg:' => 49,
      'bg:black' => 40,
      'bg:red' => 41,
      'bg:green' => 42,
      'bg:yellow' => 43,
      'bg:blue' => 44,
      'bg:magenta' => 45,
      'bg:cyan' => 46,
      'bg:light-gray' => 47,
      'bg:dark-gray' => 100,
      'bg:light-red' => 101,
      'bg:light-green' => 102,
      'bg:light-yellow' => 103,
      'bg:light-blue' => 104,
      'bg:light-magenta' => 105,
      'bg:light-cyan' => 106,
      'bg:white' => 107
   ];

   /**
    * Write a message to a stream, adding color where neccesary
    *
    * Example: write a message in intense red
    * <code>
    * $text = 'here is some text';
    * Output::write("@[bold+red]$text@[reset]"); 
    * </code>
    *
    * Example: write white text over a green background
    * <code>
    * $text = 'here is some text';
    * Output::write("@[bg:green+white]$text@[reset]"); 
    * </code>
    *
    * 
    * @param string|array.<string> The text to output
    * @param string $stream The output stream to write to
    * @return void
    */
   public static function write($message, $stream = Console::STDOUT)
   {
      $messages = (array) $message;
      $content = '';
      foreach ($messages as $message) {
         $content .= self::applyStyles($message);
      }
      file_put_contents($stream, $content);
   }

   /**
    * Replace style tags with the appropriate character sequences
    *
    * @param string $content
    * @return string
    */
   public static function applyStyles($content)
   {
      $regex = '/@\\[([a-z-:+ ]+)\\]/';
      if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
         foreach ($matches as $captures) {
            $match = $captures[0];
            $keys = explode('+', $captures[1]);
            $codes = [];
            foreach ($keys as $key) {
               $key = trim($key);
               if (array_key_exists($key, self::$styleCodes)) {
                  $codes[] = self::$styleCodes[$key];
               }
            }

            if ($codes) {
               $replace = "\033[".implode(';', $codes).'m';
               $content = str_replace($match, $replace, $content);
            }
         }
      }

      return $content;
   }
}

