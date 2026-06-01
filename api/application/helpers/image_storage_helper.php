<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Company images are stored on S3 when credentials are configured;
 * otherwise they are saved under public/uploads/{company_id}/.
 */

if (!function_exists('image_storage_use_s3')) {
	function image_storage_use_s3()
	{
		$key = getenv('AWS_ACCESS_KEY');
		$secret = getenv('AWS_SECRET_KEY');
		$bucket = getenv('AWS_S3_BUCKET');

		if ($key === false || $key === '' || $secret === false || $secret === '') {
			return false;
		}
		if ($bucket === false || $bucket === '') {
			return false;
		}

		$placeholders = array('ENTER YOUR AWS', 'YOUR AWS S3 BUCKET');
		foreach (array($key, $secret, $bucket) as $value) {
			foreach ($placeholders as $needle) {
				if (stripos($value, $needle) !== false) {
					return false;
				}
			}
		}

		return true;
	}
}

if (!function_exists('image_storage_base_url')) {
	function image_storage_base_url()
	{
		if (image_storage_use_s3()) {
			return 'https://' . getenv('AWS_S3_BUCKET') . '.s3.amazonaws.com/';
		}

		$CI =& get_instance();
		return rtrim($CI->config->item('base_url'), '/') . '/uploads/';
	}
}

if (!function_exists('image_storage_company_path')) {
	function image_storage_company_path($company_id)
	{
		return rtrim(FCPATH, '/') . '/uploads/' . (int) $company_id . '/';
	}
}

if (!function_exists('image_storage_ensure_company_dir')) {
	function image_storage_ensure_company_dir($company_id)
	{
		$dir = image_storage_company_path($company_id);
		if (is_dir($dir)) {
			return is_writable($dir);
		}
		return @mkdir($dir, 0775, true) && is_writable($dir);
	}
}

if (!function_exists('image_storage_save_local')) {
	function image_storage_save_local($company_id, $source_path, $filename)
	{
		if (!image_storage_ensure_company_dir($company_id)) {
			return false;
		}

		$dest = image_storage_company_path($company_id) . $filename;
		if (file_exists($dest)) {
			@unlink($dest);
		}

		if (is_uploaded_file($source_path)) {
			return @move_uploaded_file($source_path, $dest);
		}

		return @copy($source_path, $dest);
	}
}

if (!function_exists('image_storage_delete_local')) {
	function image_storage_delete_local($company_id, $filename)
	{
		$path = image_storage_company_path($company_id) . basename($filename);
		if (file_exists($path)) {
			return @unlink($path);
		}
		return true;
	}
}

if (!function_exists('image_storage_upload_failure_message')) {
	function image_storage_upload_failure_message()
	{
		if (image_storage_use_s3()) {
			return 'Image upload failed. Please verify your AWS S3 settings or try again.';
		}
		return 'Image upload failed. The server could not save the file. Please contact support.';
	}
}
