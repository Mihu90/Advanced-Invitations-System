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
  Ultima modificare a codului : 20.05.2012 14:47
 */

$l['advinvsys_modname'] = 'Advanced Invitations System';
$l['advinvsys_close'] = 'Close';
$l['advinvsys_delete'] = 'Delete';
$l['advinvsys_edit'] = 'Edit';
$l['advinvsys_invs'] = ' invitation(s)';
$l['advinvsys_confirmation_page'] = 'Confirmation Page';
$l['advinvsys_confirmation_mess'] = 'Are you sure that you want to do this?';
// Butoane
$l['advinvsys_button_yes'] = 'Yes';
$l['advinvsys_button_no'] = 'No';
$l['advinvsys_button_submit'] = 'Submit';
$l['advinvsys_button_reset'] = 'Reset';
$l['advinvsys_button_continue'] = 'Continue';
// Erori comune
$l['advinvsys_errors_invalidreq'] = 'Invalid request.';
$l['advinvsys_errors_invalid_id'] = 'Invalid ID.';
$l['advinvsys_errors_missing_fields'] = 'There is one or more missing fields.';
// Task-uri
$l['advinvsys_task_ran'] = 'Advanced Invitations System task ran successfully.';
$l['advinvsys_task_givecredittype'] = 'Give Credits';
$l['advinvsys_task_givecredit'] = 'A total of {1} members have received invitations in exchange for the time spent online.';
// Tab-uri
$l['advinvsys_tabs_groups'] = 'Invitations';
$l['advinvsys_tabs_groups_maxinv'] = 'Maximum Invitations';
$l['advinvsys_tabs_groups_maxinv_desc'] = 'This option allows you to set a number who represent the maximum invitations for users that are in this group. Type \'-1\' if you don`t want to set a limit (unlimited).';
// Ajutor
$l['advinvsys_help_log_title'] = 'Help';
$l['advinvsys_help_log_content'] = 'With conditions you can filter the logs that you want to prune. The available fields are: Username, Type, Date and Date. You can add as many conditions as you wish by clicking on the add button in the first row of the conditions table. You can also remove any unwanted condition by clicking on the remove button on each condition row.<br/><br/>
Each condition has three parts:<br/><br/>
<b>Field</b>: the name of the field that holds the information you want to check. Every field has a specific type that can change the context of the test operator and the value.
<ul>
	<li>Username: text</li>
	<li>Type: text</li>
	<li>Data: text</li>
	<li>Date: date</li>
</ul><br/>
<b>Test</b>: the operator you want to use to test a chosen field for the given value. Tests have a different purpose for the different field types.
<ul>
<li>text<ul>
	<li>equal / not equal: test if field has the same value as the given value.</li>
	<li>is null / is not null: test if the field has the value of NULL or not.</li>
	<li>is empty / is not empty: test if the field is empty or not (= no value).</li>
	<li>lower than / greater than: test if the value of the field is lower or higher in the alphabet than the given value.</li>
	<li>in / not in: test if the value of the field is included in the give list of values (comma separated). If you would like to include a comma in your value you need enclose the value in quotes.</li>
	<li>like / not like: test if field value matches the given pattern. You can use the asterisk sign (*) for a wildcart. For example: abc*e</li>
</ul></li>
<li>date<ul>
	<li>equal / not equal: not really helpfull for dates. Use lower than / greater than instead.</li>
	<li>is null / is not null: test if the field has the value of NULL or not.</li>
	<li>is empty / is not empty: test if the field is empty or not (= no value).</li>
	<li>lower than / greater than: test if field is before or after the given date value.</li>
	<li>in / not in: no significant purpose for a date.</li>
	<li>like / not like: no significant purpose for a date.</li>
