<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('invoice_charge_line_total')) {
    /**
     * Total for one charge row (matches invoice view / invoice.js).
     */
    function invoice_charge_line_total($charge)
    {
        $rate = (float) $charge['amount'];
        $combined_tax = 0;

        if (empty($charge['taxes'])) {
            return round($rate, 2);
        }

        foreach ($charge['taxes'] as $tax) {
            if (!empty($tax['is_percentage'])) {
                if (!empty($tax['is_tax_inclusive'])) {
                    continue;
                }
                $combined_tax += (float) $tax['tax_rate'] * $rate * 0.01;
            } else {
                $combined_tax += (float) $tax['tax_rate'];
            }
        }

        return round($rate + $combined_tax, 2);
    }
}

if (!function_exists('compute_invoice_summary')) {
  /**
   * Subtotal, taxes, charge total, payments, and balance due.
   */
    function compute_invoice_summary($charges, $payments)
    {
        $totalCharge = 0;
        $totalTax = 0;
        $taxByType = array();

        if ($charges) {
            foreach ($charges as $charge) {
                $rate = (float) $charge['amount'];

                if (!empty($charge['taxes'])) {
                    foreach ($charge['taxes'] as $tax) {
                        if (!empty($tax['is_tax_inclusive'])) {
                            continue;
                        }
                        if (!empty($tax['is_percentage'])) {
                            $tax_amount = (float) $tax['tax_rate'] * $rate * 0.01;
                        } else {
                            $tax_amount = (float) $tax['tax_rate'];
                        }
                        $totalTax += $tax_amount;
                        $name = $tax['tax_type'];
                        if (!isset($taxByType[$name])) {
                            $taxByType[$name] = 0;
                        }
                        $taxByType[$name] += $tax_amount;
                    }
                }

                $totalCharge += invoice_charge_line_total($charge);
            }
        }

        $totalCredit = 0;
        if ($payments) {
            foreach ($payments as $payment) {
                $totalCredit += (float) $payment['amount'];
            }
        }

        $subtotal = $totalCharge - $totalTax;
        $amountDue = round($totalCharge - $totalCredit, 2);

        return array(
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'tax_by_type' => $taxByType,
            'total_charge' => $totalCharge,
            'total_credit' => $totalCredit,
            'amount_due' => $amountDue,
            'paid_in_full' => abs($amountDue) < 0.01,
        );
    }
}
