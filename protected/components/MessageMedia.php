<?php
/**
 * This component allows communication with MessageMedia using their SOAP client + WebPush source check
 */
class MessageMedia extends CApplicationComponent
{
	public $username = NULL;
	public $password = NULL;

	protected $_MMSoap = NULL;

	public function init()
	{
		parent::init();
		if (empty($this->_MMSoap))
		{
			$this->_MMSoap = new MMSoap($this->username, $this->password, []);

			// add aditional services
			$this->_MMSoap->serviceConfirm = new ServiceConfirm();
			$this->_MMSoap->serviceBlock   = new ServiceBlock();
			$this->_MMSoap->serviceUnblock = new ServiceUnblock();
		}
	}

	/**
	 * send a single message to one recipient
	 *
	 * @param string  $sRecipient       recipient phone number
	 * @param string  $sMessage         message content
	 * @param int     $nUID             unique, allows us to identify the user - message pair for replies)
	 * @param boolean $bDeliveryReport  whether delivery reporting is requested for the message
	 * @param string  $sOrigin          origin phone number that the message will come from (NULL = rotary)
	 * @param string  $sScheduled       schedule a message for future delivery - YYYY-MM-DDThh:mm:ssZ - UTC time
	 * @param integer $nValidityPeriod  validity period of the message, default is 169 which means 3 days
	 *
	 * @return array                    account details, sent, scheduled, failed
	 */
	public function sendMessage($sRecipient, $sMessage, $nUID = 0, $bDeliveryReport = FALSE, $sOrigin = NULL, $sScheduled = NULL, $nValidityPeriod = NULL)
	{
		$aReturn           = ['credits' => 0, 'sent' => 0, 'scheduled' => 0, 'failed' => 0];
		$aRecipientStruct  = [new StructRecipientType($sRecipient, $nUID)];
		$oRecipientsStruct = new StructRecipientsType($aRecipientStruct);

		$sOrigin = str_replace(['.com', ' ', 'CLI'], '',  Yii::app()->name);

		$aMessagesList   = [new StructMessageType($sOrigin, $oRecipientsStruct, $sMessage, $sScheduled, $bDeliveryReport, $nValidityPeriod)];
		$oMessagesStruct = new StructMessageListType($aMessagesList);
		$oRequestBody    = new StructSendMessagesBodyType($oMessagesStruct);
		$oSendRequest    = new StructSendMessagesRequestType($this->_MMSoap->authentication, $oRequestBody);

		$oResponse = $this->_MMSoap->serviceSend->sendMessages($oSendRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error sending message: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			$oResult              = $oResponse->getResult();
			$aReturn['credits']   = $oResult->accountDetails->creditRemaining;
			$aReturn['sent']      = $oResult->sent;
			$aReturn['scheduled'] = $oResult->scheduled;
			$aReturn['failed']    = $oResult->failed;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding sendMessage response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		return $aReturn;
	}

	/**
	 * check reports
	 *
	 * @param int $nMaxReports  maximum number of reports to get in the response
	 *
	 * @return array            reports, returned, remaining
	 */
	public function checkReports($nMaxReports = NULL)
	{
		$aReturn     = ['reports' => [], 'returned' => 0, 'remaining' => 0];
		$oRequestBody = new StructCheckReportsBodyType($nMaxReports);
		$oGetRequest  = new StructCheckReportsRequestType($this->_MMSoap->authentication, $oRequestBody);
		$oResponse   = $this->_MMSoap->serviceCheck->checkReports($oGetRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error checking reports: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			$oResult = $oResponse->getResult();

			$aReturn['returned']  = $oResult->returned;
			$aReturn['remaining'] = $oResult->remaining;

			if ($oResult->returned > 0)
			{
				foreach($oResult->reports as $oReport)
				{
					$aReturn['reports'][] = [
						'recipient' => $oReport->recipient->_,
						'time'      => $oReport->timestamp->_,
						'uid'       => $oReport->uid,
						'receiptId' => $oReport->receiptId,
						'status'    => $oReport->status,
					];
				}
			}
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding checkReports response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		return $aReturn;
	}

	/**
	 * confirm reports
	 *
	 * @param array $aReports  array of receiptId's to confirm
	 *
	 * @return integer         number of reports successfully confirmed
	 */
	public function confirmReports($aReports)
	{
		$aReportList = [];

		foreach ($aReports as $nReceiptId)
		{
			$aReportList[] = new StructConfirmItemType($nReceiptId);
		}

		$oConfirmStruct  = new StructConfirmReportListType($aReportList);
		$oRequestBody    = new StructConfirmReportsBodyType($oConfirmStruct);
		$oSendRequest    = new StructConfirmReportsRequestType($this->_MMSoap->authentication, $oRequestBody);

		$oResponse = $this->_MMSoap->serviceConfirm->confirmReports($oSendRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error confirming reports: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			return $oResponse->getResult()->confirmed;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding confirmReports response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}
	}

	/**
	 * check replies
	 *
	 * @param int $nMaxReplies  maximum number of replies to get in the response
	 *
	 * @return array            replies, returned, remaining
	 */
	public function checkReplies($nMaxReplies = NULL)
	{
		$aReturnData = ['replies' => [], 'returned' => 0, 'remaining' => 0];

		$oRequestBody = new StructCheckRepliesBodyType($nMaxReplies);
		$oGetRequest  = new StructCheckRepliesRequestType($this->_MMSoap->authentication, $oRequestBody);
		$oResponse    = $this->_MMSoap->serviceCheck->checkReplies($oGetRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error checking replies: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			$oResult = $oResponse->getResult();

			if ($oResult->returned > 0)
			{
				$aReturnData['returned']  = $oResult->returned;
				$aReturnData['remaining'] = $oResult->remaining;

				if ($aReturnData['returned'] == 1)
				{
					$aReturnData['replies'][] = $this->_getReplyData($oResult->replies->reply);
				}
				else
				{
					foreach($oResult->replies->reply as $oReply)
					{
						$aReturnData['replies'][] = $this->_getReplyData($oReply);
					}
				}
			}
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding checkReplies response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		return $aReturnData;
	}

	protected function _getReplyData($oReply)
	{
		return [
			'origin'    => $oReply->origin ? $oReply->origin->_ : NULL,
			'time'      => is_string($oReply->received) ? $oReply->received : (string) $oReply->received->_,
			'content'   => is_string($oReply->content) ? $oReply->content : (string) $oReply->content->_,
			'format'    => $oReply->format,
			'uid'       => $oReply->uid,
			'receiptId' => $oReply->receiptId,
		];
	}

	/**
	 * confirm replies
	 *
	 * @param array $aReplies  array of receiptId's to confirm
	 *
	 * @return integer         number of replies successfully confirmed
	 */
	public function confirmReplies($aReplies)
	{
		$aReplyList = [];

		foreach ($aReplies as $nReceiptId)
		{
			$aReplyList[] = new StructConfirmItemType($nReceiptId);
		}

		$oConfirmStruct  = new StructConfirmReplyListType($aReplyList);
		$oRequestBody    = new StructConfirmRepliesBodyType($oConfirmStruct);
		$oSendRequest    = new StructConfirmRepliesRequestType($this->_MMSoap->authentication, $oRequestBody);

		$oResponse = $this->_MMSoap->serviceConfirm->confirmReplies($oSendRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error confirming replies: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			return $oResponse->getResult()->confirmed;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding confirmReplies response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}
	}

	/**
	 * block number
	 *
	 * @param string $sRecipient  recipient number to block
	 *
	 * @return boolean            action result
	 */
	public function blockNumber($sRecipient)
	{
		$aRecipientStruct         = [new StructRecipientType($sRecipient)];
		$oRecipientsStruct        = new StructRecipientsType($aRecipientStruct);
		$oBlockNumbersRequestBody = new StructBlockNumbersBodyType($oRecipientsStruct);
		$oBlockNumbersRequest     = new StructBlockNumbersRequestType($this->_MMSoap->authentication, $oBlockNumbersRequestBody);

		$oResponse = $this->_MMSoap->serviceBlock->blockNumbers($oBlockNumbersRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error blocking number: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			return (bool) $oResponse->result->blocked;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding blockNumber response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}
	}

	/**
	 * unblock number
	 *
	 * @param string $sRecipient  recipient number to unblock
	 *
	 * @return boolean            action result
	 */
	public function unblockNumber($sRecipient)
	{
		$aRecipientStruct           = [new StructRecipientType($sRecipient)];
		$oRecipientsStruct          = new StructRecipientsType($aRecipientStruct);
		$oUnblockNumbersRequestBody = new StructUnblockNumbersBodyType($oRecipientsStruct);
		$oUnblockNumbersRequest     = new StructUnblockNumbersRequestType($this->_MMSoap->authentication, $oUnblockNumbersRequestBody);

		$oResponse = $this->_MMSoap->serviceUnblock->unblockNumbers($oUnblockNumbersRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error unblocking number: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			return (int) $oResponse->result->unblocked;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding unblockNumber response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}
	}

	/**
	 * get list of blocked numbers
	 *
	 * @param integer $nMaxResults max results
	 *
	 * @return array results
	 */
	public function getBlocked($nMaxResults = NULL)
	{
		$aReturnData = ['found' => 0, 'returned' => 0, 'blockedNumbers' => []];

		$oGetBlockedNumbersRequestBody = new StructGetBlockedNumbersBodyType($nMaxResults);
		$oGetBlockedNumbersRequest     = new StructGetBlockedNumbersRequestType($this->_MMSoap->authentication, $oGetBlockedNumbersRequestBody);

		$oResponse = $this->_MMSoap->serviceGet->getBlockedNumbers($oGetBlockedNumbersRequest);

		if ($oResponse instanceof SoapFault)
		{
			Yii::log('Error getting list of blocked numbers: ' . $oResponse->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}

		try
		{
			if ($oResponse->result->found > 0)
			{
				$aReturnData['found'] = $oResponse->result->found;

				if ($oResponse->result->returned > 0)
				{
					$aReturnData['returned'] = $oResponse->result->returned;

					if ($aReturnData['returned'] == 1)
					{
						$aReturnData['blockedNumbers'][] = $oResponse->result->recipients->recipient->_;
					}
					else
					{
						foreach($oResponse->result->recipients->recipient as $oRecipient)
						{
							$aReturnData['blockedNumbers'][] = $oRecipient->_;
						}
					}
				}
			}

			return $aReturnData;
		}
		catch(Exception $e)
		{
			Yii::log('Error decoding unblockNumber response: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
			return FALSE;
		}
	}
}