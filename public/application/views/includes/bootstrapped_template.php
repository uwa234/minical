<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<?php
 $phptoggle = '';
 $classtoggle ='';
 $phptoggle = isset($_COOKIE['sidebartogglevalue']) ? $_COOKIE['sidebartogglevalue'] : true;
 
 if(isset($phptoggle) &&  $phptoggle === "false"){
 	$classtoggle = 'closed-sidebar-mobile closed-sidebar';
 }
?>

<?php
$protocol = $this->config->item('server_protocol');
$auth_shell_paths = array('/auth/login', '/auth/register', '/auth/register_success', '/auth/forgot_password');
$current_path = parse_url(current_url(), PHP_URL_PATH);
$is_auth_login = false;
foreach ($auth_shell_paths as $auth_path) {
    if ($current_path === $auth_path || str_ends_with($current_path, '/public' . $auth_path)) {
        $is_auth_login = true;
        break;
    }
}
$body_classes = 'theme-' . (isset($this->company_ui_theme) ? $this->company_ui_theme : 0);
if ($is_auth_login) {
    $body_classes .= ' auth-login-page';
}
?>
<body class="<?php echo $body_classes; ?>">
	<?php
	$app_container_classes = 'app-container app-theme-white';
	if ($is_auth_login) {
		$app_container_classes .= ' auth-login-container';
	} else {
		$app_container_classes .= ' body-tabs-shadow fixed-header fixed-sidebar ' . $classtoggle;
	}
	?>
	<div class="<?php echo trim($app_container_classes); ?>">

		<?php 

		//Generate css and js file arrays if they don't already exist.
		//This prevents clobbering of the variables caused by multiple loading of this file (ie. iframes).
		if (!isset($css_files))
			$css_files = array();
		if (!isset($js_files))
			$js_files = array();
		
		//Load second submenu navigation css if required (load before menu css and js files for proper order) array_unshift prepends
		if (isset($secondary_submenu))
		{
			array_unshift($css_files, base_url() . auto_version('css/secondary_submenu.css'));
		}
		
		//Load second submenu navigation css if required (load before menu css and js files for proper order) array_unshift prepends
		if (isset($tertiary_submenu))
		{
			array_unshift($css_files, base_url() . auto_version('css/tertiary_submenu.css'));
		}
		
		//Load submenu navigation css if required (load before menu css and js files for proper order) array_unshift prepends
		if (isset($submenu))
		{
			array_unshift($css_files, base_url() . auto_version('css/submenu.css'));
		}

		array_unshift($js_files, base_url() . auto_version('js/template.js'));
		
		//Load menu css and js files if required
		if (isset($menu_on))
		{
			if ($menu_on){
				array_unshift($css_files, base_url() . auto_version('css/menu.css'));
				array_unshift($js_files, base_url() . auto_version('js/menu.js'));

				// add fancybox
				array_unshift($js_files, base_url() . 'js/fancybox/helpers/jquery.fancybox-media.js?v=1.0.6');
				array_unshift($js_files, base_url() . 'js/fancybox/jquery.fancybox.pack.js?v=2.1.5');
				array_unshift($css_files, base_url() . 'js/fancybox/jquery.fancybox.css?v=2.1.5');
				
			}
		}
		
		if(isset($this->company_ui_theme) && $this->company_ui_theme != THEME_DEFAULT) {
            $css_files[] = base_url() . auto_version('css/themes/theme-'.$this->company_ui_theme.'.css');
		}
		
		//Load header
		$files = get_asstes_files($this->module_assets_files, $this->router->fetch_module(), $this->controller_name, $this->function_name);
		
		foreach ($files['css_files'] as $key => $value) {
			$css_files[] = $value;
		}

		foreach ($files['js_files'] as $key => $value) {
			$js_files[] = $value;
		}
		
		$data = array ( 'css_files' => $css_files, 'js_files' => $js_files );
		$this->load->view('includes/bootstrapped_header', $data);
		?>

        <input type="hidden" name="project_url" id="project_url" value="<?php echo base_url(); ?>">

		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MLXS7DC"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->



		<?php if (validation_errors() != "" || isset($recaptcha_required) || isset($captcha_validate) ):
		?>
				<div class="container-fluid">
					<div
						class="alert alert-danger alert-dismissible" role="alert"
						style="
							position:fixed; 
							z-index:1000; 
							top:10%; 
							left:50%;
							width: 70%;
							margin-left: -35%;
							"
					>
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					  	<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
					  	<span class="sr-only"><?php echo l('Error'); ?>:</span>
	  					<strong><?php echo l('Please correct the below error(s)'); ?>:</strong>
                        <?php if(isset($recaptcha_required) && $recaptcha_required) {
                            echo "<br>".$recaptcha_required;
                        }elseif(isset($captcha_validate) && $captcha_validate){
                             echo "<br>".$captcha_validate;
                        }else{
                           echo validation_errors(); 
                        }  ?>
					</div>
				</div>
		<?php
			endif;
        $flash_warning_message = $this->session->flashdata('flash_warning_message');
        $message = $this->session->flashdata('message');
        $flash_type = $this->session->flashdata('flash_type');
        if ($flash_warning_message || $message):
		?>
				<div class="container-fluid">
					<div
						class="alert alert-<?=$flash_type && $flash_type == 'success' ? "success alert-dismissible " : ( $flash_type == 'danger' ? 'danger' : 'warning alert-dismissible')?>" role="alert"
						style="
							position:fixed; 
							z-index:1000; 
							top:10%; 
							left:50%;
							width: 70%;
							margin-left: -35%;
							"
					>
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					  	<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
	  					<?php echo $flash_warning_message ? '<span class="sr-only">Warning:</span>'.$flash_warning_message : ""; ?>
                        <?php echo $flash_warning_message && $message ? "<br/>".$message : $message; ?>
					</div>
				</div>
		<?php
			endif;
		?>

        <?php if (function_exists('platform_admin_is_impersonating') && platform_admin_is_impersonating()) {
            $keys = platform_admin_impersonation_session_keys();
            $impersonating_name = $this->session->userdata($keys['company_name']);
            ?>
            <div class="pa-impersonation-banner" role="status" style="position:fixed;top:0;left:0;right:0;z-index:2000;display:flex;align-items:center;justify-content:center;gap:16px;padding:10px 16px;background:#7c2d12;color:#fff;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,.15);">
                <span>
                    Viewing as tenant:
                    <strong><?php echo htmlspecialchars((string) $impersonating_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
                <a href="<?php echo base_url('admin/stop_impersonation'); ?>" style="color:#fff;font-weight:600;text-decoration:underline;">Exit to Platform Admin</a>
            </div>
            <style>.app-container.fixed-header{padding-top:44px !important;}</style>
        <?php } ?>

        <?php
        $platform_announcement = function_exists('get_active_platform_announcement') ? get_active_platform_announcement() : null;
        if ($platform_announcement && !$is_auth_login) {
            $ann_id = (int) $platform_announcement['id'];
            $ann_severity = ($platform_announcement['severity'] === 'warning') ? 'warning' : 'info';
            $ann_bg = ($ann_severity === 'warning') ? '#92400e' : '#1e40af';
            ?>
            <div class="pa-platform-announcement" id="pa-platform-announcement-<?php echo $ann_id; ?>" role="status"
                 data-announcement-id="<?php echo $ann_id; ?>"
                 data-dismissible="<?php echo !empty($platform_announcement['dismissible']) ? '1' : '0'; ?>"
                 style="position:fixed;top:0;left:0;right:0;z-index:1999;display:flex;align-items:center;justify-content:center;gap:12px;padding:10px 48px 10px 16px;background:<?php echo $ann_bg; ?>;color:#fff;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                <span>
                    <strong><?php echo htmlspecialchars($platform_announcement['title'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                    <?php echo htmlspecialchars($platform_announcement['body'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php if (!empty($platform_announcement['dismissible'])) { ?>
                    <button type="button" class="pa-platform-announcement-dismiss" aria-label="Dismiss announcement"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:transparent;border:0;color:#fff;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
                <?php } ?>
            </div>
            <script>
            (function () {
                var banner = document.getElementById('pa-platform-announcement-<?php echo $ann_id; ?>');
                if (!banner) { return; }
                var topPad = banner.offsetHeight;
                var container = document.querySelector('.app-container.fixed-header');
                if (container) { container.style.paddingTop = topPad + 'px'; }
                var btn = banner.querySelector('.pa-platform-announcement-dismiss');
                if (btn) {
                    btn.addEventListener('click', function () {
                        document.cookie = 'pa_ann_dismiss_<?php echo $ann_id; ?>=1; path=/; max-age=2592000; SameSite=Lax';
                        banner.parentNode.removeChild(banner);
                        if (container) { container.style.paddingTop = ''; }
                    });
                }
            })();
            </script>
        <?php } ?>

			<?php
			//Load Menu
			
			$company_id = $this->session->userdata('current_company_id');
           
			$data['my_companies'] = $this->Company_model->get_companies($this->user_id);

			$language = $this->session->userdata('language');
			$this->lang->load('menu', $language);
			
			if(current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/register' && current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/login' && current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/forgot_password'){
				// $this->load->view('includes/bootstrapped_menu', $data);
				
			}
			
			?>
			<div class="wrapper clearfix">


		<?php if ($is_auth_login) { ?>
			<div class="auth-login-shell">
			<?php $this->load->view($main_content); ?>

			</div>
		<?php } else { ?>

		
				<div class="app-main">
					<div class="app-header__mobile-menu">
					<div>
                    	<button type="button" id="hamburger-01" class="hamburger hamburger--elastic mobile-toggle-nav">
                        <i class="fa fa-bars" style="font-size: 26px;color: #3f6ad8;"></i>
                    	</button>
                		</div>

            		</div>
            	   
					<?php
                    $trial_lockout_mode = !empty($trial_lockout_mode) || !empty($this->trial_lockout_active);
                    if(
                        isset($menu_on) && $menu_on &&
                        !$trial_lockout_mode &&
                        current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/register' &&
                        current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/login' &&
                        current_url() != $protocol . $_SERVER['HTTP_HOST'].'/auth/forgot_password'
                    ){?>
						<div  >
                            
							<?php 

							$this->load->view('includes/bootstrapped_sidebar');
							?>

						</div>
					<?php }?>

				<?php if(isset($menu_on) && $menu_on){ ?>
                    <?php
                        if(
                        	current_url() != $protocol . $_SERVER['HTTP_HOST'].'/booking' &&
                        	!str_ends_with(current_url(), '/public/booking') &&
                        	current_url() != $protocol . $_SERVER['HTTP_HOST'].'/booking/overview' &&
                        	!str_ends_with(current_url(), '/public/booking/overview')
                        ){
                            ?>
                            <div class="app-main__outer main" ><div class="app-main__inner">
                            <div class="alert alert-danger trial_period" style="margin: 10px;display: none;"></div>
                        <?php
                            }else{
                        ?>
                        <div class="app_outer main" > <div class="app_inner">
                        <div class="alert alert-danger trial_period" style="margin: 10px;display: none;"></div>
                        <?php }?>
                            <?php } else { ?>
                            <div class="main" >
                            	
                                <div class="">
                                    <?php }
                                    $this->load->view($main_content);
                                    ?>
                                </div>
                                <div class="push"></div>
                                <?php
                                //Load footer
                                $this->load->view('includes/bootstrapped_footer');
                                ?>
                            </div>
                        </div>
                <?php } ?>
			</div>
		</div>
	</body>

	<input hidden id="sellingDate" name="sellingDate" value="<?php echo isset($this->selling_date)?$this->selling_date:''; ?>" />
	<div class="modal fade" id="reservation-message" data-backdrop="static"
	data-keyboard="false" style="z-index: 9999;"
	>
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					<?php echo l('message'); ?>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</h4>

			</div>
			<div class="modal-body message">

			</div>
			<div class="modal-footer">
				<a class="btn btn-success confirm-customer" flag="ok" href="">OK</a>
			</div>

		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" id="cancel-reservation" data-backdrop="static"
data-keyboard="false" style="z-index: 9999;"
>
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title">
				<span class="message-heading"><?php echo l('Confirm cancellation'); ?></span>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</h4>
		</div>
		<div class="modal-body message"></div>
		<div class="modal-footer">
			<a class="btn btn-success confirm-customer" flag="yes" href="javascript:">Yes</a>
			<a class="btn btn-danger confirm-customer" flag="no" href="javascript:">No</a>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" id="dialog-update-billing" data-backdrop="static" 
   data-keyboard="false" 
   >
  <div class="modal-dialog modal-md">
    <div class="modal-content subscription-expired-modal">
      <div class="modal-header">
        <h4 class="modal-title">
            <?php echo l('Subscription Notice', true); ?>
        </h4>        
      </div>
      <div class="modal-body">
        <p class="message"></p>
        <div class="subscription-expired-actions">
          <button type="button" class="btn btn-primary js-subscription-pay-now" style="display: none;">
            Pay Now
          </button>
          <a class="btn btn-success subscription-expired-whatsapp" href="<?php echo htmlspecialchars(platform_admin_whatsapp_url('Hi, I need help renewing my subscription.'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="Chat on WhatsApp">
            <i class="fa fa-whatsapp"></i> WhatsApp Support
          </a>
        </div>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
if (!empty($this->trial_lockout_active) && !empty($this->company_data)) {
    $lockout_popup = build_subscription_lockout_popup_data(
        $this->company_data,
        isset($this->user_id) ? (int) $this->user_id : 0,
        $this->session->userdata('user_role'),
        $this->session->userdata('permissions')
    );
    if ($lockout_popup) {
        echo '<script type="application/json" id="subscription-lockout-data">'
            . json_encode($lockout_popup, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . '</script>';
    }
}
?>
</html>
