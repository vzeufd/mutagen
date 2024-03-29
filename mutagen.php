<?

include('IXR_Library.inc.php');

$ljClient = new IXR_Client('api.mutagen.ru', '/xmlrpc');

$mutagen = new mutagen($ljClient);



$ljClient->query('mutagen.login',"mail@mail.ru","password"); //
$ljResponse = $ljClient->getResponse();
print "авторизация ID "; print_r($ljResponse); print "<br>";

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

/*
список парсеров вордстат

wordstat_key - левая колонка вордстат, 40 страниц, 2000 ключей
wordstat_key_50 - левая колонка вордстат, первая страница, 50 ключей.
wordstat_n - частотность вордстат
wordstat_q - частотность "вордстат".
wordstat_qs - частотность !"вордстат".
direct - биды директ
*/
$ljClient->query('mutagen.parser.get',"mp3","direct","2");
$ljResponse = $ljClient->getResponse();
print "-один запрос к парсеру direct в регионе 2 (Санкт-Петербург)"; print_r($ljResponse); print "<br>";

$ljClient->query('mutagen.parser.get',"mp3","direct");
$ljResponse = $ljClient->getResponse();
print "-один запрос к парсеру direct без указания региона"; print_r($ljResponse); print "<br>";

$ljClient->query('mutagen.parser.mass.new',array("mp3","mp3 скачать"),"wordstat_n",2,"проверка из api");
$ljResponse = $ljClient->getResponse();
print "-масс проверка создание "; print_r($ljResponse); print "<br>";


$ljClient->query('mutagen.parser.mass.list');
$ljResponse = $ljClient->getResponse();
print "-список масс проверок "; print_r($ljResponse); print "<br>";

$last_mass_id=key($ljResponse);

$ljClient->query('mutagen.parser.mass.id',$last_mass_id);
$ljResponse = $ljClient->getResponse();
print "-масс проверка получение "; print_r($ljResponse); print "<br>";


# запрос к отчету Мега-инструмента
$ljClient->query("mutagen.serp.report",[
"region"=>"yandex_msk",
"report"=>"report_keywords_organic",
"domain"=>"mutagen.ru",
"limit"=>10,
]);

$ljResponse = $ljClient->getResponse(); 
print "10 фраз по которым сайт mutagen.ru находится в поиске в регионе yandex_msk<br>";
print_r(json_decode($ljResponse,1));


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
