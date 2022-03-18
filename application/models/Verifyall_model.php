<?php

/**
 * VerifyAll Model Class
 *
 *
 * @category Verify Model
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

class Verifyall_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Проверки
     * @param $type // тип проверки, на данный момент используется только add
     * @return mixed
     *
     * type=add - вход из controller/api.php/add:51
     */
    function _verify($type)
    {
        $auth_base = $this->input->get_request_header('Authorization', TRUE);   // получаем заголовки авторизации
        $data = json_decode($this->security->xss_clean($this->input->raw_input_stream));    // получаем тело запроса и переводим из json в объект
        if (!isset($data->apikey)) {    // если в запросе отсутствует apikey - то возвращаем ошибку авторизации
            $this->system->_response(array("message" => "Unauthorized"), 401);
        }
        $query = $this->db->get_where('lidogen', array('api_key' => $data->apikey))->row(); // запрашиваем соответствие apikey и лидогенератора
        switch ($type) {
            case "add":
                if (!isset($query->authkey)) $this->system->_response(array("message" => "Unauthorized not authkey"), 401); // у лидогенератора нет authkey
                if ($auth_base != $query->authkey) $this->system->_response(array("message" => array("Unauthorized bad verify authkey",$auth_base)), 401); //ошибка авторизации authkey
                if (!isset($data->apikey)) $this->system->_response(array("message" => "Unauthorized not apikey"), 401); //не указан apikey (сюда и не попадаем, но пусть будет)
                $data->id_lidgen = $query->id; //добавляем id лидогенератора к данным
                $data->lidgen = $query->name;   //добавляем имя лидогенератора к данным
                if ($data->id_lidgen == 39 && isset($data->utm_source_original)) {
                    $q = $this->db->get_where('lidogen', array('name' => $data->utm_source_original))->row();
                    if (isset($q->active) && $q->active==1) {
                        $data->id_lidgen = $q->id;
                        $data->lidgen = $q->name;
                    }
                }
                break;
            case "register":
                if (!isset($data->name)) {
                    $this->system->_response(array('message' => 'Not parametrs'), 404);
                };
                break;
            case "postback":
                if ($auth_base != $query->authkey) $this->system->_response(array("message" => "Unauthorized"), 401);
                if (!isset($data->apikey)) $this->system->_response(array("message" => "Unauthorized"), 401);
                break;
        }
        return $data;   //возвращаем полученные данные обратно в /controller/api.php/add
    }

    /**
     * Проверка заполнения данных заявки
     * вход из controller/api.php/save_bd:62
     * @param $data
     * @return array
     */
    function verify_lines($data)
    {
        $error = array();
        if (!isset($data->product->amount)) {
            $error[] = "Not product->amount";
        } else if (empty($data->product->amount)) $error[] = "Empty product->amount";
        if (!isset($data->product->period)) {
            $error[] = "Not product->period";
        } else if (empty($data->product->period)) $error[] = "Empty product->period";
        if (!isset($data->client->last_name)) {
            $error[] = "Not client->last_name";
        } else if (empty($data->client->last_name)) $error[] = "Empty client->last_name";
        if (!isset($data->client->first_name)) {
            $error[] = "Not client->first_name";
        } else if (empty($data->client->first_name)) $error[] = "Empty client->first_name";
        if (!isset($data->client->middle_name)) {
            $error[] = "Not client->middle_name";
        } else if (empty($data->client->middle_name)) $error[] = "Empty client->middle_name";
        if (!isset($data->client->birthday)) {
            $error[] = "Not client->birthday";
        } else if (empty($data->client->birthday)) $error[] = "Empty client->birthday";
        if (!isset($data->client->birthplace)) {
            $error[] = "Not client->birthplace";
        } else if (empty($data->client->birthplace)) $error[] = "Empty client->birthplace";
        if (!isset($data->client->phone)) {
            $error[] = "Not client->phone";
        } else if (empty($data->client->phone)) $error[] = "Empty client->phone";
        if (!isset($data->client->passport->series)) {
            $error[] = "Not client->passport->series";
        } else if (empty($data->client->passport->series)) $error[] = "Empty client->passport->series";
        if (!isset($data->client->passport->number)) {
            $error[] = "Not client->passport->number";
        } else if (empty($data->client->passport->number)) $error[] = "Empty client->passport->number";
        if (!isset($data->client->passport->date_of_issue)) {
            $error[] = "Not client->passport->date_of_issue";
        } else if (empty($data->client->passport->date_of_issue)) $error[] = "Empty client->passport->date_of_issue";
        if (!isset($data->client->passport->organization)) {
            $error[] = "Not client->passport->organization";
        } else if (empty($data->client->passport->organization)) $error[] = "Empty client->passport->organization";
        if (!isset($data->client->passport->code)) {
            $error[] = "Not client->passport->code";
        } else if (empty($data->client->passport->code)) $error[] = "Empty client->passport->code";
        if (!isset($data->client->addresses->registration->index)) {
            $error[] = "Not client->addresses->registration->index";
        } else if (empty($data->client->addresses->registration->index)) $error[] = "Empty client->addresses->registration->index";
        if (!isset($data->client->addresses->registration->region)) {
            $error[] = "Not client->addresses->registration->region";
        } else if (empty($data->client->addresses->registration->region)) $error[] = "Empty client->addresses->registration->region";
        if (!isset($data->client->addresses->registration->city)) {
            $error[] = "Not client->addresses->registration->city";
        } else if (empty($data->client->addresses->registration->city)) $error[] = "Empty client->addresses->registration->city";
        if (!isset($data->client->addresses->registration->street)) {
            $error[] = "Not client->addresses->registration->street";
        } else if (empty($data->client->addresses->registration->street)) $error[] = "Empty client->addresses->registration->street";
        if (!isset($data->client->addresses->registration->house)) {
            $error[] = "Not client->addresses->registration->house";
        } else if (empty($data->client->addresses->registration->house)) $error[] = "Empty client->addresses->registration->house";
        if (!isset($data->client->addresses->match_addresses)) {
            $error[] = "Not client->addresses->match_addresses";
        }
        if (!isset($data->client->addresses->match_addresses) || $data->client->addresses->match_addresses != 1) {
            if (!isset($data->client->addresses->residential->index)) {
                $error[] = "Not client->addresses->residential->index";
            } else if (empty($data->client->addresses->residential->index)) $error[] = "Empty client->addresses->residential->index";
            if (!isset($data->client->addresses->residential->region)) {
                $error[] = "Not client->addresses->residential->region";
            } else if (!isset($data->client->addresses->residential->region)) $error[] = "Empty client->addresses->residential->region";
            if (!isset($data->client->addresses->residential->city)) {
                $error[] = "Not client->addresses->residential->city";
            } else if (empty($data->client->addresses->residential->city)) $error[] = "Empty client->addresses->residential->city";
            if (!isset($data->client->addresses->residential->street)) {
                $error[] = "Not client->addresses->residential->street";
            } else if (empty($data->client->addresses->residential->street)) $error[] = "Empty client->addresses->residential->street";
            if (!isset($data->client->addresses->residential->house)) {
                $error[] = "Not client->addresses->residential->house";
            } else if (empty($data->client->addresses->residential->house)) $error[] = "Empty client->addresses->residential->house";
        }
        return $error; //возвращаем полученные данные обратно в /controller/api.php/save_bd
    }
}

/* End of file Verifyall_model.php */
/* Location: ./application/models/Verifyall_model.php */