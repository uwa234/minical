
<div class="mc-page">
<?php $this->load->view('includes/mc_page_header', array(
    'title' => l('payment_type_settings', true) . ' ' . l('settings', true),
    'subtitle' => l('Click existing fields to edit.', true),
    'icon' => 'fa-credit-card',
)); ?>

<div class="main-card mb-3 card mc-card">
    <div class="card-body">



<!-- Hidden delete dialog-->		
<div id="confirm_delete_dialog" ></div>
<div class="table-responsive">
<table class="table">
	<tr>
		<th><?php echo l('payment_type'); ?></th>
		<th><?php echo l('delete_booking'); ?></th>
	</tr>			
	<?php if(isset($payment_types)) : foreach ($payment_types as $payment_type) : ?>
	<tr>
		<td>
			<div class="payment-type-name-editable" id="<?php echo $payment_type->payment_type_id; ?>"><?php echo $payment_type->payment_type; ?></div>
		</td>
		<td>
			<div class="delete-payment-type btn btn-light" id="<?php echo $payment_type->payment_type_id ?>">X</div>
		</td>
	</tr>
	<?php endforeach; ?>
	<?php else : ?>
	<h3><?php echo l('No payment types have been recorded.', true); ?></h3>
	<?php endif; ?>
</table>
</div>


<button id="add-payment-type" class="btn btn-primary"><?php echo l('add_payment_type'); ?></button>
</div></div></div>
