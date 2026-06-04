<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CLI-only PDF smoke test (no login required).
 * Usage: php public/index.php invoice_pdf_cli generate 26
 */
class Invoice_pdf_cli extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function generate($booking_id = 0)
    {
        if (!$this->input->is_cli_request()) {
            show_404();
            return;
        }

        $booking_id = (int) $booking_id;
        echo "Dompdf: " . (class_exists('Dompdf\\Dompdf') ? 'yes' : 'no') . "\n";

        $this->load->library('invoice_pdf');
        $path = $this->invoice_pdf->generate_for_booking($booking_id);

        if ($path && is_file($path)) {
            echo "OK: $path (" . filesize($path) . " bytes)\n";
        } else {
            echo "FAIL: PDF not generated for booking $booking_id\n";
        }

        $log = APPPATH . 'logs/invoice_pdf.log';
        if (is_file($log)) {
            echo "\n--- invoice_pdf.log ---\n" . file_get_contents($log);
        }
    }
}
