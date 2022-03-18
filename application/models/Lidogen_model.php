<?php

/**
 * Lidogenerator Model Class
 *
 *
 * @category Lidogenerator Model
 * @author Nikolaev Alexey
 * @link http://midet.ru/
 */

/**
 * Mini manual
 *
 * function lidogenerator_name - служит для отправки лидогенератору постбека
 * function lidogenerator_name_verify - какие либо дополнительные проверки для лидогенератора перед отправкой постбека.
 */

class Lidogen_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  object $data
     * @return array|string
     */
    function monetkin_verify(array $data)
    {
        $error = '';
//        $query = 'SELECT COUNT(*) AS col FROM queues rd WHERE rd.utm_source="monetkin" AND rd.passport_series="'
//            . $data->client->passport->series
//            . '" AND rd.passport_number="'
//            . $data->client->passport->number
//            . '" AND TIMESTAMPDIFF(day, rd.date_created, NOW())<14 AND (rd.double<>1 OR ISNULL(rd.double))';
        $col = $this->db->from('queues')
            ->where('utm_source', 'monetkin')
            ->where('passport_series',$data->client->passport->series)
            ->where('passport_number',$data->client->passport->number)
            ->where("TIMESTAMPDIFF(day, date_created, NOW())<14")
            ->group_start()
            ->where(array('double' => NULL))
            ->or_where('double<>', '1')
            ->group_end()
            ->count_all_results();
        if ($col > 0) {
            $error = array(
                'status' => 400,
                'result' => "0",
                "description" => "You have had such a client during the last two weeks"
            );
        }
        return $error;
    }

    /**
     * @param $data
     * @return array
     */
    function monetkin($data)
    {
        $url = "https://api.zaem911.ru/v3/webhook/credit911/?uid="
            . $data['transaction_id']
            . '&transaction_id=' . $data['transaction_id']
            . '&status=' . $data['status'];
        if ($data['status'] == "pending") {
//            $rez = $this->db->query(
//                "select
//                    count(*) as count
//                from
//                     postback_proxy
//                where
//                      transaction_id='" . $data['transaction_id'] . "'
//                      and status='" . $data['status'] . "'")->result();
            $count=$this->db->from('postback_proxy')
                ->where('transaction_id',$data['transaction_id'])
                ->where('status',$data['status'])
                ->count_all_results();
            //if ($count > 0) return true;

        }
        $response = $this->system->fast_request($url);
        return $response;
    }

    /**
     * @param array $data
     * @return array
     * data[token] - id лидогенератора
     * data[goal_id] - id лидогенератора
     * data[transaction_id] - внутренний uuid заявки
     * data[uid] - uid из 1с (не используется)
     * data[status] - статус заявки. pending - получено, rejected - отклонена, approved - одобрено, issued - выдана
     * data[amount] - сумма. При pending - в 99% пустая
     * data[comment] - при pending - new, в остальных случаях -update
     * $data['date'] - datetime получения постбека
     */
    function site($data)
    {
        $this->system->savelog('site_39', $data);
        //$rez = $this->db->query('select * from queues where transaction_id="' . $data['transaction_id'] . '"')->row();
        $rez=$this->db->get_where('queues',array('transaction_id' => $data['transaction_id']))->row();
        if (!is_null($rez->utm_source_original) and ($rez->utm_source_original != '')) {
            $flidogen = strtolower($rez->utm_source_original);
            if (method_exists($this, $flidogen)) {
                $response = $this->$flidogen($data);
            } else {
                $response = array('code'=>200,'response'=>array('Message' => 'lidogenetator ' . $rez->utm_source_original . ' not configured'));
            }
        } else {
            $response = array('code'=>200,'response'=>array('Message' => 'Site postback success'));
        }
        return $response;
    }


    /**
     * @param array $data
     * @return array
     * data[token] - id лидогенератора
     * data[goal_id] - id лидогенератора
     * data[transaction_id] - внутренний uuid заявки
     * data[uid] - uid из 1с (не используется)
     * data[status] - статус заявки. pending - получено, rejected - отклонена, approved - одобрено, issued - выдана
     * data[amount] - сумма. При pending - в 99% пустая
     * data[comment] - при pending - new, в остальных случаях -update
     * $data['date'] - datetime получения постбека
     * db_data - данные о заявке из базы данных
     */
    function leadgid($data)
    {
        $url = false;
        $response = '{}';
//        $db_data = $this->db->query('select * from queues where transaction_id="' . $data['transaction_id'] . '"')->row();
        $db_data=$this->db->get_where('queues',array('transaction_id' => $data['transaction_id']))->row();
        $transaction_id = $db_data->click_id;
        $offer_id = 5136;
        $adv_sub = $db_data->id_request;
        $summ=$data['amount'];
        if($summ<11000) {
            $goal_id=5183;
        } else {
            $goal_id=3990;
        }
        if ($data['status'] == "pending") {
            $url = sprintf("https://go.leadgid.ru/aff_lsr?offer_id=%s&adv_sub=%s&transaction_id=%s&format=json", $offer_id, $adv_sub, $transaction_id);
        } elseif ($data['status'] == "issued") {
            $url = sprintf("https://go.leadgid.ru/aff_goal?a=lsr&goal_id=%s&transaction_id=%s&adv_sub=%s&format=json",$goal_id, $transaction_id, $adv_sub);
        } else {
            $response = array('code'=>200,'response'=>array("Message"=>"this status is not processed by LeadGid lead generator postback"));
        }
        if ($url !== false) {
            $response = $this->system->fast_request($url);
        }
        if ($url !== false) {
            $this->system->savelog('39_leadgid_postback', array('url' => $url, 'response' => $response));
        }

        return $response;
    }

    /**
     * @param array $data
     * @return array
     */
    function leads($data) {
        /*TOKEN - личный токен доступа к API.
         * GOAL_ID - ID цели в системе Leads.su.
         * TRANSACTION_ID - идентификатор транзакции (конкретного пользователя), который был передан на посадочную страницу в параметре transaction_id. TRANSACTION_ID нужно обязательно сохранять в COOKIE или в сессии на сервере на время указанное в договоре.
         * ADV_SUB -  уникальный идентификатор (лида, конверсии, зарегистрированного клиента) в CRM системе рекламодателя (не длиннее 32 символов)
         * STATUS - статус заявки, один из перечисленных ниже (если не передавать - конверсия создастся со статусом pending)
         * - rejected - отклонена
         * - pending - в обработке
         * - approved - принята
         * COMMENT - комментарий к заявке
         */
        $db_data=$this->db->get_where('queues',array('transaction_id' => $data['transaction_id']))->row();
        $token='257e6dff0b24e8de3fb67e42297e498f';
        $transaction_id=$db_data->click_id;
        $adv_sub=$db_data->id_request;
        $status=$data['status'];
        $r=true;
        if ($status=='pending') {
            $goal_id='0';
            $status_leads='approved';
        } elseif ($status=='issued' || $status=='rejected') {
            $goal_id='2798';
            $status_leads=($status=='issued')?'approved':'rejected';
        } else {
            $r=false;
        }
        if ($r!==false) {
            $url = sprintf("http://api.leads.su/advertiser/conversion/createUpdate?token=%s&goal_id=%s&transaction_id=%s&adv_sub=%s&status=%s", $token, $goal_id, $transaction_id, $adv_sub, $status_leads);
            $response = $this->system->fast_request($url);
        } else {
            $response = array('code'=>200,'response'=>'{"Message": "this status is not processed by Leads lead generator postback"}');
        }
        return $response;
    }

    /**
     * @param array $data
     * @return array
     */
    function admitad($data) {
        $db_data=$this->db->get_where('queues',array('transaction_id' => $data['transaction_id']))->row();
        $campaign_code="8445484d70";                                                                        //campaign_code
        $postback_key="4f1bEC19BdC35b952973d06A2969D34d";                                                   //postback_key
        $admitad_id=$db_data->utm_campaign;                                                                 //uid
        $order_id=$db_data->id_request;                                                                     //order_id
        $action_code='0';                                                                                    //action_code
        $tariff_code='1';                                                                                    //tariff_code
        $currency_code='RUB';                                                                               //currency_code
        $summ=$data['amount'];
        if($data['status']=='pending') {
            $payment_type='sale';
            $action_code="1";
        }
        if($data['status']=='issued') {
            $payment_type='sale';
            if($summ<11000) {
                $action_code="1";
            } else {
                $action_code="2";
            }
        }

        if ($data['status']=='pending' || $data['status']=='issued') {
            $url='https://ad.admitad.com/r?campaign_code='.$campaign_code
                .'&postback=1'
                .'&postback_key='.$postback_key
                .'&action_code='.$action_code
                .'&uid='.$admitad_id
                .'&order_id='.$order_id
                .'&tariff_code='.$tariff_code
                .'&currency_code='.$currency_code
                .'&payment_type='.$payment_type;
        } else {
            $url=false;
        }


        if ($url !== false) {
            $response = $this->system->fast_request($url);
        } else {
            $response = array('code'=>200,'response'=>'{"Message": "this status is not processed by Leads lead generator postback"}');
        }
        return $response;
    }

    function creditcall($data) {
        $response = array('code'=>200,'response'=>array('Message' => 'Site postback success'));
        return $response;
    }
}
/* End of file Lidogen_model.php */
/* Location: ./application/models/Lidogen_model.php */