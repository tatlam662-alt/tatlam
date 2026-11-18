<?php
// Heading
$_['heading_title']              = 'Orders';

// Text
$_['text_account']               = 'Account';
$_['text_order']                 = 'Order #%s';
$_['text_order_id']              = 'Order ID';
$_['text_invoice_no']            = 'Invoice No.';
$_['text_tbc']                   = 'TBC';
$_['text_shipping_address']      = 'Shipping Address';
$_['text_payment_address']       = 'Payment Address';
$_['text_shipping_method']       = 'Shipping Method';
$_['text_payment_method']        = 'Payment Method';
$_['text_model']                 = 'Model';
$_['text_points']                = 'Reward Points';
$_['text_subscription']          = 'Subscription';
$_['text_subscription_trial']    = '%s every %d %s(s) for %d payment(s) then ';
$_['text_subscription_duration'] = '%s every %d %s(s) for %d payment(s)';
$_['text_subscription_cancel']   = '%s every %d %s(s) until canceled';
$_['text_day']                   = 'day';
$_['text_week']                  = 'week';
$_['text_semi_month']            = 'half-month';
$_['text_month']                 = 'month';
$_['text_year']                  = 'year';
$_['text_date_next']             = 'Date Next';
$_['text_remaining']             = 'Remaining';
$_['text_comment']               = 'Note';
$_['text_history']               = 'Order History';
$_['text_success']               = 'Success: You have added <a href="%s">%s</a> to your <a href="%s">shopping cart</a>!';
$_['text_no_results']            = 'You have not made any previous orders!';
$_['text_order_step_1']          = 'Order placed';
$_['text_order_step_2']          = 'Confirmed';
$_['text_order_step_3']          = 'Shipping';
$_['text_order_step_4']          = 'Completed';
$_['text_filter_all']            = 'All';
$_['text_filter_processing']     = 'Processing';
$_['text_filter_shipping']       = 'Shipping';
$_['text_filter_complete']       = 'Completed';
$_['text_filter_cancelled']      = 'Cancelled';
$_['text_order_product']         = 'Product in order';
$_['text_quantity']              = 'Quantity';
$_['text_other_products']        = 'other products';
$_['text_order_number']          = 'Order';
$_['text_date_ordered']          = 'Date ordered';
$_['text_product_count']         = 'Products';
$_['text_status']                = 'Status';
$_['text_order_total']           = 'Order total';

// Column
$_['column_order_id']            = 'Order ID';
$_['column_customer']            = 'Customer';
$_['column_product']             = 'Product';
$_['column_product_total']       = 'No. of Products';
$_['column_status']              = 'Status';
$_['column_total']               = 'Total';
$_['column_date_added']          = 'Date Added';
$_['column_quantity']            = 'Quantity';
$_['column_price']               = 'Price';
$_['column_comment']             = 'Comment';
$_['column_action']              = 'Action';

// Error
$_['error_reorder']              = 'The product you are trying to purchase again has some issues. Please check the error messages shown below.';

$_['button_delete_order'] = 'Delete order';
$_['text_delete_order_confirm'] = 'Delete this order while it is awaiting shop confirmation?';
$_['text_delete_order_success'] = 'The order has been deleted.';
$_['error_delete_order'] = 'This order cannot be deleted. The shop may have already confirmed it.';
$_['button_cancel_order'] = 'Cancel order';
$_['text_order_status'] = 'Order status';
$_['text_cancel_order_confirm'] = 'Cancel this order while it is awaiting shop confirmation?';
$_['text_cancel_order_success'] = 'The order has been cancelled.';
$_['text_history_pagination'] = 'History %d–%d of %d updates';
$_['button_delete_cancelled_order'] = 'Delete order';
$_['text_delete_cancelled_order_confirm'] = 'Permanently delete this cancelled order? This action cannot be undone.';
$_['text_delete_cancelled_order_success'] = 'The order has been deleted.';
$_['error_delete_cancelled_order'] = 'Only a cancelled order belonging to your account can be deleted.';
$_['error_cancel_order'] = 'This order cannot be cancelled. The shop may have already confirmed it.';
$_['error_cancel_order_session'] = 'Your session has expired. Reload the page and try again.';
$_['error_cancel_order_method'] = 'The cancellation request is invalid. Reload the page and try again.';
$_['error_cancel_order_not_found'] = 'This order was not found for the current account.';
$_['error_cancel_order_status'] = 'The order is not awaiting confirmation (status ID: %d), so it cannot be cancelled.';
$_['error_cancel_order_target'] = 'The store has no valid cancellation status configured.';
$_['text_customer_cancelled_order'] = 'The customer requested to cancel this order.';
