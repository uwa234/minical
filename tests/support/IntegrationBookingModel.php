<?php

require_once APPPATH . 'models/booking_model.php';

class IntegrationBookingModel extends Booking_model
{
    public function setDatabase($db): void
    {
        $this->db = $db;
    }

    public function __construct()
    {
        CI_Model::__construct();
    }
}
