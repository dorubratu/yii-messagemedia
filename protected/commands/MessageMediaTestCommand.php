<?php
/**
 * This command is used to test the MessageMedia API
 */
class MessageMediaTestCommand extends ConsoleCommand
{
	protected $_aActions = [
		'sendMessage'    => 'Sends a message',
		'checkReplies'   => 'Checks replies',
		'checkReports'   => 'Checks reports',
		'confirmReplies' => 'Confirms replies',
		'confirmReports' => 'Confirms reports',
		'blockNumber'    => 'Blocks number',
		'unblockNumber'  => 'Unblocks number',
		'getBlocked'     => 'Gets list of blocked numbers',
	];

	public function getHelp()
	{
		$sHelp = <<<EOD
USAGE
  yiic messagemediatest <action> [params]

DESCRIPTION
  Executes the specified action and outputs the result

ACTIONS

EOD;
		foreach ($this->_aActions as $sAction => $sActionHelp)
		{
			$sHelp .= sprintf('%-21s- ', '* ' . $sAction) . $sActionHelp . PHP_EOL;
		}

		$sHelp .= PHP_EOL;
		$sHelp .= 'PARAMS' . PHP_EOL;
		$sHelp .= '* to                 - Destination number' . PHP_EOL;
		$sHelp .= '* content            - Message content' . PHP_EOL;
		$sHelp .= '* uid                - Message UID' . PHP_EOL;
		$sHelp .= '* receiptId          - ReceiptId to confirm' . PHP_EOL;
		$sHelp .= '* number             - Number to block/unblock ' . PHP_EOL;

		return $sHelp;
	}

	public function run($aArgs)
	{
		$this->parseArguments($aArgs);

		if (!isset($this->_args[0]))
		{
			$this->usageError('No action specified');
		}

		$sAction = $this->_args[0];
		if (!array_key_exists($sAction, $this->_aActions))
		{
			$this->usageError("Unknown action ('$sAction') specified");
		}

		if ($this->_args[0] == 'sendMessage' && !isset($this->_args['--to']))
		{
			$this->usageError('you need to specify a destination for the message (--to)');
		}

		if ($this->_args[0] == 'sendMessage' && !isset($this->_args['--content']))
		{
			$this->usageError('you need to specify a content for the message (--content)');
		}

		if ($this->_args[0] == 'sendMessage' && !isset($this->_args['--uid']))
		{
			$this->_args['--uid'] = 0;
		}

		if (in_array($this->_args[0], ['confirmReplies', 'confirmReports']) && !isset($this->_args['--receiptId']))
		{
			$this->usageError('you need to specify a receiptId to confirm (--receiptId)');
		}

		if (in_array($this->_args[0], ['blockNumber','unblockNumber']) && !isset($this->_args['--number']))
		{
			$this->usageError('you need to specify a number to block/unblock (--number)');
		}

		switch ($sAction)
		{
			case 'sendMessage':
				$mResult = Yii::app()->messageMedia->sendMessage($this->_args['--to'], $this->_args['--content'], $this->_args['--uid']);
				break;

			case 'confirmReplies':
			case 'confirmReports':
				$mResult = Yii::app()->messageMedia->{$sAction}($this->_args['--receiptId']);
				break;

			case 'blockNumber':
			case 'unblockNumber':
				$mResult = Yii::app()->messageMedia->{$sAction}($this->_args['--number']);
				break;

			default:
				$mResult = Yii::app()->messageMedia->{$sAction}();
				break;
		}

		echo var_dump($mResult);
    Yii::app()->end();
	}
}