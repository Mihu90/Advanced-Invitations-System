<?php

/*
  @author     : Surdeanu Mihai ;
  @date       : 8 august 2012 ;
  @version    : 1.1.1 ;
  @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
  @description: Modificare permite introducerea unui sistem de invitatii pe forumul tau!
  @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
  @copyright  : Licenta speciala. Pentru mai multe detalii te rugam sa citesti sectiunea Licenta din cadrul fisierului
  ReadME.pdf care poate fi gasit in acest pachet. Iti multumim pentru intelegere!
  ====================================
  Ultima modificare a codului : 08.08.2012 14:36
 */

// Poate fi accesat direct fisierul?
if (!defined("IN_MYBB")) {
    die("This file cannot be accessed directly.");
}

// se incarca si fisierul prin care vedem ce versiune se foloseste
require_once MYBB_ROOT . "inc/plugins/advinvsys/system_version.php";

// se incarca si fisierul cu clasa de dezvoltare
require_once MYBB_ROOT . "inc/plugins/advinvsys/classes/class_develop.php";

global $AISDevelop;
$AISDevelop = new AISDevelop();

// Carlige de legatura cu core-ul MyBB
/* Interfata cu utilizatorul */
$plugins->add_hook('global_start', 'advinvsys_plugins_start');
$plugins->add_hook('datahandler_user_validate', 'advinvsys_user_validate');
$plugins->add_hook('member_profile_end', 'advinvsys_profile');
$plugins->add_hook('member_register_agreement', 'advinvsys_register_agreement');
$plugins->add_hook('member_register_end', 'advinvsys_register_end');
$plugins->add_hook('member_do_register_end', 'advinvsys_do_register');
$plugins->add_hook('usercp_menu', 'advinvsys_menu_built', 30);
$plugins->add_hook('usercp_start', 'advinvsys_main_page');
$plugins->add_hook('stats_end', 'advinvsys_stats');
$plugins->add_hook('xmlhttp', 'advinvsys_xmlhttp');
/* Panoul de administrare */
$plugins->add_hook('admin_load', 'advinvsys_admin_load_hook');
$plugins->add_hook("admin_config_menu", "advinvsys_admin_menu");
$plugins->add_hook("admin_config_action_handler", "advinvsys_admin_action_handler");
$plugins->add_hook("admin_user_groups_edit_graph_tabs", "advinvsys_admin_groups_edit_tab");
$plugins->add_hook("admin_user_groups_edit_graph", "advinvsys_admin_groups_edit");
$plugins->add_hook("admin_user_groups_edit_commit", "advinvsys_admin_groups_edit_save");

// se incarca si fisierul prin care se acorda credite
require_once MYBB_ROOT . "inc/plugins/advinvsys/function_credits.php";

// Informatii legate de modificare
function advinvsys_info() {
    global $db;
    
    $array = array(
        "name"          => "Advanced Invitations System",
        "description"   => "Based on a credit system and with a lot of powerful features this is the ultimate way to get more users in your forum.",
        "website"       => "http://mybb.ro",
        "author"        => "MyBB Rom&#226;nia Team",
        "authorsite"    => "http://mybb.ro",
        "version"       => AIS_VERSION,
        "release"       => "08.08.2012",
        "compatibility" => "16*"
    );
    
    if (advinvsys_compare_version())
        $array['description'] .= "<div style='float:right; color:green'>[PHP : " . PHP_VERSION ."; DB : {$db->type}]</div>";
    else
        $array['description'] .= "<div style='float:right; color:red'>[PHP : " . PHP_VERSION ."; DB : {$db->type}]</div>";
        
    return $array;
}

// Functia verifica daca versiunea de PHP este cea buna...
function advinvsys_compare_version() {
    if (version_compare(PHP_VERSION, AIS_MIN_PHP) >= 0)
        return true;
    else
        return false;
}

// Functia de instalare a modificarii
function advinvsys_install() {
    global $db;
    
    // inainte de a crea eventuale tabele vom vedea ce colocatie avem...
    $collation = $db->build_create_table_collation();
    
    // daca tabelul cu log-uri exista atunci nu se va mai crea
    if (!$db->table_exists("advinvsys_codes")) {
        // daca nu exista se purcede la crearea lui
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "advinvsys_codes` (
            `cid` bigint(30) UNSIGNED NOT NULL auto_increment,
            `did` bigint(30) UNSIGNED NOT NULL default '1',
            `email` varchar(64) NOT NULL default '',
            `code` varchar(32) NOT NULL default '',
            `date` bigint(30) UNSIGNED NOT NULL default '0',
            PRIMARY KEY  (`cid`), KEY(`code`)
                ) ENGINE=MyISAM{$collation}");
    }
    if (!$db->table_exists("advinvsys_logs")) {
        // daca nu exista se purcede la crearea lui
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "advinvsys_logs` (
            `lid` bigint(30) UNSIGNED NOT NULL auto_increment,
            `uid` bigint(30) UNSIGNED NOT NULL default '1',
            `type` varchar(256) NOT NULL default '',
            `data` TEXT NOT NULL,
            `date` bigint(30) UNSIGNED NOT NULL default '0',
            PRIMARY KEY  (`lid`), KEY(`date`)
                ) ENGINE=MyISAM{$collation}");
    }
    if (!$db->table_exists("advinvsys_incomes")) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "advinvsys_incomes` (
            `iid` bigint(30) UNSIGNED NOT NULL auto_increment,
            `enabled` int(1) NOT NULL default '0',
            `name` varchar(100) NOT NULL default '',
            `type` varchar(64) NOT NULL default '',
            `description` text NOT NULL,
            `fid` text NOT NULL,
            `gid` text NOT NULL,
            `invitations` DECIMAL(30,2) UNSIGNED NOT NULL default '0',
            `additional` DECIMAL(30,2) UNSIGNED NOT NULL default '0',    
            PRIMARY KEY  (`iid`)
                ) ENGINE=MyISAM{$collation}");
    }
    
    // se mai adauga o noua coloana in tabela "users" din sistemul MyBB
    if (!$db->field_exists('invitations', 'users')) {
        // se va adauga doar daca campul nu exista in tabela
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `invitations` DECIMAL(30,2) UNSIGNED NOT NULL default '0';");
    }
    if (!$db->field_exists('last_timeonline', 'users')) {
        // se va adauga doar daca campul nu exista in tabela
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `last_timeonline` bigint(30) UNSIGNED NOT NULL default '0';");
    }
    if (!$db->field_exists('invitation_refer', 'users')) {
        // se va adauga doar daca campul nu exista in tabela
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `invitation_refer` bigint(30) UNSIGNED NOT NULL default '0';");
    }
    
    // se mai adauga o noua coloana in tabela "usergroups" din sistemul MyBB
    if (!$db->field_exists('max_invitations', 'usergroups')) {
        // se va adauga doar daca campul nu exista in tabela
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "usergroups` ADD `max_invitations` DECIMAL(30,2) NOT NULL default '-1.00';");
    }
    
    
    // se creaza un set de sabloane in baza de date
    $template_group = array(
        'prefix' => 'advinvsys',
        'title' => 'AIS'
    );
    
    // grupul este adaugat in baza de date
    $db->insert_query('templategroups', $template_group);
    
    // se adauga si eventualele sabloane ale modificarii	
    $templates = advinvsys_get_templates();
    
    // pentru fiecare sablon din vector se realizeaza introducerea lui in baza de date	
    foreach ($templates as $template) {
        $insert = array(
            "title" => $template['title'],
            "template" => $db->escape_string($template['template']),
            "sid" => "-2",
            "version" => "1600",
            "status" => "",
            "dateline" => TIME_NOW
        );
        $db->insert_query("templates", $insert);
    }
    
    // se creaza si un nou task
    $new_task = array(
        "title" => "Advanced Invitations System",
        "description" => "Gives you the possibility to create automated tasks.",
        "file" => "advinvsys_task",
        "minute" => '0',
        "hour" => '0',
        "day" => '*',
        "month" => '*',
        "weekday" => '0',
        "enabled" => '1',
        "logging" => '1'
    );
    // va rula dupa 10 minute de la data adaugarii in sistem
    $new_task['nextrun'] = intval(TIME_NOW + 600);
    
    $db->insert_query('tasks', $new_task);
}

// Functia care verifica daca modificarea e instalata
function advinvsys_is_installed() {
    global $db;
    // exista tabelele aplicatiei ?
    return ($db->table_exists('advinvsys_codes') && 
            $db->table_exists('advinvsys_logs')) ? true : false;
}

// Functia prin care se dezinstaleaza o modificare
function advinvsys_uninstall() {
    global $db;
    
    // daca tabela "advinvsys_codes" exista in baza de date atunci se sterge!
    if ($db->table_exists('advinvsys_codes')) {
        $db->drop_table('advinvsys_codes');
    }
    if ($db->table_exists('advinvsys_logs')) {
        $db->drop_table('advinvsys_logs');
    }
    if ($db->table_exists('advinvsys_incomes')) {
        $db->drop_table('advinvsys_incomes');
    }
    
    // se sterge coloana adaugata de modificare din tabela "users" ?
    if ($db->field_exists('invitations', 'users')) {
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` DROP `invitations`;");
    }
    if ($db->field_exists('last_timeonline', 'users')) {
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` DROP `last_timeonline`;");
    }
    if ($db->field_exists('invitation_refer', 'users')) {
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "users` DROP `invitation_refer`;");
    }
    // se sterge coloana adaugata de modificare din tabela "usergroups " ?
    if ($db->field_exists('max_invitations', 'usergroups')) {
        $db->write_query("ALTER TABLE `" . TABLE_PREFIX . "usergroups` DROP `max_invitations`;");
    }
    
    // se sterge grupul de sabloane
    $db->delete_query("templategroups", "prefix = 'advinvsys'");
    // se sterg sabloanele din baza de date
    $db->delete_query("templates", "title LIKE 'advinvsys_%'");
    // se sterge task-ul din sistem
    $db->delete_query("tasks", "file = 'advinvsys_task'");
}

