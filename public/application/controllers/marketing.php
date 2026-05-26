<?php

/**
 * Public marketing homepage (hosted SaaS mode).
 */
class Marketing extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('Platform_settings_model');
    }

    function index()
    {
        if (!is_hosted_prod_service()) {
            redirect('auth/login');
            return;
        }

        $data['pricing_tiers'] = $this->Platform_settings_model->get_pricing_tiers(true);
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['hero_title'] = $this->Platform_settings_model->get(
            'marketing_hero_title',
            'Hotel management software that grows with you'
        );
        $data['hero_subtitle'] = $this->Platform_settings_model->get(
            'marketing_hero_subtitle',
            'Reservations, channel manager, payments, and revenue tools in one platform.'
        );
        $data['css_files'] = array(base_url() . auto_version('css/marketing/home.css'));
        $data['js_files'] = array(base_url() . auto_version('js/marketing/home.js'));

        $this->load->view('marketing/home', $data);
    }
}
