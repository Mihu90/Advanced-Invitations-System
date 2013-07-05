<?php
/*
  @author     : Surdeanu Mihai ;
  @date       : 20 mai 2012 ;
  @version    : 1.1.0 ;
  @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
  @description: Modificare permite introducerea unui sistem de invitatii pe forumul tau!
  @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
  @copyright  : Licenta speciala. Pentru mai multe detalii te rugam sa citesti sectiunea Licenta din cadrul fisierului
  ReadME.pdf care poate fi gasit in acest pachet. Iti multumim pentru intelegere!
  ====================================
  Ultima modificare a codului : 20.05.2012 13:43
 */

// Extra
$l['advinvsys_mod_name'] = 'Advanced Invitations System';
$l['advinvsys_usercp_my'] = 'My Invitations';
$l['advinvsys_profile_ins'] = 'Invitations';
$l['advinvsys_profile_ref'] = 'Members Referred';
$l['advinvsys_profile_send'] = 'Send Invites';
// Tabel cu invitatii deja trimise (created)
$l['advinvsys_created_title'] = 'Invitations Created';
$l['advinvsys_created_email'] = 'Email';
$l['advinvsys_created_code'] = 'Invitation Code';
$l['advinvsys_created_date'] = 'Created';
$l['advinvsys_created_expr'] = 'Expiration';
$l['advinvsys_created_acts'] = 'Actions';
$l['advinvsys_created_getback'] = 'Get Back';
$l['advinvsys_created_resend'] = 'Resend';
$l['advinvsys_created_noinvs'] = 'You do not have sent any invitations.';
// Optiunea de a reprimi o invitatie trimisa
$l['advinvsys_giveback_err1'] = 'The invitation specified does not exist in the database or she has not expired yet.';
$l['advinvsys_giveback_err2'] = 'The invitation specified is not yours.';
$l['advinvsys_giveback_succ1'] = 'The recovery was successful. Now you have with one invitation more.';
$l['advinvsys_giveback_succ2'] = 'The recovery was successful, but your number of invites have not increased.';
// Optiunea de a retrimite o invitatie
$l['advinvsys_resend_err1'] = 'The invitation specified does not exist in the database or she can be resended after a period of time.';
$l['advinvsys_resend_err2'] = 'No email address is attached to this invitation.';
$l['advinvsys_resend_succ'] = 'The invitation to {1} was successfully resend.';
// Formular de trimitere a unei invitatii prin email
$l['advinvsys_invite_title'] = 'Generate Invitation';
$l['advinvsys_invite_email'] = 'Email :';
$l['advinvsys_invite_email_desc'] = 'Enter an email address where do you want to send an invitation. Leave empty if you don`t want to send to an email address.';
$l['advinvsys_invite_mess'] = 'Message :';
$l['advinvsys_invite_mess_desc'] = 'Enter a message for the user who will read the invitation email.';
$l['advinvsys_invite_submit'] = 'Send';
$l['advinvsys_invite_preview'] = 'Preview';
$l['advinvsys_invite_invalidemail'] = 'Invalid email address. Please type a valid one.';
$l['advinvsys_invite_selfinvite'] = 'You can\'t send an invitation to yourself.';
$l['advinvsys_invite_multiple'] = 'The entered e-mail address already have been invited.';
$l['advinvsys_invite_gived'] = 'You have successfully send {1} invitation. Now you have only {2} invitation(s).';
$l['advinvsys_invite_log'] = 'Sent an invitation to {1}.';
$l['advinvsys_invite_log_type'] = 'Give Invite';
// Formular de cumparare a unor invitatii
$l['advinvsys_buy_title'] = 'Buy Invitations';
$l['advinvsys_buy_gateway'] = 'Payment Gateway :';
$l['advinvsys_buy_gateway_desc'] = 'Select a payment gateway that you want to use in order to buy some invites.';
$l['advinvsys_buy_number'] = 'Number of Invitations :';
$l['advinvsys_buy_number_desc'] = 'Select how many invites do you want to buy.';
$l['advinvsys_buy_price'] = 'Price :';
$l['advinvsys_buy_price_desc'] = 'You need to pay the following amount to do this action.';
$l['advinvsys_buy_invalidgateway'] = 'Invalid gateway!';
$l['advinvsys_buy_invalidprice'] = 'You cannot obtain invitations for free using this form!';
$l['advinvsys_buy_notenough'] = 'You do not have enough points to make this conversion.';
$l['advinvsys_buy_smscountry'] = '<center>You are not allowed to use Fortumo for this country!</center>';
$l['advinvsys_buy_succes'] = 'You have successfully bought {1} invitation(s). Now you have a number of {2} invitation(s).';
$l['advinvsys_buy_np_logt'] = 'New Invitation';
$l['advinvsys_buy_np_log'] = 'Used NewPoints gateway to convert {1} point(s) in {2} invitation(s).';
// Formular de trimitere a unor invitatii catre un alt membru de pe forum
$l['advinvsys_send_title'] = 'Donate Invitations';
$l['advinvsys_send_user'] = 'Username : (<em>*</em>)';
$l['advinvsys_send_user_desc'] = 'Enter the user name of the user you want to send a donation.';
$l['advinvsys_send_amount'] = 'Amount : (<em>*</em>)';
$l['advinvsys_send_amount_desc'] = 'Enter the amount of invitations you want to send to the user.';
$l['advinvsys_send_amount_have'] = 'You have: <b>{1}</b> invitations.';
$l['advinvsys_send_reason'] = 'Reason :';
$l['advinvsys_send_reason_desc'] = 'Enter a reason for the donation.';
$l['advinvsys_send_submit'] = 'Donate';
$l['advinvsys_send_reset'] = 'Reset';
$l['advinvsys_send_donated'] = 'You have successfully donated {1} invitation(s) to the selected user. Now you have only {2} invitation(s).';
$l['advinvsys_send_log_type'] = 'New Donation';
$l['advinvsys_send_log'] = 'Has donated {1} invitation(s) to the user {2}.';
$l['advinvsys_send_pmsubject'] = 'New Donation';
$l['advinvsys_send_pmmessage'] = 'Hello, I\'ve just sent you a donation of [b]{1}[/b] invite(s).';
$l['advinvsys_send_pmmessage_reason'] = 'Hello, I\'ve just sent you a donation of [b]{1}[/b] invite(s).\nReason:[quote]{2}[/quote]';
$l['advinvsys_send_selfdonate'] = 'You can\'t send a donation to yourself.';
$l['advinvsys_send_erramount'] = 'You have entered an invalid amount of invitations.';
$l['advinvsys_send_erruser'] = 'You have entered an invalid user name.';
$l['advinvsys_send_notenough'] = 'You don\'t have enough invitations to do this action. You have only: {1}';
// Inregistrare
$l['advinvsys_reg_title'] = 'Registration Key'; 
$l['advinvsys_reg_explain'] = "Please type an invitation code that you have got via e-mail. You cannot register without an invitation!"; 
$l['advinvsys_reg_wrong_answer'] = 'The invitation code is invalid. You can leave it out.'; 
$l['advinvsys_reg_maxattempts'] = 'You have been locked out of the invitation system. You will be able to try again after {1} minutes.';
$l['advinvsys_reg_correct_answer'] = 'The invitation code exists in our database.'; 
// Fortumo-SMS Gateway
$l['advinvsys_fortumo_noservice'] = 'SMS Gateway has been disabled or there is a problem with service id.';
$l['advinvsys_fortumo_nousername'] = 'The username that you chose does not exist in our database!';
$l['advinvsys_fortumo_novalid'] = 'Your email address isn`t a valid one.';
$l['advinvsys_fortumo_wrongmessage'] = 'Wrong SMS text message.';
$l['advinvsys_fortumo_pm_subject'] = 'Hi {1}!';
$l['advinvsys_fortumo_mail'] = 'Invitation Code';
$l['advinvsys_fortumo_log0'] = 'New Invitation';
$l['advinvsys_fortumo_log1'] = 'This user has bought a number of {1} invitations using {2}.';
$l['advinvsys_fortumo_log2'] = 'A new invitation has been sent to {1} by system. (Payment Gateway: {2})';
$l['advinvsys_fortumo_payment_succes'] = 'You have successfully received items purchased! Thank you for this payment!';
$l['advinvsys_fortumo_payment_succes1'] = 'You have successfully bought an invitation code ({1}). Thank you for this payment!';
$l['advinvsys_fortumo_buy_err1'] = 'Service or signature specified are not correct!';
$l['advinvsys_fortumo_buy_err2'] = 'There was an error processing data from server.';
$l['advinvsys_fortumo_buy_err3'] = 'You cannot pay using Fortumo because this system it is not available for your country!';
$l['advinvsys_fortumo_show_key'] = 'Keyword :';
$l['advinvsys_fortumo_show_code'] = 'ShortCode :';
$l['advinvsys_fortumo_show_ctr'] = 'Country :';
$l['advinvsys_fortumo_show_chs'] = 'Email or User ID';
// Acordare de credite (invitatii)
$l['advinvsys_income_logt'] = 'Give Credits';
$l['advinvsys_income_log0'] = 'The user with {1} have {2} a number of {3} invites.';
$l['advinvsys_income_log0_r'] = 'received';
$l['advinvsys_income_log0_t'] = 'been taken';
// Texte legate de statistici
$l['advinvsys_stats_total'] = 'Invitations:';
$l['advinvsys_stats_perusers'] = 'Invitations per member:';
// Erori des intalnite
$l['advinvsys_errors_invalidreq'] = 'Invalid request.';
?>