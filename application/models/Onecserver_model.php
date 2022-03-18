<?php

/**
 * 1С Model Class
 *
 *
 * @category 1С Model
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

class Onecserver_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    function send_data($data) {
        $rez = $this->system->curl_request($data);
    }

    function adaptation($data) {

    }

    private function verify_data($data)
    {
        if (isset($data->client->addresses->registration->index) && empty($data->client->addresses->registration->index)) $data->client->addresses->registration->index = "000000";
        if (isset($data->client->addresses->residential->index) && empty($data->client->addresses->residential->index)) $data->client->addresses->residential->index = "000000";
        $r = $this->verify->verify_lines($data);
        if (count($r) > 0) {
            $t = json_encode($r, JSON_UNESCAPED_UNICODE);
            $this->system->_response(array("messages" => $r), 400);
        }
        $lidgen_v = $data->lidgen . '_verify';
        if (method_exists($this->lidogen, $lidgen_v)) $rez = $this->lidogen->$lidgen_v($data);
        if (isset($rez['status']) && $rez['status'] == 400) {
            $this->system->_response(array('result' => $rez['result'], 'description' => $rez['description']), $rez['status']);
        }
        $onecapi = $this->system->generate21c($data);
        $req['URL'] = "http://194.67.28.222:136/myMFO_zis/hs/Request/";
        $req['login'] = "API";
        $req['password'] = "123321";
        $req['request'] = json_encode($onecapi, JSON_UNESCAPED_UNICODE);
        $rez = $this->system->curl_request($req);
        $sendOK = 1;
        if ($rez['httpCode'] != 200) {
            $this->system->_response(array('result' => 0, 'description' => '1c Server Error', '1c server answer' => $rez), 500);
        }
        if (!$rez['result']) {
            $onecapi['sendOK'] = 0;
            $this->system->_response(array('result' => 0, 'description' => 'API Error', '1c server answer' => $rez), 500);
        }
        $res = json_decode($rez['result']);
        $saved = $onecapi;
        $saved['id_lidogen'] = $data->id_lidgen;
        $rez = array();
        $newDate = new DateTime();
        $fomattedDate = $newDate->format('Y-m-d H:i:s');
        $saved["date_created"] = $fomattedDate;
        $saved['sendOK'] = $sendOK;
        if ($res->result == 0) {
            $data->error = $res->description;
            $saved['result_request'] = $res->result;
            $saved['error_request'] = $res->description;
            $this->db->insert('request_data', $saved);
            $this->system->_response($res, 200);
        } else {
            $saved['result_request'] = $res->result;
            $saved['error_request'] = $res->description;
            $saved["IDDeal"] = $res->IDDeal;
            $saved["IDClient"] = $res->IDClient;
            $pb = $this->db->query('select * from postback_proxy where `transaction_id`="' . $saved['utm_term'] . '"')->row();
            if (isset($pb->uid)) {
                $saved['uid'] = $pb->uid;
                $saved['transaction_id'] = $pb->transaction_id;
                $saved['last_status'] = $pb->status;
            }
            $last_id = $this->saveRequest($saved);
            $rez['result'] = '1';
            $rez['id'] = $last_id;
            $rez['description'] = "Request created";
            $rez['uid'] = $pb->uid;
            $rez['transaction_id'] = $pb->transaction_id;
            $this->system->_response($rez, 200);
        }
    }
}

/* End of file Onecserver_model.php */
/* Location: ./application/models/Onecserver_model.php */