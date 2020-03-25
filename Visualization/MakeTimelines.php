<?php

include 'ManzParser.php';

set_error_handler('exceptions_error_handler');

function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

$fbnrArr = json_decode(file_get_contents(
'./fbnrList.json'));

foreach ($fbnrArr as $entry) {
	$fbnr = $entry->fbnr;
	$scheinunternehmenDate = $entry->scheinunternehmenDate;
	try 
	{

		$files = scandir('jsonFiles/');
		foreach ($files as $file) {
			$drawTimeline = escapeshellcmd('python3 timeline.py '.$file);
			$output = shell_exec($drawTimeline);
			echo 'SUCCESS: '.$file.' drawn'."\n";
		}
	} catch (Exception $e) {
		echo 'ERROR with '.$file.' ('.$fbnr.')'."\n";
	}
}

?>