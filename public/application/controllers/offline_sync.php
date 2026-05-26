<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Offline_sync extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('language');
        $language = $this->session->userdata('language');
        $this->lang->load('menu', $language ? $language : 'english');
        $this->load->vars(array('menu_on' => true));
    }

    public function index()
    {
        $data = array(
            'selected_menu' => 'offline_sync',
            'css_files' => array(
                base_url() . auto_version('css/offline/offline-sync-status.css'),
            ),
            'js_files' => array(
                base_url() . auto_version('js/offline/offline-sync-status.js'),
            ),
            'main_content' => 'offline_sync/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }
}
