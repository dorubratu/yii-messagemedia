<?php
/**
 * Base class for commands
 */
abstract class ConsoleCommand extends CConsoleCommand
{
	protected static $_oCurrent = NULL;

	protected $_args = NULL;

	public function init()
	{
		parent::init();
		self::$_oCurrent = $this;
	}

	public static function getCurrent()
	{
		return self::$_oCurrent;
	}

	protected function parseArguments($aArgs)
	{
		foreach($aArgs as $sArgument)
		{
			$aArguments = explode('=', $sArgument);
			if (isset($aArguments[0]) && isset($aArguments[1]))
			{
				$this->_args[$aArguments[0]] = $aArguments[1];
			}
			else
			{
				// Add as a numeric argument
				$this->_args[] = $aArguments[0];
			}
		}

		if (isset($this->_args['--verbose']) && $this->_args['--verbose'] == 1)
		{
			Yii::$verbose = TRUE;
			unset($this->_args['--verbose']);
		}

		return $this->_args;
	}

	protected function getArgument($sName, $defaultValue = FALSE)
	{
		$sName = '--' . $sName;
		if (isset($this->_args[$sName]) && !empty($this->_args[$sName]))
		{
			return $this->_args[$sName];
		}

		return $defaultValue;
	}
}
