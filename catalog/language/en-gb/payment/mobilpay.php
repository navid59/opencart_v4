<?php
// Heading
$_['heading_title']    = 'NETOPIA Payments'; // OK

// Text
$_['text_option_card']      = 'Online payment by any kind of Debit / Credit Card';   // ok
$_['text_payment']          = 'Order registerd and redirecting to NETOPIA Payment to pay the Order fee.';    // ok
$_['text_payment_paid']     = 'Order is paid.';    // ok
$_['text_payment_pendding'] = 'Order status change to pendding.';    // ok
$_['text_payment_failed']   = 'Order status change to failed.';    // ok
$_['text_payment_denied']   = 'Order status change to denied.';    // ok
$_['text_redirecting']      = 'Redirecting to Netopia Payment';    // ok
$_['text_payment_unknown_ipn_msg']  = '  ---  ';    // ok

// Errors
$_['error_redirect'] = 'Imi pare rau, nu putem sa redirectionam in pagina de plata NETOPIA payments';
$_['error_redirect_problem_note'] = 'Asigura-te ca ai completat configurari in setarii,pentru mediul sandbox si live!. Citeste cu atentie instructiunile din manual!';
$_['error_redirect_problem_contact'] = 'Ai in continuare probleme? Trimite-ne doua screenshot-uri la <a href="mailto:implementare@netopia.ro">implementare@netopia.ro</a>, unul cu setarile metodei de plata din adminul.';
$_['error_redirect_problem_unknown'] = 'There is a problem, the server is not response to request or Payment URL is not generated';

$_['error_redirect_code_401'] = 'Sa pare ca datele de authentificare introduse nu sunt corecte sau lipsesc.';
$_['error_redirect_code_99'] = 'Sa pare ca datele de POS introduse ( POS ) nu sunt corecte sau lipsesc.';


// Messages
$_['message_redirect'] = 'Iti multumim pentru comanda. Te redirectionam catre NETOPIA payments pentru plata';

// Payment failure
$_['ntp_failure_heading_title'] = 'Failed Payment!';
$_['ntp_failure_text_basket']   = 'Shopping Cart';
$_['ntp_failure_text_home'] = 'Shopping Cart';
$_['ntp_failure_text_checkout'] = 'Checkout';
$_['ntp_failure_text_failure']  = 'Failed Payment';
$_['ntp_failure_text_message']  = '<p>There was a problem processing your payment and the order did not complete.</p>

<p>Possible reasons are:</p>
<ul>
  <li>Cancel payment</li>
  <li>Insufficient funds</li>
  <li>Verification failed</li>
</ul>

<p>Please try to order again using a different payment method.</p>

<p>If the problem persists please <a href="%s">contact us</a> with the details of the order you are trying to place.</p>
';

// Payment Success
$_['ntp_success_heading_title'] = 'Your order has been placed!';
$_['ntp_success_text_basket']   = 'Shopping Cart';
$_['ntp_success_text_home'] = 'Shopping Cart';
$_['ntp_success_text_checkout'] = 'Checkout';
$_['ntp_success_text_success']  = 'Success';
$_['ntp_success_text_customer'] = '<p>Your order has been successfully processed!</p><p>You can view your order history by going to the <a href="%s">my account</a> page and by clicking on <a href="%s">history</a>.</p><p>If your purchase has an associated download, you can go to the account <a href="%s">downloads</a> page to view them.</p><p>Please direct any questions you have to the <a href="%s">store owner</a>.</p><p>Thanks for shopping with us online!</p>';
$_['ntp_success_text_guest']    = '<p>Your order has been successfully processed!</p><p>Please direct any questions you have to the <a href="%s">store owner</a>.</p><p>Thanks for shopping with us online!</p>';