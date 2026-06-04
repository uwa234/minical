<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Email Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/email_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Validate email address
 *
 * @access	public
 * @return	bool
 */
if ( ! function_exists('valid_email'))
{
	function valid_email($address)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}
}

// ------------------------------------------------------------------------

/**
 * Send an email
 *
 * @access	public
 * @return	bool
 */
if ( ! function_exists('send_email'))
{
	function send_email($recipient, $subject = 'Test email', $message = 'Hello World')
	{
		return mail($recipient, $subject, $message);
	}
}

/**
 * From address for outbound SMTP mail. Must match the authenticated SMTP mailbox;
 * property or company addresses belong in Reply-To instead.
 */
if ( ! function_exists('minical_smtp_from_email'))
{
	function minical_smtp_from_email($whitelabelinfo = null)
	{
		$smtp_user = function_exists('minical_env') ? minical_env('SMTP_USER', '') : getenv('SMTP_USER');
		if ($smtp_user) {
			return $smtp_user;
		}
		return function_exists('minical_env') ? minical_env('MAIL_FROM', 'info@veurion.com') : 'info@veurion.com';
	}
}

/**
 * Invoice e-mail copy with English defaults (DB phrase keys are optional).
 *
 * @param string $key
 * @param array  $sprintf_args Optional values for sprintf placeholders
 * @return string
 */
if ( ! function_exists('invoice_email_copy'))
{
	function invoice_email_copy($key, array $sprintf_args = array())
	{
		$defaults = array(
			'invoice_email_subject' => 'Your invoice from %s — Invoice #%s',
			'invoice_email_body_intro' => 'Please find your invoice attached to this email for your stay with us.',
			'invoice_email_body_online' => 'You can also view your invoice online at the link below:',
			'invoice_email_body_link_only' => 'Please use the link below to view your invoice:',
			'master_invoice_email_subject' => 'Your group invoice from %s — Group #%s',
			'master_invoice_email_body_intro' => 'Please find your group invoice attached to this email.',
			'master_invoice_email_body_online' => 'You can also view your group invoice online at the link below:',
			'invoice_email_view_online' => 'Please visit the following link to view your invoice',
		);

		$text = function_exists('l') ? l($key, true) : $key;
		if ($text === $key || strpos($text, 'invoice_email_') === 0 || strpos($text, 'master_invoice_email_') === 0) {
			$text = isset($defaults[$key]) ? $defaults[$key] : $key;
		}

		return $sprintf_args ? vsprintf($text, $sprintf_args) : $text;
	}
}

if ( ! function_exists('invoice_pdf_label'))
{
	function invoice_pdf_label($phrase_key, $english_default)
	{
		if (!function_exists('l')) {
			return $english_default;
		}
		$text = l($phrase_key, true);
		if ($text === $phrase_key || $text === '') {
			return $english_default;
		}
		return $text;
	}
}


/* End of file email_helper.php */
/* Location: ./system/helpers/email_helper.php */