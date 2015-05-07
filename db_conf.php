<?php
	if(!file_exists('db.conf'))
	{
		echo "Please check db.conf and correct configurement";
	}
	if(!($lines=file('db.conf',FILE_SKIP_EMPTY_LINES)))
	{
		echo "Error reading db.conf";
	}
	$i=0;
	foreach($lines as $line)
	{
		if(stripos($line,'#')!==0)
		{
			if($pos=stripos($line,'='))
			{
				$name=substr($line,0,$pos);
				$$name=trim(substr($line,$pos+1));
			}
		}		
	}
	//$mysqli = mysqli_connect('citypayroll.db.10341889.hostedresource.com','citypayroll','Payrollcity123#','citypayroll');
	$mysqli = mysqli_connect($HOSTNAME,$USERNAME,$PASSWORD,$DB_NAME);
	if ($mysqli->connect_errno) 
	{
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error."<br/>";
		exit("Database Failure");
	}
?>