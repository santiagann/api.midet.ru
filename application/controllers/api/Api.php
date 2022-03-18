<?php

/**
 * Api Class
 *
 *
 * @category Main Controller
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * @property postback_model $postback
 * @property lidogen_model $lidogen
 * @property verifyall_model $verify
 * @property system_model $system
 * @property uuid $uuid
 */
class Api extends CI_Controller
{

    var $postback_add_id;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('postback_model', 'postback');
        $this->load->model('lidogen_model', 'lidogen');
        $this->load->model('verifyall_model', 'verify');
        $this->load->model('system_model', 'system');
        $this->load->model('onecserver_model', '1c');
        $this->load->library('uuid');
    }

    /**
     * @return void
     */
    public function index()
    {
        echo 'api';
    }

    /**
     * @return void
     */
    public function instruction()
    {
        echo '<pre>';
        echo 'API использует JSON и Basic Authorization
        <a href="/schema.json">Схема JSON</a>' . "\n\n";
        echo file_get_contents('schema.json');
        echo '</pre>';
    }

    /*
     * Добавление запроса в базу данных
     * получает в теле post-запроса данные заявки в соответствии с json-schema
     */
    /**
     * @return void
     */
    public function add()
    {
        $data = $this->verify->_verify('add');                 // проверка на правильность ключей api и авторизации. Возвращает структурированные данные, распарсенные из json
        //model/Verifyall.php/_verify
        $data->date_add = date('Y-m-d H:i:s');          // добавляет в данные дату и время добавления заявки
        $data->date_last_update = date('Y-m-d H:i:s');  // добавляет в данные дату и время последнего обновления записи
        $this->save_bd($data);                                  // отправляет на запись в БД
    }

    public function save_bd($data)
    {
        if (isset($data->client->addresses->registration->index) && (empty($data->client->addresses->registration->index) or is_null($data->client->addresses->registration->index))) $data->client->addresses->registration->index = "000000"; // так как не все операторы предоставляют индексы к адресам - если индексы не указаны - заменяем их на индекс 000000
        if (isset($data->client->addresses->residential->index) && (empty($data->client->addresses->residential->index) or is_null($data->client->addresses->residential->index))) $data->client->addresses->residential->index = "000000"; // так как не все операторы предоставляют индексы к адресам - если индексы не указаны - заменяем их на индекс 000000
        $error = $this->verify->verify_lines($data);    // проверка на заполнение всех необходимых данных. возвращается список ошибок заполнения
        // /model/verifyall.php/verify_lines
        if (count($error) > 0) {                          // если список ошибок не пустой
            //    $t = json_encode($error, JSON_UNESCAPED_UNICODE);
            $this->system->_response(array("messages" => $error), 400); //возвращаем клиенту 400 ошибку со списком ошибок. Отправляем необходимый нам http-код и массив с ошибками
        }
        $lidgen_v = $data->lidgen . '_verify'; // получаем название функции проверки лидогенератора, на случай существования такой

        $onecapi = $this->system->generate21c($data);  // генерируем данные из заявки в формат 1c
        // model/system_model.php/generate1c:52
        $saved = $onecapi;                              //формируем данные для записи в БД
        $saved['id_lidogen'] = $data->id_lidgen;        //добавляем к записи в БД id лидогенератора
        $saved['transaction_id'] = $saved['utm_term'];  //добавляем поле transaction_id и присваиваем ему значение из поля utm_term
        $newDate = new DateTime();                      //текущая дата
        $fomattedDate = $newDate->format('Y-m-d H:i:s'); //текущая дата в формате для SQL
        $saved["date_created"] = $fomattedDate;                 //date_created = текущая дата в формате для SQL
        $saved['sendOK'] = 0;                                   // признак отправки в 1с (0 - не отправлено, 1 - отпралено
        if ($saved['phone'] == '89999999999') {                    // признак тестовой заявки
            $saved['reserved'] = 1;                               // признак подхваченной заявки скриптом обработкти(xnj ,s yt jnghfdbkfcm fdnjvfnbxtcrb)
        }
        if (isset($data->utm_source_original)) $saved['utm_source_original'] = $data->utm_source_original; //если есть поле utm_source_original - то пишем в базу его
        if (isset($data->utm_content)) $saved['utm_content'] = $data->utm_content; // соответственно вышеуказанному
        if (isset($data->utm_medium)) $saved['utm_medium'] = $data->utm_medium; // соответственно вышеуказанному
        if (isset($data->utm_campaign)) $saved['utm_campaign'] = $data->utm_campaign; // соответственно вышеуказанному
        if (isset($data->click_id)) $saved['click_id'] = $data->click_id; // соответственно вышеуказанному
        if (isset($data->webmaster_id)) $saved['webmaster_id'] = $data->webmaster_id; // соответственно вышеуказанному
        if (method_exists($this->lidogen, $lidgen_v)) {     //если есть метод индивидуальной проверки для лидогенератора
            $rez = $this->lidogen->$lidgen_v($data);        // вызываем этот метод
            if (isset($rez['status']) && $rez['status'] == 400) { // если он вернул статус и он равен 400
                $saved['sendOK'] = 1;                                 // признак отправки - 1
                $saved['sendStatus'] = 0;                             // статус отправки - 0
                $saved['SendMessage'] = $rez['description'];          // сообщение ошибки - то, что вернула проверка
                $saved['double'] = 1;                                 // дубль=1 по идее надо исправить и поле сделать, которое охначало, что заявка не прошла проверку условий лидогенератора)
                $this->db->insert('queues', $saved);                // запись заявки в БД
                //$this->system->savelog($data->client->passport->series.$data->client->passport->number.'-'.$data->lidgen.'-double',$saved);
                $this->system->_response(array('result' => $rez['result'], 'description' => $rez['description']), $rez['status']); // возвращаем клиенту ответ проверки
            }
        }
        $this->db->insert('queues', $saved); // записываем в БД
        //$this->system->savelog($data->client->passport->series.$data->client->passport->number.'-'.$data->lidgen.'-output',$saved);
        $last_id = $this->db->insert_id(); // получаем id записи
        $rez = array();
        $rez['result'] = '1';   // составляем ответ клиенту
        $rez['id'] = $last_id;
        $rez['description'] = "Request created";
        $rez['uid'] = $saved['transaction_id'];
        $this->system->_response($rez, 200); // отвечаем клиенту о том, что приняли заявку в обработку с таким-то id
    }

    /**
     * Регистрация лидогенератора
     * на вход нужно подать post-запросом json с именем лидогенератора
     * { "name" : "lidogenName"}
     * в ответ функция возвращает json с name, apikey, authkey
     * и, соответственно, добавляет это в базу.
     * @return void
     */
    public function register()
    {
        $this->system->__clearDB();
        //$data = $this->_verify('register');
        $data = json_decode($this->security->xss_clean($this->input->raw_input_stream));
        if (!isset($data->name)) {
            $this->system->_response(array('message' => 'Not parametrs'), 404);
        };
        $apikey = $this->uuid->v4(false); //uniqid();
        $this->db->where('name', $data->name);
        $this->db->from('lidogen');
        if ($this->db->count_all_results() > 0) {
            $this->system->_response(array("message" => "This name already use"), 403);
        } else {
            $rez['name'] = $data->name;
            $rez['api_key'] = $apikey;
            $rez['authkey'] = 'Basic ' . base64_encode($data->name . ":" . $apikey);
            $date = new DateTime();
            $date->add(new DateInterval("P5D"));
            $rez['datetime'] = $date->format('Y-m-d H:i:s');
            $this->db->insert('lidogen', $rez);
            $last_id = $this->db->insert_id();
            $rez['id'] = $last_id;
            $this->system->_response($rez, 200);
        }
    }

    /**
     * Функция получения постбеков от 1с
     * вход напрямую, снаружи
     */
    public function postback_proxy()
    {
        $data_body = $this->security->xss_clean($this->input->raw_input_stream); // получаем тело постбека
        $data_uri = $this->input->get();    //получаем данные, которые передавались get запросом (1с работает через get, поэтомк предыдущая строка не имеет смысла)
        $newDate = new DateTime();             //текущие дата и время
        $fomattedDate = $newDate->format('Y-m-d H:i:s');    // в SQL-формате
        $data_uri['date'] = $fomattedDate;  // добавляем в данные
        $this->db->insert('postback_proxy', $data_uri); // записываем факт прихода постбека с его данными в БД
        $this->postback_add_id = $this->db->insert_id();    // получаем id записи
        $this->postback->postback($data_uri, $this->postback_add_id);    // передаем в функцию обработки постбеков от 1с
        // model/postback_model.php/postback:30
    }

    /*
     * Функция отправки данных в 1с (вызывается Через cron)
     */
    public function queue_send()
    {
        /*        $queueses = $this->db->query("SELECT
                        `id_request`,
                        `ID`,
                        `last_name`,
                        `first_name`,
                        `middle_name`,
                        `period`,
                        `creditproduct`,
                        `interval`,
                        `phone`,
                        `birthday`,
                        `birthplace`,
                        `email`,
                        `amount`,
                        `passport_series`,
                        `passport_number`,
                        `passport_date_of_issue`,
                        `passport_org`,
                        `passport_code`,
                        `registration_country`,
                        `registration_index`,
                        `registration_region`,
                        `registration_city`,
                        `registration_street`,
                        `registration_house`,
                        `registration_building`,
                        `registration_apartment`,
                        `match_addresses`,
                        `residential_country`,
                        `residential_index`,
                        `residential_region`,
                        `residential_city`,
                        `residential_street`,
                        `residential_house`,
                        `residential_building`,
                        `residential_apartment`,
                        `NewLoanFromLK`,
                        `utm_source`,
                        `utm_term`
                    FROM queues
                    where SendOK=0 and (reserved is null or reserved=0)
                    order by id_request
                limit 15")->result();*/
        // получаем не более 15 записей. (подобрать количество в зависимости от того, как долго 1с обрабатывает эти заявки)
        $queueses = $this->db->select('`id_request`,
            `ID`,
            `last_name`,
            `first_name`,
            `middle_name`,
            `period`,
            `creditproduct`,
            `interval`,
            `phone`,
            `birthday`,
            `birthplace`,
            `email`,
            `amount`,
            `passport_series`,
            `passport_number`,
            `passport_date_of_issue`,
            `passport_org`,
            `passport_code`,
            `registration_country`,
            `registration_index`,
            `registration_region`,
            `registration_city`,
            `registration_street`,
            `registration_house`,
            `registration_building`,
            `registration_apartment`,
            `match_addresses`,
            `residential_country`,
            `residential_index`,
            `residential_region`,
            `residential_city`,
            `residential_street`,
            `residential_house`,
            `residential_building`,
            `residential_apartment`,
            `NewLoanFromLK`,
            `utm_source`,
            `utm_term`')->from('queues')
            ->where('SendOK', '0')
            ->group_start()
            ->where(array('reserved' => NULL))
            ->or_where('reserved', '0')
            ->group_end()
            ->order_by('id_request')
            ->limit(15)
            ->get()->result();
        // пробегаемся по списку, резервируя полученные заявки
        foreach ($queueses as $line) {
            //$this->db->query('update queues set reserved="1" where id_request=' . $line->id_request);
            $this->db->set('reserved', "1")->where("id_request", $line->id_request)->update('queues');
        }
        // теперь целенаправленно идем по этому списку, отправляя каждый из них в 1с
        foreach ($queueses as $line) {
            $this->_send21c($line);
        }
    }

    /*
     * Функция отправки заявки в 1с
     */
    private function _send21c($data)
    {
        $req['URL'] = "http://194.67.28.222:136/myMFO_zis/hs/Request/"; //URL api сервера 1с
        if ($data->phone == '89999999999') $req['URL'] = "http://194.67.28.222:136/mymfo_test/hs/Request/"; //URL api тестового сервера 1с
        $req['login'] = "API";  //логин и пароль для 1с. [rem] по идее вынести в отдельный файл конфигурации
        $req['password'] = "123321";
        $queue_id = $data->id_request;  //запоминаем id заявки в отдельной переменной
        unset($data->id_request);   //уничтожаем эту переменную в массиве
        // формируем на основании предоставленной логики кредитные продукты из заявки
        if ($data->amount > 30000) {
            unset($data->period);
        } else {
            unset($data->interval);
        }
        $req['request'] = json_encode($data, JSON_UNESCAPED_UNICODE); // генерируем JSON из данных для заявки
        $rez = $this->system->curl_request($req);   // отправляем в 1с
        $sendOK = 1;    // по умолчанию должно быть отправлено
        if ($rez['httpCode'] != 200) {  //если ответ 1с не 200 то:
            $sendOK = 0;    // отправка не удалась
            if ($rez['httpCode'] == 0 || $rez['httpCode'] == 100) { //если код 0 или 100
                $code = $rez['httpCode'];
                $error = "Not answer server 1c";
            } else {    //иначе
                $code = $rez['httpCode'];
                $error = 'Not Connect';
            }
            // обновляем запись в БД с данными ошибки
//            $this->db->query("update `queues` set `SendOK`='0', `sendStatus`='" . $code
//                . "', `SendMessage`='" . addslashes($error)
//                . "' where `id_request`=" . $queue_id . " and (`sendStatus` is null or `sendStatus`<>1)");
            $this->db->set('SendOK', 0)
                ->set('sendStatus', $code)
                ->set('SendMessage', addslashes($error))
                ->where('id_request', $queue_id)
                ->group_start()
                ->where(array('sendStatus' => NULL))
                ->or_where('sendStatus<>', 1)
                ->group_end()
                ->update('queues');
        }
        if (!$rez['result']) {
            $sendOK = 0;
            $SendError = '99';
            $SendMessage = "Not answer server";
//            $this->db->query("update `queues` set `SendOK`='" . $sendOK
//                . "', `sendStatus`='" . $SendError . "', `SendMessage`='" . addslashes($SendMessage)
//                . "' where `id_request`=" . $queue_id . " and (`sendStatus` is null or `sendStatus`<>1)");
            $this->db->set('SendOK', $sendOK)
                ->set('sendStatus', $SendError)
                ->set('SendMessage', addslashes($SendMessage))
                ->where('id_request', $queue_id)
                ->group_start()
                ->where(array('sendStatus' => NULL))
                ->or_where('sendStatus<>', 1)
                ->group_end()
                ->update('queues');

        } else {
            $id1c = "";
            $res = json_decode($rez['result']);
            $sendOK = 1;
            if (!isset($res->result) or $res->result == 0) {
                $SendError = 0;
                if ($rez['httpCode'] != 200) {
                    $SendError = $rez['httpCode'];
                    $sendOK = 0;
                }
                if ($rez['httpCode'] == 404) {
                    $desc = 'Server url not found';

                }
                $SendMessage = (isset($res->description)) ? $res->description : $desc;
            } else {
                $SendError = $res->result;
                $SendMessage = $res->description;
                $id1c = $res->IDDeal;
            }
//            $this->db->query("update `queues` set `SendOK`='" . $sendOK
//                . "', `sendStatus`='" . $SendError
//                . "', `SendMessage`='" . addslashes($SendMessage)
//                . "', `id1c`='" . $id1c
//                . "' where `id_request`=" . $queue_id . " and (`sendStatus` is null or `sendStatus`<>1)");
            $this->db->set('sendStatus', $SendError)
                ->set('SendOK', $sendOK)
                ->set('SendMessage', addslashes($SendMessage))
                ->set('id1c', $id1c)
                ->where('id_request', $queue_id)
                ->group_start()
                ->where(array('sendStatus' => NULL))
                ->or_where('sendStatus<>', '1')
                ->group_end()
                ->update('queues');
            if ($sendOK == "0"
                && addslashes($SendMessage) == "Уже имеются открытые заявки на рассмотрении."
                && $SendError == "0")
            {
                $url= "https://api.celfin.ru/postback/?token=".$data->id_lidogen."&goal_id=".$data->id_lidogen."&transaction_id=".$data->utm_term."&uid=".$data->utm_term."&status=rejected&amount=".$data->amount."&comment=".addslashes($SendMessage);
                $this->system->fast_request($url);
            }
        }
        //$this->db->query("update `queues` set `reserved`='0'  where `id_request`=" . $queue_id);
        $this->db->set('reserved', 0)
            ->where("id_request", $queue_id)
            ->update('queues');
    }

    public function testq()
    {
        echo '<pre>';
        echo 'directory: '.$this->router->directory."\n";
        echo 'class: '.$this->router->class."\n";
        echo 'method: '.$this->router->method."\n";
    }
}


/* End of file Api.php */
/* Location: ./application/controllers/api/Api.php */