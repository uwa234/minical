<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CSRF protection by verifying the Origin / Referer of state-changing requests.
 *
 * Browsers always send Origin on cross-site POSTs, so a request whose Origin
 * (or Referer) host differs from this site is rejected. Server-to-server calls
 * (webhooks, cron) send neither header and are allowed through. Routes that
 * receive cross-site browser POSTs (payment gateway returns, the embeddable
 * booking engine) are listed in csrf_exclude_routes.
 */
class MY_Security extends CI_Security {

	public function csrf_verify()
	{
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		if (in_array($method, array('GET', 'HEAD', 'OPTIONS'), TRUE))
		{
			return $this;
		}

		if ($this->_is_excluded_route())
		{
			return $this;
		}

		$source = '';
		if ( ! empty($_SERVER['HTTP_ORIGIN']))
		{
			$source = $_SERVER['HTTP_ORIGIN'];
		}
		elseif ( ! empty($_SERVER['HTTP_REFERER']))
		{
			$source = $_SERVER['HTTP_REFERER'];
		}

		if ($source === '')
		{
			return $this;
		}

		$source_host = $this->_normalize_host($source);
		if ($source_host !== '' && in_array($source_host, $this->_trusted_hosts(), TRUE))
		{
			return $this;
		}

		log_message('error', 'CSRF: blocked '.$method.' to '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '').' from '.$source);
		$this->csrf_show_error();
	}

	public function csrf_show_error()
	{
		$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		if ($is_ajax)
		{
			header('HTTP/1.1 403 Forbidden');
			header('Content-Type: application/json');
			exit(json_encode(array('success' => FALSE, 'error' => 'The action you have requested is not allowed.')));
		}
		show_error('The action you have requested is not allowed.', 403);
	}

	private function _trusted_hosts()
	{
		$hosts = array();
		if ( ! empty($_SERVER['HTTP_HOST']))
		{
			$hosts[] = strtolower($_SERVER['HTTP_HOST']);
		}
		if ( ! empty($_SERVER['HTTP_X_FORWARDED_HOST']))
		{
			$hosts[] = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]));
		}

		$base_url = config_item('base_url');
		if ($base_url)
		{
			$hosts[] = $this->_normalize_host($base_url);
		}

		$extra = getenv('CSRF_TRUSTED_ORIGINS');
		if ($extra)
		{
			foreach (explode(',', $extra) as $origin)
			{
				$hosts[] = $this->_normalize_host(trim($origin));
			}
		}

		return array_values(array_filter(array_unique($hosts), 'strlen'));
	}

	private function _normalize_host($url)
	{
		if (strpos($url, '://') === FALSE)
		{
			$url = 'http://'.$url;
		}
		$parts = parse_url($url);
		if (empty($parts['host']))
		{
			return '';
		}
		$host = strtolower($parts['host']);
		return isset($parts['port']) ? $host.':'.$parts['port'] : $host;
	}

	private function _is_excluded_route()
	{
		$path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
		$path = '/'.trim(strtolower((string) $path), '/').'/';

		$routes = config_item('csrf_exclude_routes');
		if ( ! is_array($routes))
		{
			return FALSE;
		}

		foreach ($routes as $route)
		{
			if (strpos($path, '/'.trim(strtolower($route), '/').'/') !== FALSE)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
}
