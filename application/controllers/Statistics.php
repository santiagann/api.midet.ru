<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Ion_auth $ion_auth
 * @property system_model $system
 */
class Statistics extends CI_Controller
{
    var $from;
    var $to;

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *        http://example.com/index.php/welcome
     *    - or -
     *        http://example.com/index.php/welcome/index
     *    - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('system_model', 'system');
    }

    public function index()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }
        //print_r($this->getCont());
        $stat_request = $this->get_statistics();
        $site = (object)[
            'name' => 'Целевые Финансы',
            'homePage' => 'statistics',
            'thisPage' => 'statistics'
        ];
        $user = (object)[
            'name' => 'Администратор',
            'avatar' => '/img/user2-160x160.jpg'
        ];
        $data['data'] = (object)['site' => $site, 'user' => $user, 'statReq' => $stat_request];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/navbar', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('statistics', $data);
        $this->load->view('templates/sidebar-right', $data);
        $this->load->view('templates/footer', $data);
    }

    function get_statistics($days = 30)
    {
        $nowdate = date('Y-m-d');
        $date = date('Y-m-d', strtotime($nowdate . '-30 days'));
        $request['all'] = $this->db->from('queues')->where('sendStatus', '1')->count_all_results();
        $request['rejected'] = $this->db->from('queues')->where('sendStatus', '1')->where('last_state', 'rejected')->count_all_results();
        $request['issued'] = $this->db->from('queues')->where('sendStatus', '1')->where('last_state', 'issued')->count_all_results();
        $request['approved'] = $this->db->from('queues')->where('sendStatus', '1')->where('last_state', 'approved')->count_all_results();
        return (object)$request;
    }

    public function getdata()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->system->_response(array('code' => 401, 'messages' => 'Not Authorized'), 401);
        }
        $data = json_decode($this->security->xss_clean($this->input->raw_input_stream));
        if (!isset($data->from) or !isset($data->to)) {
            $this->system->_response(array('code' => 400, 'messages' => 'Not parametrs'), 400);
        }
        $fromArr = explode('.', $data->from);
        $toArr = explode('.', $data->to);
        $error = array();
        if (count($fromArr) != 3 or !checkdate($fromArr[1], $fromArr[0], $fromArr[2])) $error[] = 'Wrong date `from`';
        if (count($toArr) != 3 or !checkdate($toArr[1], $toArr[0], $toArr[2])) $error[] = 'Wrong date `to`';
        if (count($error) != 0) {
            $this->system->_response(array('code' => 400, 'messages' => $error), 400);
        }
        $this->from = $datas['from'] = $this->system->dateConvert1c($data->from);
        $this->to = $datas['to'] = $this->system->dateConvert1c($data->to);
        $newDate = new DateTime($datas['to']);
        $newDate->add(new DateInterval('P1D'));
        $datas['to'] = $newDate->format('Y-m-d');
        $res=$this->__getStat($datas['from'], $datas['to']);
        $this->system->_response(array('messages'=>'file done','file'=>$res),200);
    }

    private function __getStat($from, $to)
    {
        $count = $this->db
            ->from('queues')
            ->where('date_created>', $from)
            ->where('date_created<', $to)
            ->order_by('id_request', 'DESC')
            ->count_all_results();
        for ($i = 0; $i < $count; $i = $i + 100) {
            $limit_rate = 100;
            $res = $this->db
                ->select('
                id_request
                ,date_created
                ,last_name
                ,first_name
                ,middle_name
                ,creditproduct
                ,phone
                ,birthday
                ,amount
                ,passport_series
                ,passport_number
                ,passport_date_of_issue
                ,passport_code
                ,utm_source
                ,utm_source_original
                ,utm_content
                ,utm_medium
                ,utm_campaign
                ,click_id
                ,webmaster_id
                ,utm_term
                ,transaction_id
                ,sendStatus
                ,SendMessage
                ,id1c')
                ->where('date_created>', $from)
                ->where('date_created<', $to)
                ->order_by('id_request', 'DESC')
                ->limit($limit_rate,$i)
                ->get('queues')
                ->result();
            $filenameArr = $this->savefile($res, $i);
            $res = '';
        }
        $zip = new ZipArchive();
        $filenameZ = $filenameArr['uri'].$filenameArr['filename'] . ".zip";
        $zip->open($filenameZ, ZipArchive::CREATE);
        $zip->addFile($filenameArr['uri'].$filenameArr['filename'], $filenameArr['filename']);
        $zip->close();
        unlink($filenameArr['uri'].$filenameArr['filename']);
        return array('fullfile'=>$filenameZ,'filename'=>$filenameArr['filename'].'.zip');
    }

    private function savefile($res, $line)
    {
        $header = array(
            'id_request',
            'date_created',
            'last_name',
            'first_name',
            'middle_name',
            'creditproduct',
            'phone',
            'birthday',
            'amount',
            'passport_series',
            'passport_number',
            'passport_date_of_issue',
            'passport_code',
            'utm_source',
            'utm_source_original',
            'utm_content',
            'utm_medium',
            'utm_campaign',
            'click_id',
            'webmaster_id',
            'utm_term',
            'transaction_id',
            'SendStatus',
            'SendMessage',
            'id1c'
        );
        $uri = 'download/';
        $date=date('Y-m-d');
        $uri=$uri.$date.'/';
        $filename = $this->from . '---' . $this->to . '.csv';
        $full_filename = $uri . $filename;
        if (!is_dir('download/')) {
            mkdir('download/');
        }
        if (!is_dir($uri)) {
            mkdir($uri);
        }
        if ($line == 0) {
            $handle = fopen($full_filename, 'w');
            fputcsv($handle, $header, ";");
        } else {
            $handle = fopen($full_filename, 'a');
        }
        foreach ($res as $line) {
            fputcsv($handle, $this->iconv_array((array)$line), ";");
        }
        fclose($handle);
        return array('uri'=>$uri,'filename'=>$filename);
    }

    private function iconv_array($array)
    {
        foreach (array_keys($array) as $key) {
            $array[$key] = iconv('UTF-8', 'windows-1251', $array[$key]);
        }
        return $array;
    }

    public function filelist() {
        echo '<pre>';
        echo 'directory: '.$this->router->directory."\n";
        echo 'class: '.$this->router->class."\n";
        echo 'method: '.$this->router->method."\n";
    }

    private function getCont() {
        return array('directory'=>$this->router->directory,'class'=>$this->router->class,'method'=>$this->router->method);
    }
}
