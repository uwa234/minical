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
            'Reservations, channel manager, payments, revenue tools, and integrations — one cloud platform for your hotel.'
        );
        $data['platform_feature_groups'] = marketing_platform_feature_groups();
        $data['saas_highlights'] = marketing_saas_highlights();
        $data['platform_extensions'] = marketing_extensions_from_modules($this->all_active_modules);
        $brand = seo_brand_name();
        $meta_description = trim($data['hero_subtitle']);
        if ($meta_description === '') {
            $meta_description = seo_default_description();
        } else {
            $meta_description = $brand . ' — ' . $meta_description;
        }
        $seo = seo_build_page(array(
            'title' => $brand . ' — Hotel property management software',
            'description' => $meta_description,
            'canonical' => seo_absolute_url(),
            'json_ld' => array(
                seo_json_ld_organization(),
                seo_json_ld_website(),
                seo_json_ld_software_application(),
            ),
        ));
        $data = array_merge($data, marketing_page_layout($seo));

        $this->load->view('marketing/home', $data);
    }
}
