<?

$moduleInstanceId = $_IPS['InstanceId'];
$message = $_IPS['Message'];
$log = $_IPS['Log'];

$moduleName = IPS_GetObject($moduleInstanceId)['ObjectName'];

LogMessage("Processing incoming message...");

if(preg_match_all('/^\r\n\+CMTI: \"(SM|ME)\",([0-9]+)\r\n$/', $message, $matches, PREG_SET_ORDER, 0)!=0) {
	LogMessage("It's a sms...");
	$readCommand = "AT+CMGR=".$matches[0][2];
	$deleteCommand = "AT+CMGD=".$matches[0][2];
	LogMessage("Read command is: ".$readCommand);
	LogMessage("Delete command is: ".$deleteCommand);
	SIM868SMSv2_SendCommand($moduleInstanceId, "AT+CMGF=1");
	$smsMessage = SIM868SMSv2_SendCommand($moduleInstanceId, $readCommand);
	SIM868SMSv2_SendCommand($moduleInstanceId, $deleteCommand);
	
	LogMessage("Analyzing the SMS: ". $smsMessage);
	
	if(preg_match_all('/^AT\+CMGR=(\d{1,2})\r\r\n\+CMGR: \"REC.+\",\"(.+)\",\"\",\".+\"\r\n(.+)\r\n\r\nOK\r\n$/is', $smsMessage, $matches, PREG_SET_ORDER, 0)!=0) {
		$sender = trim($matches[0][2]);
		$smsMessage = trim($matches[0][3]);
		
		LogMessage("Received SMS is from ".$sender);
		LogMessage("Received SMS text is ".$smsMessage);
		
		$smsCommands = json_decode(GSMS_GetSMSCommands($moduleInstanceId));
		$maxCount = count($smsCommands);
		
		LogMessage("Analyzing SMS text...");	
		for ($x=0; $x<$maxCount; $x++) {
			LogMessage("Checking SMS command: ".$smsCommands[$x]);
			if($smsMessage == $smsCommands[$x]['command']){
				LogMessage("The SMS is a command");
				LogMessage("Executing corresponding script: ".$smsCommands[$x]['script']);
				IPS_RunScriptEx($smsCommands[$x]['script']);
				break;
			}	
		}
		
		if($x==maxCount)
			LogMessage("No corresponding command found for the SMS");
	} else
		LogMessage("Unable to analyze the SMS");
	
} else
	LogMessage("Unkonwn message");	

return true;

function LogMessage($Message) {
	global $moduleName;
	global $log;
	
	if($log)
		IPS_LogMessage($moduleName, "_Dispatch: ".$Message);
}
	
?>