<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Runs night audit on a schedule using company.night_audit_auto_run_* settings.
 * No marketplace extension required.
 */
class Automated_night_audit {

    const RUN_WINDOW_MINUTES = 90;
    const OVERDUE_GRACE_HOURS = 3;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Company_model');
        $this->ci->load->model('Night_audit_model');
        $this->ci->load->library('night_audit');
        $this->ci->load->helper(array('timezone', 'automated_night_audit'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function process_all()
    {
        $companies = $this->ci->Company_model->get_companies_with_auto_night_audit();
        $results = array();

        if (!$companies) {
            return $results;
        }

        foreach ($companies as $company) {
            $company_id = (int) $company['company_id'];
            $results[$company_id] = $this->process_company($company);
        }

        return $results;
    }

    /**
     * @param array<string, mixed>|int $company row or company_id
     * @return array<string, mixed>
     */
    public function process_company($company)
    {
        if (!is_array($company)) {
            $company = $this->ci->Company_model->get_company((int) $company);
        }

        if (!$company || empty($company['company_id'])) {
            return array('status' => 'error', 'message' => 'Company not found');
        }

        $company_id = (int) $company['company_id'];
        $name = isset($company['name']) ? $company['name'] : ('#' . $company_id);

        if (empty($company['night_audit_auto_run_is_enabled'])) {
            return array('status' => 'skipped', 'message' => 'Auto night audit disabled');
        }

        $time_zone = $this->ci->Company_model->get_time_zone($company_id);
        $local_now = automated_night_audit_local_now($time_zone);
        $run_time = !empty($company['night_audit_auto_run_time'])
            ? $company['night_audit_auto_run_time']
            : '04:00:00';

        $selling_date = $this->ci->Company_model->get_selling_date($company_id);
        $has_log = $this->ci->Night_audit_model->has_logged_for_selling_date($company_id, $selling_date);

        if (!automated_night_audit_is_run_window($local_now, $run_time, self::RUN_WINDOW_MINUTES)) {
            if ($this->_send_overdue_alert_if_needed($company, $local_now, $run_time, $selling_date, $has_log)) {
                return array(
                    'status' => 'alert',
                    'message' => 'Selling date behind calendar; alert sent',
                    'selling_date' => $selling_date,
                );
            }

            return array('status' => 'skipped', 'message' => 'Outside scheduled run window');
        }

        if (!automated_night_audit_needs_roll($local_now, $selling_date, $has_log)) {
            return array('status' => 'skipped', 'message' => 'Night audit already completed for selling date');
        }

        $actual_date = $local_now->format('Y-m-d');
        if ($selling_date > $actual_date) {
            return array(
                'status' => 'error',
                'message' => "Selling date ($selling_date) is ahead of calendar ($actual_date)",
            );
        }

        $result_message = $this->ci->night_audit->run_night_audit($company_id);
        $success = $this->ci->Night_audit_model->has_logged_for_selling_date($company_id, $selling_date);

        if ($success) {
            $this->ci->Company_model->update_company($company_id, array(
                'last_night_audit' => $selling_date,
            ));

            log_message('info', "Automated night audit completed for company {$company_id} ({$name})");

            return array(
                'status' => 'ran',
                'message' => $result_message,
                'selling_date_before' => $selling_date,
                'selling_date_after' => $this->ci->Company_model->get_selling_date($company_id),
            );
        }

        log_message('error', "Automated night audit failed for company {$company_id}: {$result_message}");
        $this->_send_failure_alert($company, $result_message);

        return array('status' => 'error', 'message' => $result_message);
    }

    private function _send_overdue_alert_if_needed($company, DateTime $local_now, $run_time, $selling_date, $has_log)
    {
        if (!automated_night_audit_is_overdue(
            $local_now,
            $run_time,
            $selling_date,
            $has_log,
            self::OVERDUE_GRACE_HOURS
        )) {
            return false;
        }

        $this->_send_alert_email(
            $company,
            'Night audit overdue — ' . $company['name'],
            "The selling date ({$selling_date}) is behind today's calendar date ("
            . $local_now->format('Y-m-d') . ") and automated night audit has not run.\n\n"
            . "Please check Night Audit settings, default room charge type, and cron job "
            . "(/cron/run_automated_night_audit)."
        );

        return true;
    }

    private function _send_failure_alert($company, $error_message)
    {
        $this->_send_alert_email(
            $company,
            'Automated night audit failed — ' . $company['name'],
            "Automated night audit returned an error:\n\n{$error_message}\n\n"
            . 'Run night audit manually from Settings → Night Audit & Date.'
        );
    }

    private function _send_alert_email($company, $subject, $body)
    {
        if (getenv('NIGHT_AUDIT_SEND_ALERTS') === '0') {
            return;
        }

        if (!getenv('SMTP_USER')) {
            log_message('debug', 'Night audit alert skipped: SMTP_USER not configured');

            return;
        }

        if (empty($company['owner_email']) && !empty($company['company_id'])) {
            $full = $this->ci->Company_model->get_company($company['company_id']);
            if ($full && !empty($full['owner_email'])) {
                $company['owner_email'] = $full['owner_email'];
            }
        }

        $to = getenv('NIGHT_AUDIT_ALERT_EMAIL');
        if (!$to && !empty($company['owner_email'])) {
            $to = $company['owner_email'];
        }

        if (!$to) {
            log_message('debug', 'Night audit alert skipped: no recipient');

            return;
        }

        $this->ci->load->library('email');
        $from_email = 'donotreply@minical.io';
        $from_name = 'miniCal';

        $this->ci->email->from($from_email, $from_name);
        $this->ci->email->to($to);
        $this->ci->email->subject($subject);
        $this->ci->email->message(nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));
        $this->ci->email->set_mailtype('html');

        if (!$this->ci->email->send()) {
            log_message('error', 'Night audit alert email failed: ' . $this->ci->email->print_debugger(array('headers')));
        }
    }
}
