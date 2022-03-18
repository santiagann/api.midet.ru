<?php

/**
 * Postback Model Class
 *
 *
 * @category Postback Model
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

class Postback_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * первоначальная обработка постбека, пришедшего от 1с
     * @param array $data
     * data[token] - id лидогенератора
     * data[goal_id] - id лидогенератора
     * data[transaction_id] - внутренний uuid заявки
     * data[uid] - uid из 1с (не используется)
     * data[status] - статус заявки. pending - получено, rejected - отклонена, approved - одобрено, issued - выдана
     * data[amount] - сумма. При pending - в 99% пустая
     * data[comment] - при pending - new, в остальных случаях -update
     */
    function postback($data, $postback_add_id)
    {
        $response = array();
        $token = $data['token'];
        $query = $this->db->get_where('lidogen', array('id' => $token))->row();
        if (!isset($query->name)) {
            $response = array('code' => 200, 'response' => array("Message" => "this Lidogenerator not present"));
        }
        $lidgen = $query->name; // получаем имя лидогенератора
        if (method_exists($this->lidogen, $lidgen)) {   // проверяем наличие функции лидогенератора, если есть - то отправляем в неё $data
            $this->updateRequest($data);
            $response = $this->lidogen->$lidgen($data);
            if ($response === true) {
                return true;
            } else {
                if ($response['code'] == '200') {
                    $this->system->_response($response['response'], 200);
                } else {
                    //$this->db->query('UPDATE postback_proxy SET `resend`="1",`http_code`="' . $response['code'] . '" WHERE  `id`=' . $this->$postback_add_id)->result();
                    $this->db->set('resend', "1")
                        ->set('$response', $response['code'])
                        ->where("id", $this->$postback_add_id)
                        ->update('postback_proxy');
                    $this->system->_response($response['response'], 500);
                }
            }

        } else {
            $response = array('code' => 200, 'response' => array("Message" => "this Lidogenerator not present"));
        }
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    function updateRequest($data)
    {
        $this->db->set('last_state', $data['status'])->where("utm_term", $data['transaction_id'])->update('queues');
        return true;
    }
}

/* End of file Postback_model.php */
/* Location: ./application/models/Postback_model.php */