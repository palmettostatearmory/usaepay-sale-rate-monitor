<?php

/*************************************************
* Set-up
*************************************************/
$config = include('config.php');

$client =  new SoapClient($config['wsdl'], array("trace"=>1, "exceptions"=>1));

// generate random seed value
date_default_timezone_set('America/New_York');
$seed=time() . rand();

// make hash value using sha1 function
$clear= $config['sourcekey'] . $seed . $config['pin'];
$hash=sha1($clear);

// assembly ueSecurityToken as an array
// (php5 will correct the type for us)
$tok=array(
	'SourceKey'=>$config['sourcekey'],
	'PinHash'=>array(
		'Type'=>'sha1',
		'Seed'=>$seed,
		'HashValue'=>$hash
	),
	'ClientIP'=>getHostByName(getHostName())
);

$now = date("Y-m-d H:i:s",time());
$now = date("Y-m-d H:i:s", date_add(date_create_from_format('Y-m-d H:i:s',$now), date_interval_create_from_date_string($config['timediff'] . ' hour'))->getTimestamp());

$message = "Now (adjusted server time): " . $now . "\n";

$interval = $config['interval'];

$message .= "Interval: " . $interval . " minutes\n------\n";

$firstIntervalStart =  date("Y-m-d H:i:s", date_sub(date_create_from_format('Y-m-d H:i:s',$now), date_interval_create_from_date_string($interval*2 . ' minute'))->getTimestamp());

$betweenIntervals =  date("Y-m-d H:i:s", date_sub(date_create_from_format('Y-m-d H:i:s',$now), date_interval_create_from_date_string($interval . ' minute'))->getTimestamp());

/*************************************************
* Do search for transactions in first interval.
*************************************************/

$search=array( 
    array( 
      'Field'=>'created',  
      'Type'=>'gt',  
      'Value'=> $firstIntervalStart),
    array( 
      'Field'=>'created',  
      'Type'=>'lt',  
      'Value'=> $betweenIntervals),
   array( 
      'Field'=>'type',  
      'Type'=>'eq',  
      'Value'=> 'S'),
 ); 

$start=0; 
$limit=1000; 
$matchall=true; 
$sort='created';

$res=$client->searchTransactions($tok,$search,$matchall,$start,$limit,$sort); 

$compareCt = $res->TransactionsMatched;

$message .= "Orders, first interval:  " . $compareCt . "\n";

$comparePerMin = round(($compareCt / $interval), 1);

$message .= "per min:  " . $comparePerMin . "\n------\n";


/*************************************************
* Do search for transactions in second interval.
*************************************************/

$search=array( 
    array( 
      'Field'=>'created',  
      'Type'=>'gt',  
      'Value'=> $betweenIntervals),
    array( 
      'Field'=>'created',  
      'Type'=>'lt',  
      'Value'=> $now),
   array( 
      'Field'=>'type',  
      'Type'=>'eq',  
      'Value'=> 'S'),
 ); 

$res=$client->searchTransactions($tok,$search,$matchall,$start,$limit,$sort); 

$nowCt = $res->TransactionsMatched;

$mostRecentOrder= end($res->Transactions);

$message .= "Orders, second interval:  " . $nowCt . "\n";

$nowPerMin = round(($nowCt / $interval), 1);

$message .= "per min:  " . $nowPerMin . "\n------\n";

/*************************************************
* Check growth rate accross intervals, send email
* if threshold passed.
*************************************************/

$growthRate = round(($nowPerMin / $comparePerMin), 1);

$message .= "Growth Rate:  " . $growthRate . "\n";

if ($mostRecentOrder) {
    $message .= "Most Recent Order:  " . $mostRecentOrder->DateTime . " (" . $mostRecentOrder->Details->OrderID . ")\n";
}

echo $message;

if($growthRate < $config['threshold']) {
        $email = $config['email'];
        $subject = 'SLOW ORDERS ALERT';
        $message = $message;

        if(mail($email, $subject, $message)) {
            echo "successfull email\n";
        } else {
            echo "unsuccessful email\n";
        }
}