</ul></li>
</ul><br/>
<b>Value</b>: the value you want to test for. Some field types can have different types of values.
<ul>
	<li>text: <ul>
		<li>equal / not equal: a string value (for example: abcd or efgh)</li>
		<li>is null / is not null: no value</li>
		<li>is empty / is not empty: no value</li>
		<li>lower than / greater than: a string value (for example: abcd or efgh)</li>
		<li>in / not in: a list of string values (for example: abcd,efgh,\"abcd,efgh\").</li>
		<li>like / not like: a pattern (for example: abc*fgh)</li>
	</ul></li>
	<li>date: <ul>
		<li>A date can have two types of format:<ul>
			<li>absolute date: a date formatted as dd-mm-YYYY or mm/dd/YYYY. Not really helpfull because the reminder will only work for one specific date.</li>
			<li>relative date: a textual representation in English of a relative date. For example: -1 week (= one week ago), +2 week (= 2 weeks from now), today (= today). Please read the <a href=\"http://php.net/manual/en/function.strtotime.php\">strtotime()</a> documentation for more information about supported formats.</li>
		</ul></li>
		<li>equal / not equal: a date value (for example: 02-12-2011)</li>
		<li>is null / is not null: no value</li>
		<li>is empty / is not empty: no value</li>
		<li>lower than / greater than: a date value (for example: +1 month)</li>
		<li>in / not in: a list of dates (for example: 02-12-2011,04-12-2011).</li>
		<li>like / not like: not applicable</li>
	</ul></li>
</ul>';
// Pagina principala
$l['advinvsys_main'] = 'Main Page';
$l['advinvsys_main_description'] = 'Here you can find various maintenance tools.';
$l['advinvsys_main_income_title'] = 'Forum Rules';
$l['advinvsys_main_income_add'] = 'Add Forum Rule';
$l['advinvsys_main_income_none'] = 'Could not find any income rules.';
$l['advinvsys_main_income_name'] = 'Rule Name';
$l['advinvsys_main_income_name_desc'] = 'Enter the name of the rule.';
$l['advinvsys_main_income_desc'] = 'Rule Description';
$l['advinvsys_main_income_desc_desc'] = 'Enter a description of the rule.';
$l['advinvsys_main_income_type'] = 'Rule Type';
$l['advinvsys_main_income_type_desc'] = 'This rule will be avaible for the following action.';
$l['advinvsys_main_income_rate'] = 'Standard Rate';
$l['advinvsys_main_income_rate_desc'] = 'Enter the standard rate for the selected action. (Default : 1 invitation per action)';
$l['advinvsys_main_income_addi'] = 'Additional Rate';
$l['advinvsys_main_income_addi_desc'] = 'Enter the additional rate for <b id="additional_rate"></b>. (Default : 0 invitation)';
$l['advinvsys_main_income_forum'] = 'Forums';
$l['advinvsys_main_income_forum_desc'] = 'Select the forums affected by this rule. You are allowed to select multiple forums.';
$l['advinvsys_main_income_group'] = 'Groups';
$l['advinvsys_main_income_group_desc'] = 'Select the usergroups affected by this rule. You are allowed to select multiple groups.';
$l['advinvsys_main_income_enable'] = 'Is Enabled?';
$l['advinvsys_main_income_enable_desc'] = 'Is this rule enabled?';
$l['advinvsys_main_income_type'] = 'Rule Type';
$l['advinvsys_main_income_enabled'] = 'Rule Active?';
$l['advinvsys_main_income_options'] = 'Options';
$l['advinvsys_main_income_added'] = 'A new rule has been successfully added.';
$l['advinvsys_main_income_added_logt'] = 'New Rule';
$l['advinvsys_main_income_added_log'] = 'A new rule (with id = {1}) has been successfully added.';
$l['advinvsys_main_income_deleted'] = 'The selected rule has been deleted successfully.';
$l['advinvsys_main_income_notexist'] = 'The selected rule does not exist in your database.';
$l['advinvsys_main_income_deleteconfirm'] = 'Are you sure you want to delete the selected rule?';
$l['advinvsys_main_income_deleted_logt'] = 'Delete Rule';
$l['advinvsys_main_income_deleted_log'] = 'The rule with id = {1} has been deleted successfully.';
$l['advinvsys_main_income_edittitle'] = 'Edit Rule';
$l['advinvsys_main_income_invalid'] = 'Invalid rule.';
$l['advinvsys_main_income_edited'] = 'The selected rule has been edited successfully.';
$l['advinvsys_main_income_edited_logt'] = 'Edit Rule';
$l['advinvsys_main_income_edited_log'] = 'The rule with id = {1} has been successfully edited.';
// Reset
$l['advinvsys_main_reset'] = 'Reset Invitations';
$l['advinvsys_main_reset_per_page'] = 'Per page';
$l['advinvsys_main_reset_per_page_desc'] = 'Enter the number of users you want to reset per page.';
$l['advinvsys_main_reset_invs'] = 'Invitations';
$l['advinvsys_main_reset_invs_desc'] = 'Number of invitations everyone will be reset to.';
$l['advinvsys_main_reset_action'] = 'You have successfully reset users\'s invitations.';
$l['advinvsys_main_reset_done'] = 'You have successfully reset users\'s invitations.';
// Edit User
$l['advinvsys_main_edituser'] = 'Edit User';
$l['advinvsys_main_edituser_uid'] = 'User ID';
$l['advinvsys_main_edituser_uid_desc'] = 'Enter the user id of the user you want to edit.';
$l['advinvsys_main_edituser_log'] = 'User with uid = {1} has now only {2} invitations.';
$l['advinvsys_main_invalid_edituser'] = 'Invalid user.';
$l['advinvsys_main_user_edited'] = 'The selected user has been edited successfully.';
$l['advinvsys_main_edituser_points'] = 'Edit invitations';
$l['advinvsys_main_edituser_points_desc'] = 'Enter the number of invitations you want the selected user to have.';
$l['advinvsys_main_continue'] = 'Click Continue to proceed.';
$l['advinvsys_main_reset_done'] = 'You have successfully reset users\'s invitations.';
$l['advinvsys_main_reset_confirm'] = 'Are you sure you want to reset everyone\'s invitations?';
// Pagina cu jurnale
$l['advinvsys_logs'] = 'Logs';
$l['advinvsys_logs_description'] = 'Here you can manage log entries.';
$l['advinvsys_logs_user'] = 'Username';
$l['advinvsys_logs_type'] = 'Type';
$l['advinvsys_logs_mess'] = 'Data';
$l['advinvsys_logs_info'] = 'Information';
$l['advinvsys_logs_date'] = 'Date';
$l['advinvsys_logs_acts'] = 'Actions';
$l['advinvsys_logs_without'] = 'There are no log entries at this moment.';
$l['advinvsys_logs_deleted'] = 'Your log (with id {1}) has been deleted successfully.';
$l['advinvsys_logs_prune'] = 'Prune Logs';
$l['advinvsys_logs_prune_field'] = 'Field';
$l['advinvsys_logs_prune_test'] = 'Test';
$l['advinvsys_logs_prune_value'] = 'Value';
$l['advinvsys_logs_noconditions'] = 'You do not set any conditions. Please set at least one!';
$l['advinvsys_logs_prune_one'] = 'A number of one log entry has been pruned successfully.';
$l['advinvsys_logs_prune_more'] = 'A number of {1} log entries have been pruned successfully.';
// Pagina cu statisticile
$l['advinvsys_stats'] = 'Statistics';
$l['advinvsys_stats_description'] = 'View your forum statistics.';
$l['advinvsys_stats_error'] = 'Could not gather any data.';
$l['advinvsys_stats_richest'] = 'Richest Users';
$l['advinvsys_stats_user'] = 'User';
$l['advinvsys_stats_invs'] = 'Invitations';
$l['advinvsys_stats_ref'] = 'Members Referred';
$l['advinvsys_stats_refs'] = 'Users with the Most Referrals';
// Pagina cu modulele aplicatiei
$l['advinvsys_modules'] = 'Modules';
$l['advinvsys_module'] = 'Module';
$l['advinvsys_modules_description'] = 'Here you can manage all modules for "Advanced Invitations System" plugin.';
$l['advinvsys_modules_without'] = 'There are no modules for this modification at this time.';
$l['advinvsys_modules_invalid'] = 'The selected module does not exist.';
$l['advinvsys_modules_activated'] = 'The selected module has been activated successfully.';
$l['advinvsys_modules_deactivated'] = 'The selected module has been deactivated successfully.';
$l['advinvsys_modules_delete_success'] = 'The file {1} was deleted successfully.';
$l['advinvsys_modules_delete_error'] = 'The file {1} cannot be deleted from your server.';
$l['advinvsys_modules_development'] = "Read 'Development' section from ReadMe file if you want to get more information about development.";
$l['advinvsys_import_a_module'] = 'Import a Module';
$l['advinvsys_import_from'] = 'Import from';
$l['advinvsys_import_file'] = 'Local file';
$l['advinvsys_import_url'] = 'URL';
$l['advinvsys_import_from_description'] = 'Select a file to import. You can either import the module file from your computer or from a URL.';
$l['advinvsys_import_module'] = 'Import Module';
$l['advinvsys_import_error_make'] = 'At least one error occurred while writing data to disk.';
$l['advinvsys_import_missing_url'] = 'Please enter a module to import it.';
$l['advinvsys_import_local_file'] = 'Could not open the local file. Does it exist? Please check and try again.';
$l['advinvsys_import_uploadfailed'] = 'Upload failed. Please try again.';
$l['advinvsys_import_uploadfailed_detail'] = 'Error details: ';
$l['advinvsys_import_uploadfailed_php1'] = 'PHP returned: Uploaded file exceeded upload_max_filesize directive in php.ini.  Please contact your forum administrator with this error.';
$l['advinvsys_import_uploadfailed_php2'] = 'The uploaded file exceeded the maximum file size specified.';
$l['advinvsys_import_uploadfailed_php3'] = 'The uploaded file was only partially uploaded.';
$l['advinvsys_import_uploadfailed_php4'] = 'No file was uploaded.';
$l['advinvsys_import_uploadfailed_php6'] = 'PHP returned: Missing a temporary folder.  Please contact your forum administrator with this error.';
$l['advinvsys_import_uploadfailed_php7'] = 'PHP returned: Failed to write the file to disk.  Please contact your forum administrator with this error.';
$l['advinvsys_import_uploadfailed_phpx'] = 'PHP returned error code: {1}.  Please contact your forum administrator with this error.';
$l['advinvsys_import_uploadfailed_lost'] = 'The file could not be found on the server.';
$l['advinvsys_import_uploadfailed_nocontents'] = 'MyBB could not find the module with the file you uploaded. Please check the file if is correct and is not corrupt.';
$l['advinvsys_import_success'] = 'The selected module has been imported successfully.';
$l['advinvsys_upgrades_title'] = 'Upgrades';
$l['advinvsys_upgrades_name'] = 'Upgrade';
$l['advinvsys_upgrades_controls'] = 'Controls';
$l['advinvsys_upgrades_run'] = 'Run';
$l['advinvsys_upgrades_no'] = 'No upgrades found.';
$l['advinvsys_upgrades_confirm'] = 'Are you sure you want to run the selected upgrade file?';
$l['advinvsys_upgrades_ran'] = 'Upgrade script ran successfully.';
$l['advinvsys_upgrades_error'] = 'An error occured the upgrade process.';
// Pagina cu echipa
$l['advinvsys_about'] = 'About us';
$l['advinvsys_about_description'] = 'On this page you can find more about us and about this modification.';
$l['advinvsys_about_team'] = 'Team';
$l['advinvsys_about_team_name'] = 'Nickname';
$l['advinvsys_about_team_contribution'] = 'Role';
$l['advinvsys_about_license'] = 'License Agreement';
$l['advinvsys_about_license_version'] = 'Version : {1}';
$l['advinvsys_about_license_text'] = '<small>1. The original version of this product is absolutely <b>FREE</b> and cannot be sold.<br>
2. The author of this product cannot be held responsible in any conditions, for any damages caused as a result of its use.<br>
3. You are free to distribute translations of the original version, without to receive the written consent of the author, but without any modification of the product itself.<br>
4. You are free to use certain parts of this product, without to receive the written consent of the author, but you must to specify the following notice, in your own code: "This part of code belong to <a href="http://mybb.ro" target="_blank">http://www.mybb.ro</a>".<br>
5. You are free to change this product for compatibility with your forum, without to receive the written consent of the author, but you cannot release or redistribute a modified version only in the following conditions:<br>
- you must to receive the written consent of the author;<br>
- you cannot use the original name of this product for your version;<br>
- you must provide the following notice in your own code: "The original belong to <a href="http://mybb.ro" target="_blank">http://www.mybb.ro</a>".<br>
6. You are not allowed to sell a modified version of this product.<br>
7. The author does not provide support for modified versions of this product.<br>
8. You can remove copyright link or author name but then you must know that you don`t have to request support on our website.<br>
9. This <i>LICENSE AGREEMENT</i> is strictly valid for the current version of this product.</small>';
$l['advinvsys_about_text_left'] = '<h3>Description</h3>
This modification is an <i>advanced invitations system</i> fully integrated with MyBB. Based on a credit system and with a lot of powerful features this is the ultimate way to get more users in your forum.
<h3>Requirements</h3>
<ul type="square">
<li>at least MyBB 1.6.0</li>
<li>PHP 5.2.0 or greater</li>
<li>MySQL as database</li>
</ul>
<h3>Key Features List</h3>
<div><img src="../images/advinvsys/email.png" style="vertical-align: top;">
<h4 style="display: inline;">Customized Emails:</h4></div>
<ul type="square">
<li>Custom email subject.</li>
<li>Custom email body.</li>
<li>Commands like {INVITATION_CODE}, {REGISTRATION_URL} and more.</li>
<li>Interpreted email body (using MyCode).</li>
<li>User custom message if it\'s enabled.</li>
</ul>
<div><img src="../images/advinvsys/keys.png" style="vertical-align: top;">
<h4 style="display: inline;">Advanced Keys:</h4></div>
<ul type="square">
<li>Give credits based on:
<ul type="circle">
<li>Reputation</li>
<li>Posts</li>
<li>New registration</li>
<li>Voting</li>
</ul>
</li>
<li>Users can buy credits using Paypal or SMS - Fortumo.</li>
<li>Users can donate credits to other users.</li>
<li>Amount of credits to receive depending on method.</li>
<li>Max credits that regular users can have.</li>
<li>Allow users to have unlimitied credits.</li>
<li>Keys are generated with an unique ID can\'t be copied.</li>
<li>Days while keys are valid. (Default : 7)</li>
<li>Option to add credits for expired keys.</li>
<li>Credits received on registration.</li>
</ul>
<div><img src="../images/advinvsys/extra.png" style="vertical-align: top;">
<h4 style="display: inline;">Extra Features:</h4></div>
<ul type="square">
<li>Permissions for invitations and unlimitied Credits.</li>
<li>Force key, optional key or disabled keys on registration.</li>
<li>Invitations logger.</li>
<li>Option to show number of members invited on profile.</li>
<li>Top invitees on Admin CP.</li>
<li>Give additional credits to specific members using Admin CP.</li>
</ul>
';
$l['advinvsys_about_donate_title'] = 'Donate';
$l['advinvsys_about_donate_body'] = 'This plugin will always be free but if you like it and want to support the further development of this, feel free to donate.<br><center><b>Thank you!</b></center>';
?>