// Functia de activare a modificarii
function advinvsys_activate() {
    global $mybb, $db, $AISDevelop;
    
    // se creaza grupul de setari
    $group = array(
        "gid" => "NULL",
        "name" => "advinvsys_group",
        "title" => "Advanced Invitations System",
        "description" => "Settings for the \"Advanced Invitations System\" plugin.",
        "disporder" => "1",
        "isdefault" => "no",
    );
    $db->insert_query("settinggroups", $group);
    $gid = $db->insert_id();
    
    // se creaza vectorul de setari ale modificarii
    $settings = advinvsys_get_settings(array());
    
    $AISDevelop->addSettings('', $settings, $gid, $AISDevelop::DISPLAY_ORDER_BEGIN);
    
    // se adauga campul de verificare al codului invitatiei in cadrul formularului de inregistrare
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets("member_profile", '#' . preg_quote('{$warning_level}') . '#i', '{$warning_level}{$advinvsys_profile}');
    find_replace_templatesets("member_register", "#" . preg_quote('{$regimage}') . "#i", '{$advinvsys_reg_field}{$regimage}');
    find_replace_templatesets("member_register_agreement", "#" . preg_quote('<input type="hidden" name="action" value="register" />') . "#i", '<input type="hidden" name="action" value="register" />{$advinvsys_save_code}');
    find_replace_templatesets("stats", "#" . preg_quote('<strong>{$stats[\'numusers\']}</strong>') . "#i", '<strong>{$stats[\'numusers\']}</strong><br />{$lang->advinvsys_stats_total} <strong>{$stats[\'totalinvs\']}</strong>');
    find_replace_templatesets("stats", "#" . preg_quote('<strong>{$repliesperthread}</strong>') . "#i", '<strong>{$repliesperthread}</strong><br />{$lang->advinvsys_stats_perusers} <strong>{$advinvsys_invsperusers}</strong>');
}

// Functia de dezactivare a modificarii
function advinvsys_deactivate() {
    global $db;
    
    // se sterg setarile din baza de date
    $db->write_query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name = 'advinvsys_group'");
    $db->write_query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name LIKE 'advinvsys_setting_%'");
    
    // se reconstruiesc toate setarile
    rebuild_settings();
    
    // se sterge cache-ul folosit la reguli
    $db->delete_query("datacache", "title = 'advinvsys_rules'");
    
    // se sterge campul de verificare al codului invitatiei in cadrul formularului de inregistrare
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets("member_profile", '#' . preg_quote('{$advinvsys_profile}') . '#i', '', 0);
    find_replace_templatesets("member_register", "#" . preg_quote('{$advinvsys_reg_field}') . "#i", '', 0);
    find_replace_templatesets("member_register_agreement", "#" . preg_quote('{$advinvsys_save_code}') . "#i", '', 0);
    find_replace_templatesets("stats", "#" . preg_quote('<br />{$lang->advinvsys_stats_total} <strong>{$stats[\'totalinvs\']}</strong>') . "#i", '', 0);       
    find_replace_templatesets("stats", "#" . preg_quote('<br />{$lang->advinvsys_stats_perusers} <strong>{$advinvsys_invsperusers}</strong>') . "#i", '', 0); 
}

// Functia intoarce un vector cu sabloanele ce vor fi instalate
function advinvsys_get_templates($ids = array())
{
    $templates = array();
    $templates[] = array(
        'title' => 'advinvsys_donate_header',
        'template' => '<script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
        <script type="text/javascript">
            Event.observe(document, "dom:loaded", function() {
                $(\'amount\').observe(\'change\', function() {
                    var value = this.getValue();
                    if (value.match(/^\d{1,9}(\.\d{1,2})?$/))
                        this.setStyle({ background: \'#BCF5A9\' });
                    else
                        this.setStyle({ background: \'#F5A9BC\' });
                });
            });
        </script>'
    );
    $templates[] = array(
        'title' => 'advinvsys_preview_header',
        'template' => '<link type="text/css" rel="stylesheet" href="./inc/plugins/advinvsys/classes/style_table.css" />
        <style type="text/css">
            a.advinvsys_send_button {
                display: block; height: 16px; width: 16px; text-indent: -999px; text-decoration: none; overflow: hidden; padding: 0px; margin: 0px;
                background: transparent url(./images/advinvsys/send.png);
            }
            a.tooltip span {
                display:none; padding:2px 3px; margin-left:-5px;
            }
            a.tooltip:hover span {
                display:inline; position:absolute; border:1px solid #cccccc; background:#ffffff; color:#6c6c6c;
            }
        </style>
        <link type="text/css" rel="stylesheet" href="./inc/plugins/advinvsys/jscripts/default.css" />
        <script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
        <script type="text/javascript" src="./inc/plugins/advinvsys/jscripts/window.js"> </script>
        <script type="text/javascript" src="./inc/plugins/advinvsys/jscripts/window_effects.js"> </script>
        <script type="text/javascript">
            function previewEmail() {
            	postData = "&message=" + encodeURIComponent(document.getElementById("message_new").value);
                new Ajax.Request(\'xmlhttp.php?action=ais_previewEmail\', {
                    method: \'post\', 
                    postBody: postData, 
                    onComplete: function(data) { 
                        text = data.responseText.replace(/(\r\n|\n|\r)/gm, "");
                        if(text.match(/<success>(.*)<\/success>/)) {
                            htmlb = text.match(/<success>(.*)<\/success>/);
                            var win = new Window("previewEmail", {className: "dialog", width:350, height:200, zIndex: 100, resizable: true, title: "Preview Email Content", showEffect:Effect.BlindDown, hideEffect: Effect.SwitchOff, wiredDrag:true, destroyOnClose: true}) 
                            win.setHTMLContent("<div align=\"left\">"+ htmlb[0] + "</div>");
                            win.setStatusBar("<small>Copyright &copy; 2012 by <a href=\"http://mybb.ro\" target=\"_blank\">MyBB Romania</a>. All rights reserved.</small>"); 
                            win.toFront();                            
                            win.showCenter();
                        }
                    }
                });
            }
        </script>'
    );
    $templates[] = array(
        'title' => 'advinvsys_profile',
        'template' => '<tr>
            <td class="trow2"><strong>{$lang->advinvsys_profile_ins}:</strong></td>
            <td class="trow2"><a href="{$mybb->settings[\'bburl\']}/usercp.php?action=advinvsys">{$invitations}</a>{$extra}{$extra1}</td>
        </tr>'
    );
    $templates[] = array(
        'title' => 'advinvsys_profile_extra',
        'template' => '<span class="smalltext">[{$lang->advinvsys_profile_ref}: {$count}]</span>'
    );
    $templates[] = array(
        'title' => 'advinvsys_profile_extra1',
        'template' => '<span class="smalltext">[<a href="{$mybb->settings[\'bburl\']}/usercp.php?action=advinvsys&amp;method=send{$username}">{$lang->advinvsys_profile_send}</a>]</span>'
    );
    $templates[] = array(
        "title" => "advinvsys_nav_option",
        "template" => "<style>.usercp_nav_advinvsys {background: url(images/advinvsys/invitations.gif) no-repeat left center;}</style><tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/{\$nav_link}\" class=\"{\$class1} {\$class2}\">{\$nav_text}</a></td></tr>"
    );
    $templates[] = array(
        "title" => "advinvsys_registration_agreement",
        "template" => '<input type="text" style="visibility: hidden;" value="{$value}" name="advinvsys_save_code" />'
    );
    $templates[] = array(
        "title" => "advinvsys_registration_field",
        "template" => "<br/>
<fieldset class=\"trow2\">
<legend><strong>{\$lang->advinvsys_reg_title}</strong></legend>
<table cellspacing=\"0\" cellpadding=\"{\$theme['tablespace']}\">
<tr>
<td colspan=\"2\"><span class=\"smalltext\">{\$lang->advinvsys_reg_explain}</span></td>
</tr>
<tr>
<td width=\"60%\"><br /><input type=\"text\" class=\"textbox\" name=\"advinvsys_reg_value\" value=\"{\$advinvsys_saved_code}\" id=\"advinvsys_reg_value\" style=\"width: 100%;\"/></td>
<td align=\"right\" valign=\"bottom\"></td>
</tr>
<tr>
	<td id=\"advinvsys_reg_status\"  style=\"display: none;\" colspan=\"2\">&nbsp;</td>
</tr>
</table>
</fieldset>"
    );
    $templates[] = array(
        'title' => 'advinvsys_send_form',
        'template' => '<form action="usercp.php?action=advinvsys&amp;method=send" method="post">
        {$errors}
        <table border="0" cellspacing="1" cellpadding="4" class="tborder">
          <tbody>
            <tr>
              <td class="thead" colspan="2" width="100%">
                <strong>{$lang->advinvsys_send_title}</strong>
              </td>
            </tr>
            <tr>
              <td class="trow1" width="50%">
                  <strong>{$lang->advinvsys_send_user}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_send_user_desc}</span>
              </td>
              <td class="trow1" width="50%">
                  <input type="text" name="username" id="username" value="{$usr}" maxlength="32" />
              </td>
            </tr>
            <tr>
              <td class="trow2" width="50%">
                  <strong>{$lang->advinvsys_send_amount}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_send_amount_desc}</span>
              </td>
              <td class="trow2" width="50%">
                  <input type="text" name="amount" id="amount" value="" /> ({$myinvitations})
              </td>
            </tr>
            <tr>
              <td class="trow1" width="50%">
                  <strong>{$lang->advinvsys_send_reason}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_send_reason_desc}</span>
              </td>
              <td class="trow1" width="50%">
                  <textarea name="message" id="message"></textarea>
              </td>
            </tr>
            <tr>
              <td class="thead" colspan="2" align="center">
                  <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
                  <input type="submit" name="send_submit" value="{$lang->advinvsys_send_submit}" />
                  <input type="reset" name="send_reset" value="{$lang->advinvsys_send_reset}" />
              </td>
            </tr>
          </tbody>
        </table>
</form>'
    );
    $templates[] = array(
        'title' => 'advinvsys_invite_form',
        'template' => '<form action="usercp.php?action=advinvsys" method="post">
        {$errors1}
        <table border="0" cellspacing="1" cellpadding="4" class="tborder">
          <tbody>
            <tr>
              <td class="thead" colspan="2" width="100%">
                <strong>{$lang->advinvsys_invite_title}</strong>
              </td>
            </tr>
            <tr>
              <td class="trow1" width="40%">
                  <strong>{$lang->advinvsys_invite_email}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_invite_email_desc}</span>
              </td>
              <td class="trow1" width="60%">
                  <input type="text" name="email" id="email" value="" maxlength="64" />
              </td>
            </tr>
            <tr>
              <td class="trow1" width="40%">
                  <strong>{$lang->advinvsys_invite_mess}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_invite_mess_desc}</span>
              </td>
              <td class="trow1" width="60%">
                  <textarea name="message" id="message" rows="8" cols="75" tabindex="2"></textarea>
                  {$codebuttons}
              </td>
            </tr>
            <tr>
              <td class="thead" colspan="2" align="center">
                  <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
                  <input type="hidden" name="my_form" value="invite" />
                  <input type="submit" name="send_submit" value="{$lang->advinvsys_invite_submit}" />         
                  <input type="button" onclick="previewEmail()" value="{$lang->advinvsys_invite_preview}" />
                  <input type="reset" name="send_reset" value="{$lang->advinvsys_send_reset}" />
              </td>
            </tr>
          </tbody>
        </table>
