<?php

class Profile extends CI_Controller
{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if(!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }
        $site = (object) [
            'name'=>'Целевые Финансы',
            'homePage'=>'statistics',
            'thisPage'=>'statistics'
        ];
        $user=(object)[
            'name'=>'Администратор',
            'avatar'=>'/img/user2-160x160.jpg'
        ];
        $data['data'] = (object) ['site'=>$site,'user'=>$user];
        $this->load->view('templates/header',$data);
        $this->load->view('templates/navbar',$data);
        $this->load->view('templates/sidebar',$data);
        $this->load->view('profile',$data);
        $this->load->view('templates/sidebar-right',$data);
        $this->load->view('templates/footer',$data);
    }
}