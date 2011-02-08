<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Helper functions for working in a command-line environment.
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_CLI {

	public static $wait_msg = 'Press any key to continue...';

	protected static $foreground_colors = array(
		'black' => '0;30',
		'dark_gray' => '1;30',
		'blue' => '0;34',
		'light_blue' => '1;34',
		'green' => '0;32',
		'light_green' => '1;32',
		'cyan' => '0;36',
		'light_cyan' => '1;36',
		'red' => '0;31',
		'light_red' => '1;31',
		'purple' => '0;35',
		'light_purple' => '1;35',
		'brown' => '0;33',
		'yellow' => '1;33',
		'light_gray' => '0;37',
		'white' => '1;37',
	);

	protected static $background_colors = array(
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'magenta' => '45',
		'cyan' => '46',
		'light_gray' => '47',
	);

	/**
	 * Returns one or more command-line options. Options are specified using
	 * standard CLI syntax:
	 *
	 *     php index.php --username=john.smith --password=secret --var="some value with spaces"
	 *
	 *     // Get the values of "username" and "password"
	 *     $auth = CLI::options('username', 'password');
	 *
	 * @param   string  option name
	 * @param   ...
	 * @return  array
	 */
	public static function options($options)
	{
		// Get all of the requested options
		$options = func_get_args();

		// Found option values
		$values = array();

		// Skip the first option, it is always the file executed
		for ($i = 1; $i < $_SERVER['argc']; $i++)
		{
			if ( ! isset($_SERVER['argv'][$i]))
			{
				// No more args left
				break;
			}

			// Get the option
			$opt = $_SERVER['argv'][$i];

			if (substr($opt, 0, 2) !== '--')
			{
				// This is not an option argument
				continue;
			}

			// Remove the "--" prefix
			$opt = substr($opt, 2);

			if (strpos($opt, '='))
			{
				// Separate the name and value
				list ($opt, $value) = explode('=', $opt, 2);
			}
			else
			{
				$value = NULL;
			}

			if (in_array($opt, $options))
			{
				// Set the given value
				$values[$opt] = $value;
			}
		}

		return $values;
	}

	/**
	 * Reads input from the user. This can have either 1 or 2 arguments.
	 *
	 * Usage:
	 *
	 * // Waits for any key press
	 * CLI::read();
	 *
	 * // Takes any input
	 * $color = CLI::read('What is your favorite color?');
	 *
	 * // Will only accept the options in the array
	 * $ready = CLI::read('Are you ready?', array('y','n'));
	 *
	 * @author     Fuel Development Team
	 * @license    MIT License
	 * @copyright  2010 - 2011 Fuel Development Team
	 * @link       http://fuelphp.com
	 * @return string the user input
	 */
	public static function read()
	{
		$args = func_get_args();

		// Ask question with options
		if (count($args) == 2)
		{
			list($output, $options) = $args;
		}

		// No question (probably been asked already) so just show options
		elseif (count($args) == 1 && is_array($args[0]))
		{
			$output = '';
			$options = $args[0];
		}

		// Question without options
		elseif (count($args) == 1 && is_string($args[0]))
		{
			$output = $args[0];
			$options = array();
		}

		// Run out of ideas, EPIC FAIL!
		else
		{
			$output = '';
			$options = array();
		}

		// If a question has been asked with the read
		if (!empty($output))
		{
			$options_output = '';
			if (!empty($options))
			{
				$options_output = ' [ '.implode(', ', $options).' ]';
			}

			fwrite(STDOUT, $output.$options_output.': ');
		}

		// Read the input from keyboard.
		$input = trim(fgets(STDIN));

		// If options are provided and the choice is not in the array, tell them to try again
		if (!empty($options) && !in_array($input, $options))
		{
			Minion_CLI::write('This is not a valid option. Please try again.'.PHP_EOL);

			$input = Minion_CLI::read($output, $options);
		}

		// Read the input
		return $input;
	}

	/**
	 * Outputs a string to the cli. If you send an array it will implode them
	 * with a line break.
	 *
	 * @author     Fuel Development Team
	 * @license    MIT License
	 * @copyright  2010 - 2011 Fuel Development Team
	 * @link       http://fuelphp.com
	 * @param string|array $text the text to output, or array of lines
	 */
	public static function write($text = '', $foreground = null, $background = null)
	{
		if (is_array($text))
		{
			$text = implode(PHP_EOL, $text);
		}

		if ($foreground OR $background)
		{
			$text = Minion_CLI::color($text, $foreground, $background);
		}

		fwrite(STDOUT, $text.PHP_EOL);
	}

	/**
	 * Waits a certain number of seconds, optionally showing a wait message and
	 * waiting for a key press.
	 *
	 * @author     Fuel Development Team
	 * @license    MIT License
	 * @copyright  2010 - 2011 Fuel Development Team
	 * @link       http://fuelphp.com
	 * @param int $seconds number of seconds
	 * @param bool $countdown show a countdown or not
	 */
	public static function wait($seconds = 0, $countdown = false)
	{
		if ($countdown === true)
		{
			$time = $seconds;

			while ($time > 0)
			{
				fwrite(STDOUT, $time.'... ');
				sleep(1);
				$time--;
			}

			Minion_CLI::write();
		}
		else
		{
			if ($seconds > 0)
			{
				sleep($seconds);
			}
			else
			{
				Minion_CLI::write(Minion_CLI::$wait_msg);
				Minion_CLI::read();
			}
		}
	}

	/**
	 * Returns the given text with the correct color codes for a foreground and
	 * optionally a background color.
	 *
	 * @author     Fuel Development Team
	 * @license    MIT License
	 * @copyright  2010 - 2011 Fuel Development Team
	 * @link       http://fuelphp.com
	 * @param string $text the text to color
	 * @param atring $foreground the foreground color
	 * @param string $background the background color
	 * @return string the color coded string
	 */
	public static function color($text, $foreground, $background = null)
	{

		if (Kohana::$is_windows)
		{
			return $text;
		}

		if (!array_key_exists($foreground, Minion_CLI::$foreground_colors))
		{
			throw new Kohana_Exception('Invalid CLI foreground color: '.$foreground);
		}

		if ($background !== null and !array_key_exists($background, Minion_CLI::$background_colors))
		{
			throw new Kohana_Exception('Invalid CLI background color: '.$background);
		}

		$string = "\033[".Minion_CLI::$foreground_colors[$foreground]."m";

		if ($background !== null)
		{
			$string .= "\033[".Minion_CLI::$background_colors[$background]."m";
		}

		$string .= $text."\033[0m";

		return $string;
	}

} // End CLI
