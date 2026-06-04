<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Invoice_pdf {

    private $_cache_dir;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->helper(array('invoice_totals', 'date_format', 'image_storage', 'email'));
        $this->_load_dompdf_autoload();
        $this->_cache_dir = $this->_ensure_cache_dir();
    }

    /**
     * @return string|false Path to a temporary PDF file, or false on failure
     */
    public function generate_for_booking($booking_id, $folio_id = null)
    {
        try {
            if (!class_exists('Dompdf\\Dompdf')) {
                $this->_log_pdf('dompdf class not found — run composer install in project root');
                return false;
            }

            $data = $this->_load_invoice_data($booking_id, $folio_id);
            if (!$data) {
                $this->_log_pdf('could not load invoice data for booking ' . $booking_id);
                return false;
            }

            $this->ci->company_date_format = $data['company_date_format'];

            $data['invoice_summary'] = compute_invoice_summary($data['charges'], $data['payments']);
            $data['pdf_mode'] = true;
            $data['read_only'] = true;

            $this->ci->load->helper(array('invoice_totals', 'date_format', 'email'));

            $html = $this->ci->load->view('invoice/invoice_print', $data, true);
            if ($html === '' || $html === false) {
                $this->_log_pdf('empty HTML from invoice_print view for booking ' . $booking_id);
                return false;
            }

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $invoice_label = str_pad((string) ($data['invoice_number'] ?: 0), 8, '0', STR_PAD_LEFT);
            $path = $this->_cache_dir . 'invoice-' . $booking_id . '-' . $invoice_label . '.pdf';

            if (file_put_contents($path, $dompdf->output()) === false) {
                $this->_log_pdf('could not write PDF to ' . $path);
                return false;
            }

            $this->_log_pdf('generated ' . $path . ' (' . filesize($path) . ' bytes) for booking ' . $booking_id);
            return $path;
        } catch (Throwable $e) {
            $this->_log_pdf('booking ' . $booking_id . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return false;
        }
    }

    /**
     * Always writes to application/logs/invoice_pdf.log (production log_threshold is often 0).
     */
    private function _log_pdf($message)
    {
        log_message('error', 'Invoice PDF: ' . $message);
        $line = gmdate('Y-m-d H:i:s') . ' UTC — ' . $message . "\n";
        @file_put_contents(APPPATH . 'logs/invoice_pdf.log', $line, FILE_APPEND | LOCK_EX);
    }

    private function _ensure_cache_dir()
    {
        $dir = rtrim(APPPATH, '/') . '/cache/invoices/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        $resolved = realpath($dir);
        return $resolved ? $resolved . DIRECTORY_SEPARATOR : $dir;
    }

    private function _load_dompdf_autoload()
    {
        if (class_exists('Dompdf\\Dompdf')) {
            return true;
        }

        $candidates = array(
            dirname(FCPATH) . '/vendor/autoload.php',
            realpath(APPPATH . '../vendor/autoload.php'),
            realpath(APPPATH . '../../vendor/autoload.php'),
        );

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                require_once $path;
                return class_exists('Dompdf\\Dompdf');
            }
        }

        return false;
    }

    private function _load_invoice_data($booking_id, $folio_id = null)
    {
        try {
            return $this->_load_invoice_data_inner($booking_id, $folio_id);
        } catch (Throwable $e) {
            $this->_log_pdf('load data booking ' . $booking_id . ': ' . $e->getMessage());
            return false;
        }
    }

    private function _load_invoice_data_inner($booking_id, $folio_id = null)
    {
        $this->ci->load->model(array(
            'Booking_model',
            'Company_model',
            'Booking_room_history_model',
            'Room_model',
            'Charge_model',
            'Charge_type_model',
            'Payment_model',
            'Image_model',
            'Folio_model',
            'Customer_model',
            'Invoice_model',
            'Currency_model',
        ));
        $this->ci->load->library('Forecast_charges');

        $booking = $this->ci->Booking_model->get_booking_detail($booking_id);
        if (!$booking) {
            return false;
        }

        $company_id = isset($booking['company_id']) ? $booking['company_id'] : $this->ci->Company_model->get_company_id_by_booking_id($booking_id);
        $company = $this->ci->Company_model->get_company($company_id);
        if (!$company) {
            return false;
        }

        $room_detail = array('room_name' => '', 'room_type_name' => '');
        $booking_room_history = $this->ci->Booking_room_history_model->get_booking_detail($booking_id);
        if ($booking_room_history) {
            $fetched = $this->ci->Room_model->get_room(
                isset($booking_room_history['room_id']) ? $booking_room_history['room_id'] : null,
                isset($booking_room_history['room_type_id']) ? $booking_room_history['room_type_id'] : null
            );
            if (is_array($fetched)) {
                $room_detail = $fetched;
            }
        }

        $default_currency = $this->ci->Currency_model->get_default_currency($company_id);
        $currency_symbol = isset($default_currency['currency_code']) ? $default_currency['currency_code'] : '';

        $booking_customer = $this->ci->Customer_model->get_customer_info($booking['booking_customer_id']);
        if (!$booking_customer) {
            $booking_customer = array(
                'customer_name' => isset($booking['booking_customer_name']) ? $booking['booking_customer_name'] : 'Guest',
                'email' => isset($booking['booking_customer_email']) ? $booking['booking_customer_email'] : '',
                'phone' => '',
            );
        }

        $staying_customers = $this->ci->Customer_model->get_staying_customers($booking_id);
        $staying_customer_names = array();
        foreach ($staying_customers as $customer) {
            if (!empty($customer['customer_name'])) {
                $staying_customer_names[] = $customer['customer_name'];
            }
        }
        if (!empty($booking_customer['customer_name']) && count($staying_customer_names) > 0) {
            $booking_customer['customer_name'] = $booking_customer['customer_name'] . ', ' . implode(', ', $staying_customer_names);
        }

        $folios = $this->ci->Folio_model->get_folios($booking_id);
        $first_folio_id = isset($folios[0]['id']) ? $folios[0]['id'] : null;
        $folio_id = $folio_id ? $folio_id : $first_folio_id;
        $is_first_folio = ($folio_id === $first_folio_id);

        $charges = $this->ci->Charge_model->get_charges($booking_id, false, $folio_id, $is_first_folio);

        if (empty($company['hide_forecast_charges']) && $is_first_folio) {
            $charges = array_merge(
                $charges,
                $this->ci->forecast_charges->_get_forecast_charges($booking_id, false, $booking),
                $this->ci->forecast_charges->_get_forecast_extra_charges($booking_id, false, $booking)
            );
        }

        $taxes_cache = array();
        foreach ($charges as $index => $charge) {
            if (empty($charge['charge_type_id'])) {
                $charges[$index]['taxes'] = array();
                continue;
            }
            $cache_key = $charge['charge_type_id'] . '-' . $charge['amount'];
            if (isset($taxes_cache[$cache_key])) {
                $charges[$index]['taxes'] = $taxes_cache[$cache_key];
            } else {
                $charges[$index]['taxes'] = $this->ci->Charge_type_model->get_taxes($charge['charge_type_id'], $charge['amount']);
                $taxes_cache[$cache_key] = $charges[$index]['taxes'];
            }
        }

        $payments = $this->ci->Payment_model->get_payments($booking_id, false, $folio_id, $is_first_folio);
        if (!is_array($payments)) {
            $payments = array();
        }

        $logo_url = null;
        if (!empty($company['logo_image_group_id'])) {
            $company_logos = $this->ci->Image_model->get_images($company['logo_image_group_id']);
            if (isset($company_logos[0]['filename'])) {
                $logo_url = image_storage_base_url() . $company['company_id'] . '/' . $company_logos[0]['filename'];
            }
        }

        return array(
            'booking_detail' => $booking,
            'company' => $company,
            'room_detail' => $room_detail,
            'booking_customer' => $booking_customer,
            'charges' => is_array($charges) ? $charges : array(),
            'payments' => $payments,
            'invoice_number' => $this->ci->Invoice_model->get_invoice_number($booking_id),
            'currency_symbol' => $currency_symbol,
            'logo_url' => $logo_url,
            'company_date_format' => !empty($company['date_format']) ? $company['date_format'] : 'YY-MM-DD',
        );
    }
}
