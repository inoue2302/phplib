<?php

namespace Mylib\Aws;


use \Aws\Sqs;

class Sqs
{

  // SQSクライアントオブジェクトを生成
  private static function get_sqs_client()
  {
	$aws_config = \Config::get('aws.sqs');
	return Sqs\SqsClient::factory($aws_config);
  }

  // SQSにメッセージを送信
  // 先程作成した[logs]というキュー名を$sqs_nameに入れて呼び出す
  public static function send_sqs_message($data)
  {
	// SQSクライアントを取得
	$client = self::get_sqs_client();
	// キューIDを指定してメッセージを送信する
	$client->sendMessageBatch(array('QueueUrl' => \Config::get('aws.sqs_url'),
		'Entries' => array(array('Id' => '1', 'MessageBody' => $data))
	));

  }

  // SQSからメッセージを受信
  public static function receive_sqs_message()
  {
	// SQSクライアントを取得
	$client = self::get_sqs_client();

	// キューのURLを取得する
	$queueUrl = $client->getQueueUrl(array('QueueName' => 'sqstest'));

	// キューからメッセージを取り出す
	$result = $client->receiveMessage(
		array(
		  'QueueUrl' => $queueUrl['QueueUrl'],
		)
	);
	//メッセージを取り出しエコーする
	$messages = $result->getPath('Messages/*/Body');
	if(!empty($messages)) {
	  self::work($messages[0]);
	  $receiptHandle = $result->getPath('Messages/*/ReceiptHandle');
	  $client->deleteMessage(array('QueueUrl' => $queueUrl['QueueUrl'], 'ReceiptHandle' => $receiptHandle[0])); 
	  sleep(1);
	} else {
	  \Cli::write("queue is empty".PHP_EOL);
	}
	
  }
  
  private static function work($message)
  {
	//echo $message.PHP_EOL;
	\Cli::write($message);
	sleep(1);
  }
  
  public static function receive_sqs_for_multi()
  {
	$t1 = microtime(true);
	$pcount = 3;
	$pstack = array();
	for($i=1;$i<=$pcount;$i++){
		$pid = pcntl_fork();
		if( $pid == -1 ) {
			die( 'fork できません' );
		} else if ($pid) {
			 // 親プロセスの場合
			$pstack[$pid] = true;
			if( count( $pstack ) >= $pcount ) {
				unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
			}
		} else {
			sleep( 1 );
			self::receive_sqs_message();
			exit(); //処理が終わったらexitする。
		}
	}
	//先に処理が進んでしまうので待つ
	while( count( $pstack ) > 0 ) {
		unset( $pstack[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
	}

	$t2 = microtime(true);
	$process_time = $t2 - $t1;
	\Cli::write("Process time = " . $process_time);
  }

}
