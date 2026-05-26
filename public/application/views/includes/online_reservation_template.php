<?php if($this->session->userdata('online_language_id')){
            load_translations($this->session->userdata('online_language_id'));
        }
        else{
           if(isset($company_data['default_language']) && $company_data['default_language']) {
            	load_translations($company_data['default_language']);
			}
        } 

$files = get_asstes_files($this->module_assets_files, $this->router->fetch_module(), $this->controller_name, $this->function_name);
		
		foreach ($files['css_files'] as $key => $value) {
			$css_files[] = $value;
		}


        ?>

<?php 
$ifieldKey = getenv('CARDKNOX_IFIELD_KEY');
	echo "<script>
    const ifieldKey = '" . $ifieldKey . "';
        </script>";
?>

<!DOCTYPE html>
<html lang="en" class="obe-booking-engine">

	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
		<title><?php echo $company_data['name']." Online Booking " ?></title>
		
		<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>css/bootstrap.min.css"  />	
		<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>css/bootstrap-theme.min.css"  />	
		<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>css/bootstrap-override.css"  />	
		<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>css/smoothness/jquery-ui.min.css" />	
		<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>css/lightbox.css" />	
		<link rel="shortcut icon" href="<?php echo base_url();?>images/favicon.ico" type="image/x-icon" />
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
		
		<?php if (isset($css_files)) : foreach ($css_files as $path) : ?>
		<link rel="stylesheet" type="text/css" href="<?php echo $path; ?>" />
		<?php endforeach; ?>
		<?php endif; 
		if(isset($company_data['booking_engine_tracking_code']) && $company_data['booking_engine_tracking_code']) {
		   echo html_entity_decode(html_entity_decode($company_data['booking_engine_tracking_code'] , ENT_COMPAT));
		}
		
		?>
		
	</head>
	<body class="obe-booking-engine__body">

	<?php
		if (validation_errors() != ""):
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
				  	<span class="sr-only"><?php echo l('Error', true); ?>:</span>
  					<strong><?php echo l('Please correct the below error(s)', true); ?>:</strong>
  					<?php echo validation_errors(); ?>
				</div>
			</div>
	<?php
		endif;
	?>

    <input type="hidden" name="project_url" id="project_url" value="<?php echo base_url(); ?>">

	<?php
		$website = $company_data['website'];
		if (!preg_match("~^(?:f|ht)tps?://~i", $website)) {
			$website = "https://" . $website;
		}
		$room_step_label = (isset($company_data['default_room_singular']) && $company_data['default_room_singular'] != '')
			? l('Select') . ' ' . $company_data['default_room_singular']
			: l('Select Room', 1);
		$languages = get_enabled_languages();
		$current_language_label = $this->session->userdata('online_language')
			? ucfirst($this->session->userdata('online_language'))
			: (isset($company_data['default_language']) && $company_data['default_language']
				? array_search($company_data['default_language'], array_column($languages, 'id', 'language_name'))
				: 'English');
	?>
	<header class="obe-header">
		<div class="obe-header__inner container">
			<div class="obe-header__brand">
				<p class="obe-header__eyebrow"><?php echo l('You are now making a reservation at'); ?></p>
				<h1 class="obe-header__title"><a href="<?php echo $website; ?>"><?php echo $company_data['name']; ?></a></h1>
			</div>
			<div class="obe-header__actions">
				<div class="obe-language">
					<label class="obe-language__label" for="myLanguageMenu"><?php echo l('Language', true); ?></label>
					<a href="#" id="myLanguageMenu" class="obe-language__toggle" data-toggle="dropdown" aria-expanded="true">
						<span id="current_language"><?php echo $current_language_label; ?></span>
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu dropdown-menu-right obe-language__menu" role="menu" aria-labelledby="myLanguageMenu">
						<?php if (!empty($languages)) {
							foreach ($languages as $key => $value) { ?>
								<li role="presentation">
									<a class="change_language" role="menuitem" tabindex="-1" lang_data="<?php echo $value['id'] . ',' . strtolower($value['language_name']); ?>" href="javascript:">
										<?php echo $value['language_name']; ?>
									</a>
								</li>
							<?php }
						} ?>
					</ul>
				</div>
			</div>
		</div>
		<nav class="obe-steps container" aria-label="<?php echo l('Booking progress', true); ?>">
			<ol class="obe-steps__list">
				<li class="obe-steps__item <?php echo $current_step == 1 ? 'obe-steps__item--active' : ($current_step > 1 ? 'obe-steps__item--complete' : ''); ?>">
					<span class="obe-steps__number">1</span>
					<span class="obe-steps__label"><?php echo l('Select Dates', 1); ?></span>
				</li>
				<li class="obe-steps__item <?php echo $current_step == 2 ? 'obe-steps__item--active' : ($current_step > 2 ? 'obe-steps__item--complete' : ''); ?>">
					<span class="obe-steps__number">2</span>
					<span class="obe-steps__label"><?php echo $room_step_label; ?></span>
				</li>
				<li class="obe-steps__item <?php echo $current_step == 3 ? 'obe-steps__item--active' : ($current_step > 3 ? 'obe-steps__item--complete' : ''); ?>">
					<span class="obe-steps__number">3</span>
					<span class="obe-steps__label"><?php echo l('Guest information', 1); ?></span>
				</li>
				<li class="obe-steps__item <?php echo isset($current_step) && $current_step >= 4 ? 'obe-steps__item--active obe-steps__item--complete' : ''; ?>">
					<span class="obe-steps__number">4</span>
					<span class="obe-steps__label"><?php echo l('Confirmation', true); ?></span>
				</li>
			</ol>
		</nav>
		<?php if ($company_data['phone'] || $company_data['address']) : ?>
		<div class="obe-header__contact container hidden-xs">
			<address class="obe-contact">
				<?php if ($company_data['address'] != "") : ?>
					<span><?php echo $company_data['address']; ?></span>
				<?php endif; ?>
				<?php
					$city_line = trim(
						($company_data['city'] != "" ? $company_data['city'] : '') .
						($company_data['region'] != "" ? ", " . $company_data['region'] : '') .
						($company_data['postal_code'] != "" ? " " . $company_data['postal_code'] : '')
					);
					if ($city_line) : ?>
					<span><?php echo $city_line; ?></span>
				<?php endif; ?>
				<?php if ($company_data['phone']) : ?>
					<span class="obe-contact__phone">
						<i class="glyphicon glyphicon-phone-alt" aria-hidden="true"></i>
						<?php echo $company_data['phone']; ?>
					</span>
				<?php endif; ?>
			</address>
		</div>
		<?php endif; ?>
	</header>
	<main class="obe-main">
	<?php
		//Generate css and js file arrays if they don't already exist.
		//This prevents clobbering of the variables caused by multiple loading of this file (ie. iframes).
		if (!isset($css_files)) {
			$css_files = array();
		}
		if (!isset($js_files)) {
			$js_files = array();
		}

		

		foreach ($files['js_files'] as $key => $value) {
			$js_files[] = $value;
		}

		// prx($files);
		
		//Load header
		$data = array ( 'css_files' => $css_files, 'js_files' => $js_files );		
		$this->load->view($main_content, $data);
	?>


	</main>
	<footer class="obe-footer hidden-print">
	    <?php
        $whitelabelinfo = $this->session->userdata('white_label_information');
        // Set the partner name
        $partner_name =  isset($whitelabelinfo['name']) ? ucfirst($whitelabelinfo['name']) : $this->config->item('branding_name');
	    $time = time() ;
	    $year= date("Y",$time);
	    echo l('powered by', true);
        
        $wl_brand = isset($whitelabelinfo['name']) ? strtolower(trim((string) $whitelabelinfo['name'])) : '';
        $default_powered_by_brands = array('minical', 'veurion');
        if (empty($whitelabelinfo) || ($wl_brand !== '' && in_array($wl_brand, $default_powered_by_brands, true))) {
            echo " <a target='_blank' href='https://www.veurion.com'>Veurion</a>";
        } else {
        	if(!empty($whitelabelinfo['website'])) {
        		echo '<a target="_blank" href="'.$whitelabelinfo['website'].'" > '.$partner_name.'</a>';
        	} else {
        		echo (!empty($whitelabelinfo['domain']) ? " <a target='_blank' href='https://".$whitelabelinfo['domain']."'>" : " <a href='#'>").$partner_name."</a>";
        	}
            
        }
		echo ''; //Don't bother with showing copyright until a dashbar is built.
	    ?>
	</footer>


	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url();?>js/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url();?>js/lightbox.min.js"></script>
	<script type="text/javascript" src="<?php echo base_url();?>js/validator.min.js"></script>

	<?php if (isset($js_files)) : foreach ($js_files as $path) : ?>
		<script type="text/javascript" src="<?php echo $path; ?>"></script>
	<?php endforeach; ?>
	<?php endif; ?>		
	<?php $is_current_user_admin = $this->User_model->is_admin($this->user_id);
		echo "<script>var base_url = '".$this->config->item('base_url')."'</script>";
		echo "<script>var is_current_user_admin = '".$is_current_user_admin."'</script>";
		
		//echo "<script>var booking_engine_tracking_code = '".html_entity_decode(html_entity_decode($company_data['booking_engine_tracking_code'] , ENT_COMPAT))."'</script>";
	?>

	<script>
    <!-- Below script used for language translation  -->
    <?php 
        $l = addslashes(json_encode(isset($this->all_translations_data) ? $this->all_translations_data : array()));
    ?>
    <!-- Create global variable for language phrases array -->
    var language_phrases = JSON.parse('<?php echo $l; ?>');
    var nonTranslatedKeys = new Array();
    <!-- Below function return a value of phrase key -->


    function l(phrase_key)
    {
        <?php if($this->user_id === SUPER_ADMIN_USER_ID) { ?>
        if (language_phrases[phrase_key] === undefined) {
            nonTranslatedKeys.push(phrase_key);
        }
        <?php } ?>

        return language_phrases[phrase_key] || (language_phrases[phrase_key.toString().toLowerCase()] || phrase_key);
    }

    // add non-translated-keys to DB 
    <?php if($this->user_id === SUPER_ADMIN_USER_ID) { ?>
    setInterval(function () {
        if (nonTranslatedKeys.length > 0){

            $.ajax({
                type: "POST",
                url: getBaseURL() + 'language_translation/insert_non_translated_keys',
                data: { non_translated_keys: nonTranslatedKeys},
                dataType: "json",
                success: function( data ) {
                    // console.log('data', data);
                }
            });

            nonTranslatedKeys = [];
        }
    }, 10000);
    <?php } ?>

</script>
        
</body>
</html>
