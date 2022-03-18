<?php

/**
 * System Model Class
 *
 *
 * @category System Main Model
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

class System_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Очистка списка лидогенераторов, которые не подтвердили регистрацию.
     * @param $data
     */
    function __clearDB()
    {
//        $this->db->simple_query("DELETE FROM lidogen WHERE `temp`>0 AND `datetime`<NOW()");
        $this->db->where('temp>0')
            ->where('`datetime`<NOW()')
            ->delete('lidogen');
    }

    /**
     * Быстрый запрос curl без ожидания ответа
     * @param $url
     * @return array
     */
    function fast_request($url)
    {
        $this->savelog('url_postback',array($url));
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
        $rez = curl_exec($cURLConnection);
        $http_code = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);
        return array('code'=>$http_code,'response'=>json_decode($rez));
    }

    /**
     * Генерируем данные в формате, подходящем для 1с
     * вход данных из controller/api.php/save_bd:70
     * @param $data
     * @return array
     */
    function generate21c($data)
    {
        $onecapi["ID"] = 'lidgen';
        $onecapi["utm_source"] = $data->lidgen;
        $onecapi["utm_term"] = $data->id_lidgen . '-' . $this->uuid->v4(false);
        if ($data->id_lidgen == 39) {
            $onecapi["ID"] = 'nolead';
            if(isset($data->utm_source_original)) {
                $onecapi["utm_source"] = $data->utm_source_original;
                $onecapi["utm_content"]=(isset($data->utm_content))?$data->utm_content:null;
                $onecapi["utm_medium"]=(isset($data->utm_medium))?$data->utm_medium:null;
                $onecapi["utm_campaign"]=(isset($data->utm_campaign))?$data->utm_campaign:null;
                $onecapi["click_id"]=(isset($data->click_id))?$data->click_id:null;
                $onecapi["webmaster_id"]=(isset($data->webmaster_id))?$data->webmaster_id:null;
            }
        }
        $onecapi["last_name"] = (!isset($data->client->last_name)) ? '' : $data->client->last_name;
        $onecapi["first_name"] = (!isset($data->client->first_name)) ? '' : $data->client->first_name;
        $onecapi["middle_name"] = (!isset($data->client->middle_name)) ? '' : $data->client->middle_name;
        $onecapi["phone"] = (!isset($data->client->phone)) ? '' : $data->client->phone;
        $onecapi["birthday"] = (!isset($data->client->birthday)) ? '' : $this->dateConvert1c($data->client->birthday);
        $onecapi["birthplace"] = (!isset($data->client->birthplace)) ? '' : $data->client->birthplace;
        $onecapi["email"] = (!isset($data->client->email)) ? '' : $data->client->email;
        $onecapi["amount"] = (!isset($data->product->amount)) ? '' : $data->product->amount;
        $period1c = (!isset($data->product->period)) ? '4' : $data->product->period;
        $oneCProduct[0] = "СТАРТОВЫЙ";
        $oneCProduct[1] = "ЦЕЛЕВОЙ 0,95";
        if ($onecapi["amount"] > 30000) {
            $interval1c = $period1c / 2;
            $onecapi["interval"] = $interval1c;
            $onecapi["creditproduct"] = $oneCProduct[1];
        } else {
            $onecapi["creditproduct"] = $oneCProduct[0];
            $newDate = new DateTime();
            $i = $period1c * 7;
            $newDate->add(new DateInterval("P" . $i . "D"));
            $fomattedDate = $newDate->format('Y-m-d');
            $daytoend = $fomattedDate;
            $onecapi["period"] = $daytoend;
        }
        if ($data->client->addresses->match_addresses == 1) {
            $data->client->addresses->residential = $data->client->addresses->registration;
        }
        $onecapi["passport_series"] = (!isset($data->client->passport->series)) ? '' : $data->client->passport->series;
        $onecapi["passport_number"] = (!isset($data->client->passport->number)) ? '' : $data->client->passport->number;
        $onecapi["passport_date_of_issue"] = (!isset($data->client->passport->date_of_issue)) ? '' : $this->dateConvert1c($data->client->passport->date_of_issue);
        $onecapi["passport_org"] = (!isset($data->client->passport->organization)) ? '' : $data->client->passport->organization;
        $onecapi["passport_code"] = (!isset($data->client->passport->code)) ? '' : $data->client->passport->code;
        $onecapi["registration_country"] = (!isset($data->client->addresses->registration->country)) ? '' : $data->client->addresses->registration->country;
        $onecapi["registration_index"] = (!isset($data->client->addresses->registration->index)) ? '' : $data->client->addresses->registration->index;
        $onecapi["registration_region"] = (!isset($data->client->addresses->registration->region)) ? '' : $data->client->addresses->registration->region;
        $onecapi["registration_city"] = (!isset($data->client->addresses->registration->city)) ? '' : $data->client->addresses->registration->city;
        $onecapi["registration_street"] = (!isset($data->client->addresses->registration->street)) ? '' : $data->client->addresses->registration->street;
        $onecapi["registration_house"] = (!isset($data->client->addresses->registration->house)) ? '' : $data->client->addresses->registration->house;
        $onecapi["registration_building"] = (!isset($data->client->addresses->registration->building)) ? '' : $data->client->addresses->registration->building;
        $onecapi["registration_apartment"] = (!isset($data->client->addresses->registration->apartment)) ? '' : $data->client->addresses->registration->apartment;
        $onecapi["match_addresses"] = (!isset($data->client->addresses->match_addresses)) ? '' : $data->client->addresses->match_addresses;
        $onecapi["residential_country"] = (!isset($data->client->addresses->residential->country)) ? '' : $data->client->addresses->residential->country;
        $onecapi["residential_index"] = (!isset($data->client->addresses->residential->index)) ? '' : $data->client->addresses->residential->index;
        $onecapi["residential_region"] = (!isset($data->client->addresses->residential->region)) ? '' : $data->client->addresses->residential->region;
        $onecapi["residential_city"] = (!isset($data->client->addresses->residential->city)) ? '' : $data->client->addresses->residential->city;
        $onecapi["residential_street"] = (!isset($data->client->addresses->residential->street)) ? '' : $data->client->addresses->residential->street;
        $onecapi["residential_house"] = (!isset($data->client->addresses->residential->house)) ? '' : $data->client->addresses->residential->house;
        $onecapi["residential_building"] = (!isset($data->client->addresses->residential->building)) ? '' : $data->client->addresses->residential->building;
        $onecapi["residential_apartment"] = (!isset($data->client->addresses->residential->apartment)) ? '' : $data->client->addresses->residential->apartment;
        $onecapi["NewLoanFromLK"] = "True";
        //log_message('error',print_r($onecapi,true));
        return $onecapi; // возврат данных в controller/api.php/save_bd
    }

    /**
     * Конвертация даты из d.m.Y в Y-m-d
     * @param $dateStrRU
     * @return string
     */
    function dateConvert1c($dateStrRU)
    {
        $date = DateTime::createFromFormat('d.m.Y', $dateStrRU);
        $new_date = $date->format('Y-m-d');
        $rezult = $new_date;
        return $rezult;
    }

    /**
     * Конвертация даты из Y-m-d в d.m.Y
     * @param $dateStr1c
     * @return string
     */
    public function dateConvertFrom1c($dateStr1c)
    {
        $date = DateTime::createFromFormat('Y-m-d', $dateStr1c);
        $new_date = $date->format('d.m.Y');
        $rezult = $new_date;
        return $rezult;
    }


    /**
     * Ответ клиенту json из полученного массива с указаным http-кодом
     * @param array $data
     * @param int $status
     * @return void
     */
    function _response($data, $status)
    {
        $rez = array('status' => $status, 'data' => $data);
        $output=json_encode($rez, JSON_UNESCAPED_UNICODE);
        $output=mb_ereg_replace('\n','',$output);
        $output=mb_ereg_replace('\t','',$output);
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output($output)
            ->_display();
        die();
    }

    /**
     * Запрос Curl с ожиданием ответа в течении длительного времени. Используется для отправки запроса в 1с
     * @param $data
     * @return array
     */
    function curl_request($data)
    {
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($curl, CURLOPT_TIMEOUT, 240);
            curl_setopt($curl, CURLOPT_URL, $data['URL']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data['request']);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data['request']),
                    'Authorization: Basic ' . base64_encode($data['login'] . ':' . $data['password'])
                )
            );
            $result = curl_exec($curl);
        } else {
            $result = false;
        }
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $rez['result'] = $result;
        $rez['httpCode'] = $httpCode;
        return $rez;
    }


    /**
     * Запись данных из $data в файл $file
     * @param string $file
     * @param array|object $data
     * @return bool
     */
    public function savelog($file, $data) {
		$folder=date('Y-m-d');
        if(!is_dir('logs/')) {
            mkdir('logs/');
        }
		if(!is_dir('logs/'.$folder)) {
			mkdir('logs/'.$folder);
		}
		$handle=fopen('logs/'.$folder.'/'.$file.'.log','a');
		$dat=date('Y-m-d H:i:s');
		fwrite($handle,'----'.$dat.'-------------------------'."\n");
		fwrite($handle,print_r($data,true));
		fwrite($handle,'----/'.$dat.'------------------------'."\n\n");
		fclose($handle);	
		return true;
	}
	
}

/* End of file System_model.php */
/* Location: ./application/models/System_model.php */