</form>'
    );
    $templates[] = array(
        'title' => 'advinvsys_buy_form',
        'template' => '<form action="usercp.php?action=advinvsys" method="post">
        {$errors2}
        <table border="0" cellspacing="1" cellpadding="4" class="tborder">
          <tbody>
            <tr>
              <td class="thead" colspan="2" width="100%">
                <strong>{$lang->advinvsys_buy_title}</strong>
              </td>
            </tr>
            <tr>
              <td class="trow1" width="40%">
                  <strong>{$lang->advinvsys_buy_gateway}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_buy_gateway_desc}</span>
              </td>
              <td class="trow1" width="60%">
                  <select name="gateway" id="gateway" size="1">
                     {$options}
                  </select>
              </td>
            </tr>
            <tr>
              <td class="trow2" width="40%">
                  <strong>{$lang->advinvsys_buy_number}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_buy_number_desc}</span>
              </td>
              <td class="trow2" width="60%" id="tdnumber">
                  <select name="number" id="number" size="1">
                     <option value="-">Chose one value  </option>
                  </select>
              </td>
            </tr>
            <tr>
              <td class="trow1" width="40%">
                  <strong>{$lang->advinvsys_buy_price}</strong><br/>
                  <span class="smalltext">{$lang->advinvsys_buy_price_desc}</span>
              </td>
              <td class="trow1" width="60%">
                  <input type="text" name="price" id="price" value="" readonly="readonly" style="text-align: right;" /><span id="currency"></span>
              </td>
            </tr>
            <tr id="special_line">
            </tr>
            <tr>
              <td class="thead" colspan="2" align="center">
                  <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
                  <input type="hidden" name="my_form" value="buy" />
                  <input type="submit" name="buy_submit" id="buy_submit" value="{$lang->advinvsys_invite_submit}" />
                  <input type="reset" name="buy_reset" value="{$lang->advinvsys_send_reset}" />
              </td>
            </tr>
          </tbody>
        </table>
</form>'
    );
    $templates[] = array(
        'title' => 'advinvsys_standard_page',
        'template' => '<html><head>
        <title>{$mybb->settings[bbname]} - {$title}</title>
        {$headerinclude}{$extraheader}
        </head><body>
        {$header}
        <table width="100%" border="0" align="center">
        <tr>
            {$usercpnav}
            <td valign="top">
                {$content}
                <br/>
                <div style="background: #efefef;border: 1px solid #4874a3;padding: 4px;"><div class="float_right" style="margin-top : -1px;"><img src="./images/advinvsys/version.png" alt="Version"/></div><span class="smalltext">Copyright &copy; 2012 by <a href="http://mybb.ro" target="_blank">MyBB Romania</a>. All rights reserved.</span></div>
            </td>
        </tr>
        </table>
        {$footer}
        </body></html>'
    );
    $templates[] = array(
        'title' => 'advinvsys_sms_buy',
        'template' => '<table width="100%">
        <tr>
            <td width="40%"><b>{$lang->advinvsys_fortumo_show_key}</b> {$keyword} {$lang->advinvsys_fortumo_show_chs}</td>
            <td width="30%"><b>{$lang->advinvsys_fortumo_show_code}</b> {$shortcode}</td>
            <td width="30%"><b>{$lang->advinvsys_fortumo_show_ctr}</b> {$country}</td>
        </tr>
        <tr>
            <td colspan="3"><div style="padding:0 10px 0 10px;margin-bottom:10px;border:#e5e4e2 solid 1px;background-color:#f6f8f7;width:100%px;overflow-x:auto">{$promotional}</div></td>
        </tr></table>'
    );
    
    // daca vectorul nu are elemente atunci se returneaza toate
    if (count($ids) == 0)
        return $templates;
    else {
        // se returneaza doar ce ne intereseaza
        return array_intersect_key($templates, array_flip($ids));
    }
}

function advinvsys_get_settings($ids = array())
{
    $settings = array();
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_enable",
        "title" => "[Core] Is this plugin enabled?",
        "description" => "Can this plugin do his job? (Default : Yes)",
        "optionscode" => "yesno",
        "value" => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_only",
        "title" => "[Core] Enable registration only with an invitation?",
        "description" => "If it is set to \"No\", invites will not be checked on registration. But if it is set to \"Yes\" a invitation code will be required in order to register. (Default : Yes)",
        "optionscode" => "yesno",
        "value" => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_maxattempts",
        "title" => "[Core] Set max attempts for registration :",
        "description" => "After how many unsuccessful attempts to introduce a valid invitation code the registration process will be locked. Let empty or 0 to disable this feature. (Default : 5)",
        "optionscode" => "text",
        "value" => "5"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_expattempts",
        "title" => "[Core] Set time lock for unsuccessful attempts :",
        "description" => "After how much time a user locked at registration process can try again? (Default : 10 Minutes)",
        "optionscode" => "select\n10=10 Minutes\n30=30 Minutes\n60=1 Hour\n180=3 Hours",
        "value" => "10"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_showrefc",
        "title" => "[Core] Display invited user count on his profile?",
        'description' => "Do you want to display on user profile the number of people invited by him on this board? Default : Yes",
        'optionscode' => "yesno",
        'value' => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_sendemail",
        "title" => "[Core] What message will be send when someone send an invitation?",
        "description" => "Enter the message that you want to be send as email body, when someone generate and send an invitation using \"User CP\" section.",
        "optionscode" => "textarea",
        "value" => "Hello.\nThis message has been sent by a friend of yours known as <b>{INVITER_USERNAME}</b>, because he thinks that you may be interested in the following website: <a href=\"{BOARD_URL}\" target=\"_blank\">{BOARD_NAME}</a>.\n\nYou can use the following invitation code for registration : {INVITATION_CODE}\nHere you can register: <a href=\"{REGISTRATION_URL}\" target=\"_blank\">{REGISTRATION_URL}</a>\n\n<b><i>Your friend wrote:</i></b>\n-------------------------------------------\n{USER_MESSAGE}"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_dateexpr",
        "title" => "[Core] Expiration days :",
        'description' => "After how many days an invitation already send will expire? (Default : 7)",
        'optionscode' => "text",
        'value' => "7"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_giveexpr",
        "title" => "[Core] Give credits for expired invitations?",
        'description' => "Are you sure that you want to give back the credits taken for expired keys. (Default : Yes)",
        'optionscode' => "yesno",
        'value' => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_intresend",
        "title" => "[Core] When a user can resend an email with an invitation?",
        'description' => "After how many days an user can resend an invitation that was already sent in the past. (Default : 3)",
        'optionscode' => "text",
        'value' => "3"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_timeonlinetype",
        "title" => "[Income] Time spent online :",
        'description' => "When the time spent online for a user increase with one value specified below then he will receive invitations according to the next setting. (Default : 12 Hours)",
        'optionscode' => "select\n1=1 Hour\n6=6 Hours\n12=12 Hours\n24=1 Day\n72=3 Days\n168=7 Days",
        'value' => "12"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_timeonlinehow",
        "title" => "[Income] How many invites will be given ?",
        'description' => "How many invites will be given to user, according to the setting below this. Leave 0 if you want to disable this feature. (Default : 0)",
        'optionscode' => "text",
        'value' => "0"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_paypalemail",
        "title" => "[Paypal] Email address of PayPal account :",
        "description" => "Please specify the email address for your <b>PayPal</b> account! This field it is required to enable <b>PayPal</b> gateway. (Default : )",
        "optionscode" => "text",
        "value" => ""
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_paypalcurr",
        "title" => "[Paypal] Paypal currency :",
        "description" => "What is the currency of this payment gateway? (Default : Euro)",
        "optionscode" => "select\nEUR=Euro\nJPY=Japanese Yen\nGBP=Pound Sterling\nUSD=U.S. Dollar",
        "value" => "EUR"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_paypalcost",
        "title" => "[Paypal] How much is an invitation?",
        "description" => "How much is an invitation through <b>Paypal</b>. It will be used to calculate the final amount for a payment using <b>PayPal</b>! (Default : 1)",
        "optionscode" => "text",
        "value" => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_packages",
        "title" => "[Paypal + NewPoints] Packages :",
        "description" => "Packages of invitations that can be purchased via <b>PayPal</b> or <b>NewPoints</b>. You can use comma as separator. (Default : 1,5,10)",
        "optionscode" => "text",
        "value" => "1,5,10"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_newpointsenable",
        "title" => "[NewPoints] Is this gateway enabled? :",
        "description" => "Can a user buy invitations using <b>NewPoints</b> plugin? (Default : No)",
        "optionscode" => "yesno",
        "value" => "0"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_newpointscost",
        "title" => "[NewPoints] How much is an invitation?",
        "description" => "How much is an invitation through <b>NewPoints</b>. It will be used to calculate the final amount for a payment using NewPoints! (Default : 10)",
        "optionscode" => "text",
        "value" => "10"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smsenable",
        "title" => "[SMS] Is this gateway enabled?",
        "description" => "Can a user buy invitations using SMS gateway? (Default : No)",
        "optionscode" => "yesno",
        "value" => "0"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smsid",
        "title" => "[SMS] Service ID :",
        "description" => "Insert your service ID below, in order to make SMS-Fortumo gateway to work fine. The field it is required to enable this payment gateway! (Default : )",
        "optionscode" => "text",
        "value" => ""
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smssecret",
        "title" => "[SMS] Secret Code :",
        "description" => "You need to add in this textfield a secret code from Fortumo. Also this field it is required in order to activate SMS payment gateway! (Default : )",
        "optionscode" => "text",
        "value" => ""
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smssig",
        "title" => "[SMS] Signature :",
        "description" => "The signature has 32 carachters and can be found on <i>Fortumo > Dashboard > My services > Setup</i> page. A user cannot buy using this gateway until you do not type a value on this field. (Default : )",
        "optionscode" => "text",
        "value" => ""
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smsinvs",
        "title" => "[SMS] Amount of invitations",
        "description" => "Set amount of invitations that can be purchased using <b>SMS - Fortumo</b>. (Default : 1)",
        "optionscode" => "text",
        "value" => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smssendpm",
        "title" => "[PM] Send PM to buyer?",
        "description" => "If it is set to \"Yes\" then when a user buy some invitations using a gateway, the system will send a PM to him. (Default : Yes)",
        "optionscode" => "yesno",
        "value" => "1"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smssendpmm",
        "title" => "[PM] What message will be sent ?",
        "description" => "If setting \"Send PM to buyer?\" is set to \"Yes\" then here you can specify a custom message that will be send as PM.",
        "optionscode" => "textarea",
        "value" => "Hi {USER_NAME}! ({USER_EMAIL})!\n Thank you for items purchased!"
    );
    $settings[] = array(
        "sid" => "NULL",
        "name" => "setting_smssendemail",
        "title" => "[Email] What message will be sent ?",
        "description" => "What message will be sent as email when someone buys an invitation using PayPal or SMS - Fortumo?",
        "optionscode" => "textarea",
        "value" => "Hi.\nThis message contains an invitation code that you have bought using {GATEWAY} in order to register on our board - <a href=\"{BOARD_URL}\" target=\"_blank\">{BOARD_NAME}</a>.\n\nYour invitation code is {INVITATION_CODE}.\nHere you can register: <a href=\"{REGISTRATION_URL}\" target=\"_blank\">{REGISTRATION_URL}</a>\n\nThank you for purchasing this invitation."
    );
    
    // daca vectorul nu are elemente atunci se returneaza toate
    if (count($ids) == 0)
        return $settings;
    else {
        // se returneaza doar ce ne intereseaza
        return array_intersect_key($settings, array_flip($ids));
    }
}

