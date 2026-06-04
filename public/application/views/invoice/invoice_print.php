<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
$summary = isset($invoice_summary) ? $invoice_summary : compute_invoice_summary($charges, $payments);
$invoice_num_display = str_pad((string) (isset($invoice_number) ? $invoice_number : 0), 8, '0', STR_PAD_LEFT);
$charges = isset($charges) ? $charges : array();
$payments = isset($payments) ? $payments : array();
$date_format = isset($company_date_format) ? $company_date_format : 'YY-MM-DD';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .header-row td { border: none; vertical-align: top; padding: 0 12px 12px 0; }
        .totals td { border: none; padding: 4px 8px; }
        .amount-due { font-size: 14px; font-weight: bold; margin-top: 8px; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 8px; }
    </style>
</head>
<body>
<?php if (!empty($logo_url)): ?>
    <img class="logo" src="<?php echo htmlspecialchars($logo_url); ?>" alt="">
<?php endif; ?>

<table class="header-row">
    <tr>
        <td width="40%">
            <strong><?php echo htmlspecialchars($company['name']); ?></strong><br>
            <?php
            $addr = array_filter(array($company['address'], $company['city'], $company['region'], $company['postal_code']));
            echo htmlspecialchars(implode(', ', $addr));
            if (!empty($company['phone'])) {
                echo '<br>' . invoice_pdf_label('Phone', 'Phone') . ': ' . htmlspecialchars($company['phone']);
            }
            if (!empty($company['email'])) {
                echo '<br>' . invoice_pdf_label('Email', 'Email') . ': ' . htmlspecialchars($company['email']);
            }
            ?>
        </td>
        <td width="35%">
            <strong><?php echo invoice_pdf_label('billed_to', 'Billed to'); ?>:</strong><br>
            <strong><?php echo htmlspecialchars($booking_customer['customer_name']); ?></strong><br>
            <?php
            if (!empty($booking_customer['email'])) {
                echo invoice_pdf_label('Email', 'Email') . ': ' . htmlspecialchars($booking_customer['email']) . '<br>';
            }
            if (!empty($booking_customer['phone'])) {
                echo invoice_pdf_label('Phone', 'Phone') . ': ' . htmlspecialchars($booking_customer['phone']) . '<br>';
            }
            ?>
        </td>
        <td width="25%" class="text-right">
            <strong><?php echo invoice_pdf_label('invoice', 'Invoice'); ?> #:</strong> <?php echo $invoice_num_display; ?><br>
            <strong><?php echo invoice_pdf_label('booking', 'Booking') . ' ' . invoice_pdf_label('id', 'ID'); ?>:</strong> <?php echo (int) $booking_detail['booking_id']; ?><br>
            <?php if (!empty($room_detail['room_name'])): ?>
                <?php echo htmlspecialchars(!empty($company['default_room_singular']) ? $company['default_room_singular'] : 'Room'); ?>:
                <?php echo htmlspecialchars($room_detail['room_name']); ?><br>
            <?php endif; ?>
            <?php echo invoice_pdf_label('check_in_date', 'Check-in date'); ?>:
            <?php echo get_local_formatted_date($booking_detail['check_in_date'], $date_format); ?><br>
            <?php echo invoice_pdf_label('check_out_date', 'Check-out date'); ?>:
            <?php echo get_local_formatted_date($booking_detail['check_out_date'], $date_format); ?>
        </td>
    </tr>
</table>

<h1><?php echo invoice_pdf_label('invoice', 'Invoice'); ?></h1>

<table>
    <thead>
        <tr>
            <th><?php echo invoice_pdf_label('date', 'Date'); ?></th>
            <th><?php echo invoice_pdf_label('description', 'Description'); ?></th>
            <th><?php echo invoice_pdf_label('charge_type', 'Charge type'); ?></th>
            <th class="text-right"><?php echo invoice_pdf_label('amount', 'Amount'); ?></th>
            <th class="text-right"><?php echo invoice_pdf_label('total', 'Total'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($charges)): foreach ($charges as $charge): ?>
        <tr>
            <td><?php echo get_local_formatted_date($charge['selling_date'], $date_format); ?></td>
            <td><?php echo htmlspecialchars(isset($charge['description']) ? $charge['description'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($charge['charge_type_name']) ? $charge['charge_type_name'] : ''); ?></td>
            <td class="text-right"><?php echo number_format((float) $charge['amount'], 2, '.', ','); ?></td>
            <td class="text-right"><?php echo number_format(invoice_charge_line_total($charge), 2, '.', ','); ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<table class="totals" style="width: 50%; margin-left: auto;">
    <tr>
        <td class="text-right"><?php echo invoice_pdf_label('subtotal', 'Subtotal'); ?>:</td>
        <td class="text-right" width="100"><?php echo number_format($summary['subtotal'], 2, '.', ','); ?></td>
    </tr>
    <tr>
        <td class="text-right"><?php echo invoice_pdf_label('taxes', 'Taxes'); ?>:</td>
        <td class="text-right"><?php echo number_format($summary['total_tax'], 2, '.', ','); ?></td>
    </tr>
    <tr>
        <td class="text-right"><?php echo invoice_pdf_label('charge', 'Charge') . ' ' . invoice_pdf_label('total', 'total'); ?>:</td>
        <td class="text-right"><?php echo number_format($summary['total_charge'], 2, '.', ','); ?></td>
    </tr>
</table>

<?php if (!empty($payments)): ?>
<h1 style="font-size: 14px;"><?php echo invoice_pdf_label('payments', 'Payments'); ?></h1>
<table>
    <thead>
        <tr>
            <th><?php echo invoice_pdf_label('date', 'Date'); ?></th>
            <th><?php echo invoice_pdf_label('description', 'Description'); ?></th>
            <th><?php echo invoice_pdf_label('payment_type', 'Payment type'); ?></th>
            <th class="text-right"><?php echo invoice_pdf_label('amount', 'Amount'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $payment): ?>
        <tr>
            <td><?php echo get_local_formatted_date($payment['selling_date'], $date_format); ?></td>
            <td><?php echo htmlspecialchars(!empty($payment['description']) ? $payment['description'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($payment['payment_type']) ? $payment['payment_type'] : ''); ?></td>
            <td class="text-right"><?php echo number_format((float) $payment['amount'], 2, '.', ','); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="text-right"><strong><?php echo invoice_pdf_label('payment', 'Payment') . ' ' . invoice_pdf_label('total', 'total'); ?>:</strong></td>
            <td class="text-right"><strong><?php echo number_format($summary['total_credit'], 2, '.', ','); ?></strong></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<p class="amount-due text-right">
    <?php echo invoice_pdf_label('amount', 'Amount') . ' ' . invoice_pdf_label('due', 'due'); ?>:
    <?php echo number_format($summary['amount_due'], 2, '.', ','); ?>
    <?php echo htmlspecialchars($currency_symbol); ?>
    <?php if ($summary['paid_in_full']): ?>
        — <?php echo invoice_pdf_label('paid_in_full', 'Paid in full'); ?>
    <?php endif; ?>
</p>
</body>
</html>
