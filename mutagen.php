<?

include('IXR_Library.inc.php');

$ljClient = new IXR_Client('mutagen.ru', '/?xmlrpc');

$mutagen = new mutagen($ljClient);



$ljClient->query('mutagen.login',"mail@mail.ru","password"); //
$ljResponse = $ljClient->getResponse();
print "ID "; print_r($ljResponse); print "<br>";


// альтернативный вариант с паролем в md5
//$ljClient->query('mutagen.login',"mail@mail.ru",md5("password"),"true"); //
//$ljResponse = $ljClient->getResponse();
//print_r($ljResponse); print "<br>";


$ljClient->query('mutagen.balance');

$ljResponse = $ljClient->getResponse();
print "баланс "; print_r($ljResponse); print "<br>";

// конкуренция
$data=$mutagen->key_create_task("mp3");
$data=$mutagen->key_get_task($data["task_id"]);
print "конкуренция "; print_r($data); print "<br>";


// хвосты
$data=$mutagen->suggest_create_task("mp3");
$data=$mutagen->suggest_get_task($data["task_id"]);
print "хвосты ";print_r($data); print "<br>";




class mutagen{
/*
класс получения конкуренции и подсказок
*/
	function  mutagen($ljClient){
	$this->ljClient=$ljClient;
	}


	function method($option){
	/*
	вызов метода api
	*/

	$this->ljClient->query($option["method"],$option["var"]);
	$ljResponse = $this->ljClient->getResponse();

		if(isset($ljResponse["faultCode"])){
	   print $ljResponse["faultString"]; exit();
		}
	   else{
	   return $ljResponse;
	   }
	}


	function key_create_task($option){
   /*
   создаем задание на проверку ключей
   */
   return $this->method(array("method"=>"mutagen.check_key.create_task","var"=>$option));
	}

	function key_get_task($option){
	/*
	проверяем готово ли задание на проверку конкуренции
	*/
	$data=$this->method(array("method"=>"mutagen.check_key.get_task","var"=>$option));

	   if($data["status"]=="completed"){
		return $data;
	   }
	   elseif($data["status"]=="rejected"){
	   return "fail";
	   }
	   else{
	   sleep(5);
		return $this->key_get_task($option);
	   }
	}

	function suggest_create_task($option){
   /*
   создает задание на получение ключей
   */

   return $this->method(array("method"=>"mutagen.suggest.create_task","var"=>$option));
	}

	function suggest_get_task($option){
	/*
	проверяем готово ли задание
	*/
	$data=$this->method(array("method"=>"mutagen.suggest.get_task","var"=>$option));

	   if($data["status"]=="completed"){
		return $data;
	   }
	   elseif($data["status"]=="rejected"){
	   return "fail";
	   }
	   else{
	   sleep(5);
	   return $this->suggest_get_task($option);
	   }
	}
}