// Se incarca modificarile in panoul de administrare
function advinvsys_admin_load_hook() {
    global $cache, $plugins, $mybb, $theme, $db, $templates;

    advinvsys_load_modules();
}

// Se incarca modificarile pentru interfata cu utilizatorul
function advinvsys_plugins_start() {
    global $cache, $plugins, $mybb, $theme, $db, $templates;

    advinvsys_load_modules();
}

// Functia adauga informatie in cadrul paginii de statistici
function advinvsys_stats() {
    global $db, $lang, $stats, $advinvsys_invsperusers, $AISDevelop;
    
    // se incarca fisierul de limba
    $lang->load('advinvsys');
    
    // se seteaza numarul total de invitatii
    $query = $db->simple_select('users', 'SUM(invitations) AS total', '');
    $stats['totalinvs'] = floatval($db->fetch_field($query, 'total'));
	
	// variabila retine numarul de invitatii per membru!
	if ((int)$stats['numusers'] != 0) {
		$advinvsys_invsperusers = $AISDevelop->truncNumber($stats['totalinvs'] / $stats['numusers']);
	} else {
		$advinvsys_invsperusers = 'NaN';
	}
}

// Functia afiseaza o serie de informatii in profilul utilizatorului
function advinvsys_profile() {
    global $mybb, $db, $templates, $memprofile, $advinvsys_profile, $lang, $plugins, $AISDevelop;

    // verificam daca este activa aceasta functie
    if ($mybb->settings['advinvsys_setting_enable'] != 1) {
        $advinvsys_profile = '';
        return;
    }

    // se permite adaugarea unor alte portiuni de coduri
    $plugins->run_hooks('advinvsys_profile_start');

    // daca e activa atunci se afiseaza
    $lang->load('advinvsys');

    // cate invitatii are persoana de fata?
    $invitations = $AISDevelop->truncNumber($memprofile['invitations']);

    // informatii aditionale
    $extra = '';
    $extra1 = '';
    if ($mybb->settings['advinvsys_setting_showrefc'] == 1) {

        // care este uid-ul ei
        $uid = intval($memprofile['uid']);

        // se intoarce din baza de date numarul de persoane aduse pe forum
        $query = $db->write_query("SELECT COUNT(uid) AS number FROM " . TABLE_PREFIX . "users 
                WHERE invitation_refer = '{$uid}'");

        if ($row = $db->fetch_array($query)) {
            $count = intval($row['number']);
            eval("\$extra .= \"" . $templates->get('advinvsys_profile_extra') . "\";");
        }
    }

    // nu iti poti trimite singur invitatii
    // nici un vizitator nu iti poate trimite invitatii
    if ($mybb->user['uid'] && $memprofile['uid'] != $mybb->user['uid']) {
        $username = '&amp;usrn=' . $memprofile['username'];
        eval("\$extra1 .= \"" . $templates->get('advinvsys_profile_extra1') . "\";");
    }

    // se permite adaugarea unor alte portiuni de coduri
    $plugins->run_hooks('advinvsys_profile_end');

    // se afiseaza in profil informatiile legate de sistemul de invitatii
    eval("\$advinvsys_profile = \"" . $templates->get('advinvsys_profile') . "\";");
}

// Functria este folosita pentru verificarea unei invitatii
function advinvsys_user_validate($data) {
    global $mybb, $db, $lang, $plugins;

    // ne intereseaza sa realizam testele doar daca vizitatorul doreste sa se 
    // inregistreze si acest plugin este activat
    if ($mybb->settings['advinvsys_setting_enable'] == 1 &&
            $mybb->settings['advinvsys_setting_only'] == 1 &&
            $mybb->input['action'] == 'do_register') {

        // se incarca fisierul lingvistic
        $lang->load("advinvsys");

        // se pornesc sesiunile
        session_start();

        // verificam daca nu cumva a depasit numarul de incercari
        $maxattempts = intval($mybb->settings['advinvsys_setting_maxattempts']);
        if (isset($_SESSION['advinvsys_maxattemps']['attemps']) && $maxattempts > 0 &&
                $_SESSION['advinvsys_maxattemps']['attemps'] >= $maxattempts) {
            $maxtime = intval($mybb->settings['advinvsys_setting_expattempts']);
            // ne asiguram ca nu se poate cobora sub 10 minute
            if ($maxtime < 10)
                $maxtime = 10;

            // daca timpul sesiunii a expirat atunci se sterge sesiunea
            if (isset($_SESSION['advinvsys_maxattemps']['timestamp']) &&
                    $_SESSION['advinvsys_maxattemps']['timestamp'] <
                    intval(TIME_NOW - $maxtime * 60)) {

                // acum ar trebui sa se poata inregistra
                unset($_SESSION['advinvsys_maxattemps']);
            } else {
                // altfel inseamna ca avem o eroare
                $data->set_error($lang->sprintf($lang->advinvsys_reg_maxattempts, $maxtime));

                // se iese imediat
                return;
            }
        }

        // se verifica daca codul introdus exista in baza de date
        $inv_code = $db->escape_string($mybb->input['advinvsys_reg_value']);

        // si daca invitatia nu a expirat
        $days_expr = (is_numeric($mybb->settings['advinvsys_setting_dateexpr']))
            ? abs($mybb->settings['advinvsys_setting_dateexpr'])
            : 7; // 7 zile in mod implicit
        // interogarea
        $query = $db->simple_select('advinvsys_codes', '*', "code = '{$inv_code}' 
            AND date > " . intval(TIME_NOW - intval($days_expr) * 86400), array('limit' => 1));

        if (!($row = $db->fetch_array($query))) {
            // doar in cazul in care invitatia nu exista in sistem se seteaza o eroare
            $data->set_error($lang->advinvsys_reg_wrong_answer);

            // inainte de a se iesi din functie se incrementeaza contorul de autentificari esuate
            if (!isset($_SESSION['advinvsys_maxattemps'])) {
                // o vom seta pe 1
                $_SESSION['advinvsys_maxattemps'] = array(
                    'attemps' => 1,
                    'timestamp' => TIME_NOW
                );
            } else {
                $array = $_SESSION['advinvsys_maxattemps'];
                // se fac reactualizarile
                $_SESSION['advinvsys_maxattemps'] = array(
                    'attemps' => $array['attemps'] + 1,
                    'timestamp' => TIME_NOW
                );
            }

            // se iese imediat
            return;
        }

        // se permite adaugarea unor alte portiuni de coduri
        $data = $plugins->run_hooks('advinvsys_user_do_validate', $data);
        // pe cealalta ramura nu se face nimic
    }
    // se returneaza informatiile primite si prelucrate
    return $data;
}

// Functia este apelata imediat dupa inregistrarea utilizatorului
function advinvsys_do_register() {
    global $mybb, $db, $user, $user_info, $plugins;

    // ne intereseaza sa realizam testele doar daca vizitatorul doreste sa se 
    // inregistreze si acest plugin este activat
    if ($mybb->settings['advinvsys_setting_enable'] == 1 &&
            $mybb->input['action'] == "do_register") {
        $inv_code = $db->escape_string($mybb->input['advinvsys_reg_value']);

        // cine a invitat aceasta persoana ?
        $db->write_query('UPDATE ' . TABLE_PREFIX . 'users u, (SELECT did AS user_id 
            FROM ' . TABLE_PREFIX . 'advinvsys_codes WHERE code = \'' . $inv_code . '\' 
            LIMIT 1) AS u1 SET u.invitation_refer = GREATEST(0, u1.user_id) 
            WHERE u.uid = \'' . $user_info['uid'] . '\'');

        // se permite adaugarea unor alte portiuni de coduri
        $plugins->run_hooks('advinvsys_member_do_register');

        // daca e sistemul atunci se lasa pe 0
        // invitatia se sterge din baza de date
        $db->delete_query('advinvsys_codes', "code = '{$inv_code}'");

        // de asemenea se proceseaza si invitatiile expirate (daca sunt!)
        $AISDevelop->checkInvitationExpired();
    }
    return $data;
}

// Functia care permite inregistrarea utilizand un cod de invitatie primit prin e-mail
function advinvsys_register_agreement() {
    global $mybb, $db, $advinvsys_save_code, $templates;

    // este modificarea activa?
    if ($mybb->settings['advinvsys_setting_enable'] == 1) {
        $value = "";
        
        // exista un cod setat pentru modificare?
        if (isset($mybb->input['code']) && strlen($mybb->input['code']) == 32)
            $value = $db->escape_string($mybb->input['code']);
        
        eval("\$advinvsys_save_code .= \"" . 
                $templates->get('advinvsys_registration_agreement') . "\";");     
    }
}

// Functia care afiseaza pe ecran campul "Invitation Code" din cadrul formularului de inregistrare
function advinvsys_register_end() {
    global $mybb, $db, $templates, $theme, $lang, $advinvsys_reg_field, $validator_extra;

    if ($mybb->settings['advinvsys_setting_enable'] == 1) {

        // se incarca fisierul lingvistic al aplicatiei
        $lang->load('advinvsys');

        // in mod normal este afisat campul
        $advinvsys_saved_code = "";

        // se verifica daca codul de inregistrare are 32 de caractere
        if (isset($mybb->input['advinvsys_save_code']) && strlen($mybb->input['advinvsys_save_code']) == 32) {
            $advinvsys_saved_code = $db->escape_string($mybb->input['advinvsys_save_code']);
        }

        // se adauga un extra validator
        $validator_extra .= "\tregValidator.register('advinvsys_reg_value', 'ajax', {url:'xmlhttp.php?action=advinvsys_validate', loading_message:'{$lang->regq_checking}', failure_message:'{$lang->regq_wrong_answer}'});\n";

        eval("\$advinvsys_reg_field = \"" . $templates->get("advinvsys_registration_field") . "\";");
    } else {
        eval("\$advinvsys_reg_field = \"\";");
    }
}

// Functia care valideaza un cod de invitatie
function advinvsys_xmlhttp() {
    global $mybb, $db, $lang, $charset, $AISDevelop;

    // se include fisierul de limba
    $lang->load("advinvsys");

    // daca actiunea curenta este cea de validare a unui cod de invitatie
    if ($mybb->input['action'] == 'advinvsys_validate') {

        // se schimba antetul modificarii
        header("Content-type: text/xml; charset={$charset}");
        $inv_code = $db->escape_string($mybb->input['value']);

        // se verifica daca codul exista in baza de date
        $query = $db->simple_select('advinvsys_codes', '*', 
                "code = '{$inv_code}'", array('limit' => 1));

        if (!($row = $db->fetch_array($query))) {
            // codul invitatiei nu exista in baza de date
            echo "<fail>{$lang->advinvsys_reg_wrong_answer}</fail>";
        } else {
            // codul invitatiei exista si poate fi folosit
            echo "<success>{$lang->advinvsys_reg_correct_answer}</success>";
        }

        // se iese fortat
        exit;
    } else if ($mybb->input['action'] == 'ais_previewEmail') {
        // se parseaza bbcode-urile
        if (!class_exists('postParser'))
            require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser1 = new postParser;

        $body = nl2br($mybb->settings['advinvsys_setting_sendemail']);

        $message = $mybb->input['message'];
        $message = $parser1->parse_message($message, array(
            'allow_html' => 0,
            'allow_smilies' => 0,
            'allow_mycode' => 1,
            'filter_badwords' => 1
                ));

        echo "<success>" . $AISDevelop->parseBody($body, 
                md5('PREVIEW_MODE'), $message) . "</success>";
        exit;
    } else if ($mybb->input['action'] == 'ais_smsfortumo') {
        try {

            $result = advinvsys_get_sms_data(
                    'http://api.fortumo.com/api/services/2/' . $mybb->settings['advinvsys_setting_smsid'] .
                    '.' . $mybb->settings['advinvsys_setting_smssig'] . '.xml', 
                    advinvsys_country_recognition($_SERVER['REMOTE_ADDR']),
                    $lang->advinvsys_fortumo_buy_err3, 
                    $lang->advinvsys_fortumo_buy_err2
            );

            echo "<success>{$result}</success>";
        } catch (Exception $ex) {

            echo "<fail>{$lang->advinvsys_fortumo_buy_err1}</fail>";
        }
    }
}

// Functia care adauga in cadrul meniului din User CP un link catre formularul 
// de trimitere a unei invitatii
function advinvsys_menu_built() {
    global $mybb;

    // daca modificarea nu este activa atunci legatura nu va aparea in meniu
    if ($mybb->settings['advinvsys_setting_enable'] != 1) {
        return;
    }

    // altfel va aparea
    global $lang, $templates, $usercpmenu;

    // se incarca fisierul de limba al modificarii
    $lang->load('advinvsys');

    // daca ne aflam in scriptul usercp.php sau private.php?
    if (in_array(THIS_SCRIPT, array('private.php', 'usercp.php'))) {

        $advinvsys_nav_option = "";
        $class1 = "usercp_nav_item";
        $class2 = "usercp_nav_advinvsys";
        $nav_link = "usercp.php?action=advinvsys";
        $nav_text = $lang->advinvsys_usercp_my;

        // adauga codul optiunii tale
        eval("\$usercpmenu .= \"" . $templates->get("advinvsys_nav_option") . "\";");
    }
}

// CEA MAI IMPORTANTA FUNCTIE!
// Functia ce afiseaza pe ecran formularul de trimitere a unei invitatii
function advinvsys_main_page() {
    global $mybb;

    // este activa modificarea?
    if ($mybb->settings['advinvsys_setting_enable'] != 1) {
        return;
    }

    // daca este activa se afiseaza pagina pe ecran
    global $db, $cache, $plugins, $config, $lang, $theme, $templates, $forum, 
            $headerinclude, $header, $footer, $usercpnav, $AISDevelop;

    // mai intai se incarca fisierul de limba
    $lang->load('advinvsys');

    // alte teste
    if (THIS_SCRIPT != 'usercp.php' || $mybb->input['action'] != 'advinvsys')
        return;

    // se include clasa necesara tabelelor
    if (!class_exists('DefaultTable'))
        require_once MYBB_ROOT . '/inc/plugins/advinvsys/classes/class_table.php';
    // se vor parsa mesajele optionale
    if (!class_exists('postParser'))
        require_once MYBB_ROOT . 'inc/class_parser.php';
    $parser = new postParser;

    $extraheader = '';
    $content = '';

    $plugins->run_hooks('advinvsys_mainpage_start');

    if ($mybb->input['method'] == 'send') {
        if ($mybb->request_method == "post") {
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                $errors[] = $lang->advinvsys_errors_invalidreq;
            }

            $amount = floatval($mybb->input['amount']);
            $username = $db->escape_string($mybb->input['username']);

            $plugins->run_hooks('advinvsys_mainpage_do_donate_start');

            // se verifica daca nu cumva isi doneaza tot lui
            if ($username == $mybb->user['username']) {
                // iar vom genera o eroare
                $errors[] = $lang->advinvsys_send_selfdonate;
            }

            // valoarea introdusa e buna?
            if (!is_numeric($amount) || strlen($amount) > 12 || $amount <= 0) {
                // o noua eroare
                $errors[] = $lang->advinvsys_send_erramount;
            }

            // exista utilizatorul (+ sa fie unic)
            if (!($uid = $db->fetch_field($db->simple_select('users', '*', 
                    'username = \'' . $username . '\''), 'uid'))) {
                $errors[] = $lang->advinvsys_send_erruser;
            }

            // se verifica daca se pot lua invitatiile de la cel care doneaza... are destule?
            $invs = $AISDevelop->checkInvitations('uid', $mybb->user['uid'], $amount);
            if ($invs == false) {
                // donatorul nu are suficiente invitatii
                // apare eroare
                $errors[] = $lang->sprintf($lang->advinvsys_send_notenough, 
                        $mybb->user['invitations']);
            }

            // au aparut erori?
            if (count($errors) == 0) {

                // se poate face transferul
                // se adauga utilizatorului invitatiile
                $AISDevelop->addInvitations('username', $username, $amount, false, false);

                // se scad donatorului invitatiile
                $AISDevelop->addInvitations('uid', $mybb->user['uid'], -$amount, false, false);

                // mai trebuie sa introducem un log in baza de date
                $AISDevelop->addLog($lang->advinvsys_send_log_type, 
                    $lang->sprintf($lang->advinvsys_send_log, $amount, $username), 
                    $mybb->user['uid']);

                // de asemenea se va trimite si un PM
                if ($mybb->input['message'] != '') {
                    $AISDevelop->sendPM(array(
                        'subject' => $lang->advinvsys_send_pmsubject,
                        'message' => $lang->sprintf($lang->advinvsys_send_pmmessage_reason, 
                                $amount, htmlspecialchars_uni($mybb->input['message'])),
                        'receivepms' => 1,
                        'touid' => $uid
                    ));
                } else {
                    $AISDevelop->sendPM(array(
                        'subject' => $lang->advinvsys_send_pmsubject,
                        'message' => $lang->sprintf($lang->advinvsys_send_pmmessage, $amount),
                        'receivepms' => 1,
                        'touid' => $uid
                    ));
                }

                $plugins->run_hooks('advinvsys_mainpage_do_donate_end');

                // se face redirectionarea
                redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys', 
                        $lang->sprintf($lang->advinvsys_send_donated, $amount, intval($invs - $amount)));
            } else {
                // se afiseaza erorile frumos pe ecran
                $errors = inline_error($errors);
            }
        }
        
        eval("\$extraheader = \"" . $templates->get("advinvsys_donate_header") . "\";");

        $usr = htmlspecialchars_uni($mybb->input['usrn']);
        $myinvitations = $lang->sprintf($lang->advinvsys_send_amount_have, 
                $mybb->user['invitations']);

        $plugins->run_hooks('advinvsys_mainpage_donate');

        eval("\$form = \"" . $templates->get("advinvsys_send_form") . "\";");
        $content .= $form;
    } else {
        if (isset($mybb->input['send_submit']) && $mybb->request_method == 'post'
                && $mybb->input['my_form'] == 'invite') {
            // se verifica autenticitatea cererii
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                $errors1[] = $lang->advinvsys_errors_invalidreq;
            }

            $email = $db->escape_string($mybb->input['email']);
            $message = $db->escape_string($mybb->input['message']);

            $plugins->run_hooks('advinvsys_mainpage_do_invite_start');

            // se verifica faptul ca adresa de e-mail introdusa este una valida
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // se va genera o eroare
                $errors1[] = $lang->advinvsys_invite_invalidemail;
            }

            // se verifica daca nu cumva isi acorda o invitatie tot lui
            if (strcmp($email, $mybb->user['email']) == 0) {
                // iar vom genera o eroare
                $errors1[] = $lang->advinvsys_invite_selfinvite;
            }

            // verificam daca nu cumva aceeasi adresa de email a fost invitata deja
            if (!empty($email) && $AISDevelop->checkInvitationSent('email', $email)) {
                // va aparea un nou mesaj
                $errors1[] = $lang->advinvsys_invite_multiple;
            }

            // se verifica daca se poate lua o invitatie de la cel care invita...
            $invs = $AISDevelop->checkInvitations('uid', $mybb->user['uid'], 1);
            if ($invs == false) {
                // donatorul nu are suficiente invitatii
                // apare eroare
                $errors1[] = $lang->sprintf($lang->advinvsys_send_notenough, $mybb->user['invitations']);
            }

            // se poate trimite invitatia?
            if (count($errors1) == 0) {
                // se scade celui care a invitat o invitatie
                $AISDevelop->addInvitations('uid', $mybb->user['uid'], -1, false, false);

                // mai trebuie sa introducem un log in baza de date
                $AISDevelop->addLog($lang->advinvsys_invite_log_type, 
                    $lang->sprintf($lang->advinvsys_invite_log, $email),
                    $mybb->user['uid']);

                // de asemenea se va trimite si un email]
                $body = nl2br($mybb->settings['advinvsys_setting_sendemail']);
                $message = $parser->parse_message($message, array(
                    'allow_html' => 0,
                    'allow_smilies' => 0,
                    'allow_mycode' => 1,
                    'filter_badwords' => 1
                        ));
                
                $key = $AISDevelop->generateInvitation($mybb->user['uid'], $email, true);
                if (!empty($email))
                    my_mail($email, 'Invitation', $AISDevelop->parseBody($body, $key, $message), 
                        '', '', '', false, 'html');

                $plugins->run_hooks('advinvsys_mainpage_do_invite_end');

                // se face redirectionarea
                redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys', 
                        $lang->sprintf($lang->advinvsys_invite_gived, 1, intval($invs - 1)));
            } else {
                // se afiseaza erorile frumos pe ecran
                $errors1 = inline_error($errors1);
            }
        } else if (isset($mybb->input['buy_submit']) && $mybb->request_method == 'post'
                && $mybb->input['my_form'] == 'buy') {
            // se verifica autenticitatea cererii
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                $errors2[] = $lang->advinvsys_errors_invalidreq;
            }

            $amount = floatval($mybb->input['price']);
            $number = floatval($mybb->input['number']);
            $gateway = $db->escape_string($mybb->input['gateway']);
            $avaible = array(
                'newpoints'
            );

            $plugins->run_hooks('advinvsys_mainpage_do_buy_start');

            if (!in_array($gateway, $avaible)) {
                // se va genera o eroare
                $errors2[] = $lang->advinvsys_buy_invalidgateway;
            }

            if ($amount <= 0) {
                // eroare si de aceasta data
                $errors2[] = $lang->advinvsys_buy_invalidprice;
            }

            // verificam daca se poate lua suma necesara
            $check = advinvsys_check_convert($mybb->user['uid'], $amount, $gateway);
            if (!$check) {
                // eroare
                $errors2[] = $lang->advinvsys_buy_notenough;
            }

            // se poate procesa cererea?
            if (count($errors2) == 0) {
				// se iau punctele utilizatorului
                advinvsys_take_convert($mybb->user['uid'], $amount, $gateway);

                // se acorda numarul de invitatii specificat
                $AISDevelop->addInvitations('uid', $mybb->user['uid'], $number, false, false);

                // se adauga si un jurnal in sistem
                $AISDevelop->addLog($lang->advinvsys_buy_np_logt, 
                    $lang->sprintf($lang->advinvsys_buy_np_log, $amount, 
                        $AISDevelop->truncNumber($number)), 
                    $mybb->user['uid']);

                // un simplu carlig
                $plugins->run_hooks('advinvsys_mainpage_do_buy_end');

                // se realizeaza o redirectionare
                redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys', 
                        $lang->sprintf($lang->advinvsys_buy_succes, $number, 
                                $AISDevelop->truncNumber($mybb->user['invitations'] + $number)));
            } else {
                // se afiseaza erorile frumos pe ecran
                $errors2 = inline_error($errors2);
            }
        } else if (isset($mybb->input['type'])
                && $mybb->input['type'] == 'resend') {
            // se verifica autenticitatea cererii
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $errors0[] = $lang->advinvsys_errors_invalidreq;
            }

            $cid = intval($mybb->input['id']);

            $plugins->run_hooks('advinvsys_mainpage_do_resend_start');

            $interval = (is_numeric($mybb->settings['advinvsys_setting_intresend'])) ? 
                abs($mybb->settings['advinvsys_setting_intresend']) : 3;
            $query = $db->simple_select('advinvsys_codes', '*', 'date < ' .
                    intval(TIME_NOW - $interval * 86400) .
                    ' AND cid = ' . intval($cid));
            
            if ($db->num_rows($query) == 0) {
                // invitatia nu exista in sistem sau nu poti retrimite invitatia
                $errors0[] = $lang->advinvsys_resend_err1;
            } else {
                $row = $db->fetch_array($query);
                
                // verificam daca invitatia apartine utilizatorului curent
                if (intval($row['did']) != $mybb->user['uid'])
                    $errors0[] = $lang->advinvsys_giveback_err2;
                    
                // verificam daca are atasata o adresa de email
                if (empty($row['email']))
                    $errors0[] = $lang->advinvsys_resend_err2;
            }

            // daca nu au aparut erori?!
            if (count($errors0) == 0) {
                // se va retrimite invitatia catre adresa de e-mail specificata
                $body = nl2br($mybb->settings['advinvsys_setting_sendemail']);
                $message = $parser->parse_message($message, array(
                    'allow_html' => 0,
                    'allow_smilies' => 0,
                    'allow_mycode' => 1,
                    'filter_badwords' => 1
                        ));
                $key = $row['code'];
                my_mail($row['email'], 'Invitation', $AISDevelop->parseBody($body, $key, $message), 
                        '', '', '', false, 'html');

                // sa nu uitam sa facem update la data trimiterii invitatiei
                $db->update_query('advinvsys_codes', array('date' => TIME_NOW), 
                        'cid = \'' . $cid . '\'');

                $plugins->run_hooks('advinvsys_mainpage_do_resend_end');

                // se va face redirectionarea
                redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys', 
                        $lang->sprintf($lang->advinvsys_resend_succ, $row['email']));
            } else {
                // se afiseaza erorile frumos pe ecran
                $errors0 = inline_error($errors0);
            }
        } else if (isset($mybb->input['type'])
                && $mybb->input['type'] == 'giveback') {
            // se verifica autenticitatea cererii
            if (!isset($mybb->input['my_post_key']) || 
                    $mybb->post_code != $mybb->input['my_post_key']) {
                $errors0[] = $lang->advinvsys_errors_invalidreq;
            }

            $cid = intval($mybb->input['id']);

            $plugins->run_hooks('advinvsys_mainpage_do_takeback_start');

            $expr = (is_numeric($mybb->settings['advinvsys_setting_dateexpr'])) ? 
                abs($mybb->settings['advinvsys_setting_dateexpr']) : 7;
            $query = $db->simple_select('advinvsys_codes', '*', 'date < ' .
                    intval(TIME_NOW - $expr * 86400) .
                    ' AND cid = ' . intval($cid));
            
            if ($db->num_rows($query) == 0) {
                // invitatia nu a expirat sau ea nu exista in sistem
                $errors0[] = $lang->advinvsys_giveback_err1;
            } else {
                // daca exista cel putin o invitatie
                $row = $db->fetch_array($query);
                // verificam daca invitatia apartine userului curent
                if (intval($row['did']) != $mybb->user['uid']) {
                    $errors0[] = $lang->advinvsys_giveback_err2;
                }
            }

            // daca se ajunge aici atunci totul e in regula
            if (count($errors0) == 0) {
                // se sterge invitatia din baza de date
                $db->delete_query('advinvsys_codes', 'cid = \'' . intval($cid) . '\'');

                $plugins->run_hooks('advinvsys_mainpage_do_takeback_end');

                if ($mybb->settings['advinvsys_setting_giveexpr'] == 1) {
                    // se acorda o invitatie
                    $AISDevelop->addInvitations('uid', $row['did'], 1);

                    // redirect
                    redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys', 
                            $lang->advinvsys_giveback_succ1);
                } else {

                    // se face redirectionarea
                    redirect($mybb->settings['bburl'] . '/usercp.php?action=advinvsys',
                            $lang->advinvsys_giveback_succ2);
                }
            } else {
                // se afiseaza erorile frumos pe ecran
                $errors0 = inline_error($errors0);
            }
        }
        
        // un extraheader este adaugat
        eval("\$extraheader = \"" . $templates->get("advinvsys_preview_header") . "\";");
        $content .= $errors0;

        $plugins->run_hooks('advinvsys_mainpage_top');

        // se realizeaza paginarea in vederea afisarii primului tabel
        $per_page = 10; // in mod implicit
        if ($mybb->input['page'] && intval($mybb->input['page']) > 1) {
            $mybb->input['page'] = intval($mybb->input['page']);
            $start = ($mybb->input['page'] * $per_page) - $per_page;
        } else {
            $mybb->input['page'] = 1;
            $start = 0;
        }

        // se obtine id-ul utilizatorului curent
        $uid = $mybb->user['uid'];

        // acum paginarea este in regula, se trece la obtinerea datelor din tabel
        $query = $db->simple_select('advinvsys_codes', 'COUNT(cid) AS total', 
                'did = \'' . $uid . '\'');
        // variabila ce retine numarul de randuri obtinute din interogare
        $total_rows = $db->fetch_field($query, 'total');

        // tabelul cu invitatii create
        $table = new AISTable;
        $table->construct_header($lang->advinvsys_created_email, array('width' => '25%'));
        $table->construct_header($lang->advinvsys_created_code, array('width' => '35%'));
        $table->construct_header($lang->advinvsys_created_date, array('width' => '15%'));
        $table->construct_header($lang->advinvsys_created_expr, array('width' => '15%'));
        $table->construct_header($lang->advinvsys_created_acts, array('width' => '10%', 'class' => 'align_center'));

        // se creaza interogarea
        $query = $db->simple_select('advinvsys_codes', '*', 'did = \'' . $uid . '\'', 
                array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => $start . ',' . $per_page));
        $expiration_days = (is_numeric($mybb->settings['advinvsys_setting_dateexpr']))
            ? abs($mybb->settings['advinvsys_setting_dateexpr'])
            : 7;
        // se creaza tabelul rand cu rand
        while ($row = $db->fetch_array($query)) {
            $table->construct_cell((empty($row['email']) ? '-' : $row['email']), array('class' => 'align_center'));
            $table->construct_cell($row['code'], array('class' => 'align_center'));
            $table->construct_cell(my_date($mybb->settings['dateformat'], intval($row['date']), '', false), array('class' => 'align_center'));
            $date_expr = strtotime('+' . $expiration_days . ' day', intval($row['date']));
            $table->construct_cell(my_date($mybb->settings['dateformat'], $date_expr, '', false), array('class' => 'align_center'));
            $table->construct_cell('<a href="usercp.php?action=advinvsys&amp;type=giveback&amp;id=' . intval($row['cid']) . '&amp;my_post_key=' . $mybb->post_code . '" class="tooltip"><img src="./images/advinvsys/getback.gif"/><span>' . $lang->advinvsys_created_getback . '</span></a>&#160;<a href="usercp.php?action=advinvsys&amp;type=resend&amp;id=' . intval($row['cid']) . '&amp;my_post_key=' . $mybb->post_code . '" class="tooltip"><img src="./images/advinvsys/resend.gif"/><span>' . $lang->advinvsys_created_resend . '</span></a>', array('class' => 'align_center'));
            $table->construct_row();
        }

        // in cazul in care nu a existat niciun rand intors din baza de date atunci se afiseaza un mesaj central
        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->advinvsys_created_noinvs, array('class' => 'align_center', 'colspan' => 5));
            $table->construct_row();
        }

        // in final se afiseaza tabelul pe ecranul utilizatorului
        $content .= $table->construct_html($lang->advinvsys_created_title . '<div style=\'float: right;\'><a href=\'usercp.php?action=advinvsys&amp;method=send\' class=\'advinvsys_send_button\' id=\'advinvsys_send_button\'>&nbsp;</a></div>');
        // se realizeaza paginarea
        $content .= multipage($total_rows, $per_page, $mybb->input['page'], "usercp.php?action=advinvsys&amp;page={page}");

        $plugins->run_hooks('advinvsys_mainpage_middle');

        // formularul de trimitere a unei invitatii prin email
        $codebuttons = build_mycode_inserter();
        eval("\$invite_form = \"" . $templates->get("advinvsys_invite_form") . "\";");
        $content .= $invite_form;

        // formularul de cumparare a unor invitatii
        $options = '<option value="-">Choose one gateway</option>';
        $cost = array();

        // daca exista NewPoints instalat in system!
        if ($mybb->settings['advinvsys_setting_newpointsenable'] == 1 && 
                $AISDevelop->isInstalled('newpoints')) {
            $options .= '<option value="newpoints">NewPoints</option>';
            $cost['newpoints']['cost'] = (is_numeric($mybb->settings['advinvsys_setting_newpointscost'])) ? 
                floatval($mybb->settings['advinvsys_setting_newpointscost']) : 10;
            $cost['newpoints']['currency'] = (!empty($mybb->settings['newpoints_main_curname'])) ? 
                $mybb->settings['newpoints_main_curname'] : 'Points';
        }

        // este activa plata prin Paypal
        if (!empty($mybb->settings['advinvsys_setting_paypalemail']) &&
                filter_var($mybb->settings['advinvsys_setting_paypalemail'], FILTER_VALIDATE_EMAIL)) {
            $options .= '<option value="paypal">Paypal</option>';
            $cost['paypal']['cost'] = (is_numeric($mybb->settings['advinvsys_setting_paypalcost'])) ? 
                floatval($mybb->settings['advinvsys_setting_paypalcost']) : 1;
            $cost['paypal']['currency'] = $mybb->settings['advinvsys_setting_paypalcurr'];
        }

        // este activ sistemul de plata prin SMS
        if ($mybb->settings['advinvsys_setting_smsenable'] == 1 &&
                !empty($mybb->settings['advinvsys_setting_smssecret']) &&
                !empty($mybb->settings['advinvsys_setting_smsid']) &&
                !empty($mybb->settings['advinvsys_setting_smssig'])) {
            $options .= '<option value="fortumo">SMS - Fortumo</option>';
        }
        
        // se ofera posibilitatea de a se adauga si alte sisteme de plata
        $plugins->run_hooks('advinvsys_payment_add');

        if (!empty($options)) {
            $options1 = '<select name="number" id="number" size="1"><option value="-">Chose one value  </option>';
            $array = explode(',', $mybb->settings['advinvsys_setting_packages']);
            foreach ($array as $value) {
                if (is_numeric($value) && $value > 0) {
                    $options1 .= '<option value="' . $value . '">' . $value . '</option>';
                }
            }
            $options1 .= '</select>';
            
            $plugins->run_hooks('advinvsys_payment_javascript');
            
            $extraheader .= '<script type="text/javascript">
            Array.prototype.contains = function(element) {
                for (var i = 0; i < this.length; i++)
                    if (this[i] == element) 
                        return i;
                return -1;
            };
            Event.observe(document, "dom:loaded", function() {
                function checkNumber() {
                    var value = $(\'number\').getValue();
                    var gateway = $(\'gateway\').getValue();
                    switch (value) {
                        case "-" :
                            $(\'price\').value = \'0\';
                            $(\'currency\').innerHTML = \'EUR\';
                            $(\'buy_submit\').hide();
                            break;
                        default :
                            // newpoints / paypal / SMS
                            if (gateway == "paypal") {
                                $(\'price\').value = parseFloat(value) * parseFloat(' . (isset($cost['paypal']['cost']) ? $cost['paypal']['cost'] : 0) . ');
                                $(\'currency\').innerHTML = \'' . (isset($cost['paypal']['currency']) ? $cost['paypal']['currency'] : 'EUR') . '\';
                                $(\'special_line\').innerHTML = \'<td class="trow2" colspan="2"><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick"/><input type="hidden" name="business" value="' . $mybb->settings['advinvsys_setting_paypalemail'] . '"/><input type="hidden" name="amount" value="\' + $(\'price\').getValue() + \'"><input type="hidden" name="currency_code" value="' . (isset($cost['paypal']['currency']) ? $cost['paypal']['currency'] : 'EUR') . '"/><input type="hidden" name="item_name" value="Buy Invitations"/><input type="hidden" name="item_number" value="' . $mybb->user['uid'] . '"/><input type="hidden" name="image_url" value="' . $theme['logo'] . '"/><input type="hidden" name="no_shipping" value="1"/><input type="hidden" name="notify_url" value="' . $mybb->settings['bburl'] . '/inc/plugins/advinvsys/paypal_gateway.php"/><input type="hidden" name="return" value="' . $mybb->settings['bburl'] . '/"/><input type="hidden" name="cancel_return" value="' . $mybb->settings['bburl'] . '"/><center><input type="image" src="images/advinvsys/paypal_button.gif" border="0" name="submit" alt="Make payments with PayPal!"/></center></form></td>\';          
                                $(\'special_line\').show(); 
                                $(\'buy_submit\').hide();
                            }
                            else if (gateway == "newpoints") {
                                $(\'price\').value = parseFloat(value) * parseFloat(' . (isset($cost['newpoints']['cost']) ? $cost['newpoints']['cost'] : 0) . ');
                                $(\'currency\').innerHTML = \'' . (isset($cost['newpoints']['currency']) ? $cost['newpoints']['currency'] : 'EUR') . '\';
                                $(\'buy_submit\').show();
                            }                    
                    }
                }
                // se verifica daca valoarea introdusa ca suma este intreaga
                $(\'gateway\').observe(\'change\', function() {
                    var value = this.getValue();
                    switch (value) {
                        case "-" :
                            $(\'number\').innerHTML = \'<option value="-">Chose one value  </option>\';
                            $(\'special_line\').hide();
                            break;
                        case "fortumo" : 
                            //$(\'special_line\').innerHTML = \'' . $lang->advinvsys_buy_smscountry . '\';
                            new Ajax.Request(\'xmlhttp.php?action=ais_smsfortumo\', {
                                method: \'post\', 
                                onCreate: function() { 
                                    $(\'special_line\').innerHTML = \'<td class="trow2" colspan="2" align="center"><img src="images/advinvsys/load.gif"/></td>\';
                                    $(\'special_line\').show(); 
                                },
                                onComplete: function(data) { 
                                    text = data.responseText.replace(/(\r\n|\n|\r)/gm, "");
                                    if(text.match(/<success>(.*)<\/success>/)) {
                                        htmlb = text.match(/<success>(.*)<\/success>/);
                                        $(\'special_line\').innerHTML = \'<td class="trow2" colspan="2">\' 
                                            + htmlb[0] + \'</td>\'; 
                                    }
                                }
                            });
                            break;
                        case "newpoints" :
                            // newpoints
                            $(\'number\').innerHTML = \'' . $options1 . '\';
                            $(\'special_line\').innerHTML = \'\' ;
                            $(\'special_line\').hide();
                            break;
                        case "paypal" :
                            // paypal
                            $(\'number\').innerHTML = \'' . $options1 . '\';
                            break;
                    }
                    checkNumber();
                });
                $(\'number\').observe(\'change\', checkNumber);
                checkNumber();
                // in mod implicit se ascunde randul
                $(\'special_line\').hide();
            });
            </script>';
            eval("\$buy_form = \"" . $templates->get("advinvsys_buy_form") . "\";");
            $content .= '<br/>' . $buy_form;
        }
    }

    $plugins->run_hooks('advinvsys_mainpage_down');

    // se evalueaza sablonul curent
    eval("\$page = \"" . $templates->get("advinvsys_standard_page") . "\";");
    // in fine, codul HTML e afisat pe ecran
    output_page($page);
}

// Se incarca toate modulele activate pentru aceasta modificare
function advinvsys_load_modules() {
    global $cache, $plugins, $mybb, $theme, $db, $templates;

    // se intoarce lista cu modulele active
    $modules = $cache->read("advinvsys_modules");

    $plugins->run_hooks('advinvsys_modules_do_load');

    if (is_array($modules['active'])) {

        // pentru fiecare modul din lista
        foreach ($modules['active'] as $module) {
            if ($module != "" && file_exists(MYBB_ROOT . "inc/plugins/advinvsys/plugins/" . $module . ".php")) {
                require_once MYBB_ROOT . "inc/plugins/advinvsys/plugins/" . $module . ".php";
            }
        }
    }
}

// Functia de tip handler pentru partea de administrare
function advinvsys_admin_action_handler(&$action) {
    // se adauga o noua actiune in vectorul cu acelasi nume
    $action['advinvsys'] = array('active' => 'advinvsys', 'file' => 'advinvsys.php');
}

// Functia care adauga un nou link in cadrul meniului paginii de administrare intitulata "Configuration"
function advinvsys_admin_menu(&$sub_menu) {
    global $lang;

    // se incarca fisierul de limba al modificarii
    $lang->load("advinvsys");

    // se realizeaza prelucrarile de date
    end($sub_menu);
    $key = (key($sub_menu)) + 10;
    if (!$key) {
        $key = '50';
    }

    $sub_menu[$key] = array('id' => 'advinvsys', 'title' => $lang->advinvsys_modname,
        'link' => "index.php?module=config-advinvsys");
}

// Functia care mai adauga inca un tab in cadrul sectiunii de editare a unui grup din Admin CP
function advinvsys_admin_groups_edit_tab($tabs) {
    global $lang;

    // se include fisierul de limba
    $lang->load("advinvsys");

    // se adauga tab-ul in sistem
    $tabs['advinvsys'] = $lang->advinvsys_tabs_groups;
}

// Functia care afiseaza tab-ul
function advinvsys_admin_groups_edit() {
    global $lang, $form, $mybb, $plugins, $AISDevelop;

    echo "<div id=\"tab_advinvsys\">";
    $form_container = new FormContainer($lang->advinvsys_tabs_groups);

    $form_container->output_row($lang->advinvsys_tabs_groups_maxinv, 
            $lang->advinvsys_tabs_groups_maxinv_desc, 
            $form->generate_text_box('max_invitations',
                $AISDevelop->truncNumber($mybb->input['max_invitations']),
                array('id' => 'max_invitations')), 
            'max_invitations');

    $plugins->run_hooks('advinvsys_groups_edit');

    $form_container->end();
    echo "</div>";
}

// Functia care salveaza datele din cadrul tab-ului creat cu ajutorul fct. de mai sus
function advinvsys_admin_groups_edit_save() {
    global $mybb, $updated_group, $plugins, $AISDevelop;

    $updated_group['max_invitations'] = $AISDevelop->truncNumber($mybb->input['max_invitations']);

    $plugins->run_hooks('advinvsys_groups_do_edit');
}

// Functia care verifica daca e posibila o consersie unor puncte NewPoints 
// (etc.) in invitatii
function advinvsys_check_convert($uid, $amount, $system = 'newpoints') {
    global $mybb, $db, $plugins, $AISDevelop;

    try {
        if ($amount <= 0 || $uid <= 0)
            return;

        // ce sistem se foloseste ?
        if ($system == 'newpoints') {
            $userid = $mybb->user['uid'];

            if ($userid == $uid) {
                if ($amount > $mybb->user['newpoints'])
                    return false;
                else
                    return $mybb->user['newpoints'];
            }
            else {
                // se realizeaza o noua interogare
                $query = $db->simple_select('users', 'newpoints', 'uid = \'' .
                        intval($uid) . '\' AND newpoints >= ' . $AISDevelop->truncNumber($amount), array('limit' => 1));

                if ($row = $db->fetch_array($query))
                    return $AISDevelop->truncNumber($row['invitations']);
                else
                    return false;
            }
        } else {
            // altfel se da posibilitatea adaugarii unui alt sistem de convertire
            $plugins->run_hooks('advinvsys_convert_do_check');
        }
    } catch (Exception $e) {
        return false;
    }
}

// Functia care realizeaza consersia unor puncte NewPoints (etc.) in invitatii
function advinvsys_take_convert($uid, $points, $system = 'newpoints') {
    global $db, $mybb, $plugins, $advinvsys_queries;

    if ($points == 0 || $uid <= 0)
        return;

    if ($system == 'newpoints') {
        $advinvsys_queries[] = "UPDATE " . TABLE_PREFIX . "users SET newpoints = newpoints - '"
                . floatval(round($points, intval($mybb->settings['newpoints_main_decimal']))) . "' 
                WHERE uid = '" . intval($uid) . "'";

        // se va executa mai tarziu
        add_shutdown('advinvsys_run_queries');
    } else {
        // pentru alte sisteme
        $plugins->run_hooks('advinvsys_convert_do_take');
    }
}

// Functia care ruleaza mai multe interogari cu baza de date deodata
function advinvsys_run_queries() {
    global $db, $advinvsys_queries;

    // pentru fiecare interogare
    foreach ($advinvsys_queries as $key => $query)
        $db->write_query($query);

    // la final vectorul de interogari devine null
    unset($advinvsys_queries);
}

// Functia care intoarce datele necesare platii prin SMS-Fortumo
function advinvsys_get_sms_data($url, $code, $err1, $err2) {
    global $lang, $templates;
    try {
        $lang->load("advinvsys");
        
        $xml = simplexml_load_file($url);

        // totul e bine
        // cautam tara specificata in lista
        $i = 0;

        foreach ($xml->xpath(sprintf('/services_api_response/service/countries/country[@approved="true" and @code="%s"]', $code)) as $country) {
            $promotional = $country->promotional_text->english;
            if ($promotional == '')
                $promotional = $country->promotional_text->local;

            $promotional = str_replace('Support', '<br>Support', $promotional);
            $promotional = str_replace('Mobile', '<br>Mobile', $promotional);

            $plugins->run_hooks('advinvsys_sms_do_check');

            $keyword = $country->prices->price->message_profile->attributes()->keyword;
            $shortcode = $country->prices->price->message_profile->attributes()->shortcode;
            $country = $country->attributes()->name;
            
            eval("\$result = \"" . $templates->get('advinvsys_sms_buy') . "\";");
            return $result;

            $i = 1;
            break;
        }
        if ($i == 0) {
            return "<center>{$err1}</center>";
        }
    } catch (Exception $ex) {
        return "<center>{$err2}</center>";
    }
}

// Functia care intoarce codul tarii din care provine un utilizator
function advinvsys_country_recognition($ip) {
    // se incearca gasirea tarii din care este vizitatorul
    $url = 'http://api.hostip.info/country.php?ip=' . $ip;

    // se va folosi api-ul celor de la HostIP
    $country = @file_get_contents($url);

    // codul nu poate avea mai mult de 5 litere
    if (!empty($country) && strlen($country) < 5) {
        return $country;
    } else {
        return 'XX';
    }
}
?>
