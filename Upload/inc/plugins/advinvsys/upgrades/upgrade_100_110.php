<?php
/**
 * Functia prin care se face actualizarea versiunii 1.0.0 la 1.1.0
 * fara a fi nevoie de a reinstala aplicatia
 **/
function upgrade_100_110_run()
{
    global $db, $AISDevelop;
    
    try {
        // SCHIMBARI IN COLOANELE TABELELOR BAZEI DE DATE
        $AISDevelop->alterColumn('advinvsys_incomes', 'invitations', "DECIMAL(30,2) UNSIGNED NOT NULL default '0'", true);
        $AISDevelop->alterColumn('advinvsys_incomes', 'additional', "DECIMAL(30,2) UNSIGNED NOT NULL default '0'", true);
        $AISDevelop->alterColumn('users', 'invitations', "DECIMAL(30,2) UNSIGNED NOT NULL default '0'", true);
        $AISDevelop->alterColumn('usergroups', 'max_invitations', "DECIMAL(30,2) NOT NULL default '-1.00'", true);
        

        // SE ADAUGA SABLOANE IN BAZA DE DATE
        $templates = array(0, 1, 12);
        $AISDevelop->addTemplates($templates, true);
        
        // SE MODIFICA UNELE SABLOANE DEJA EXISTENTE
        require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
        find_replace_templatesets("stats", "#" . preg_quote('<strong>{$stats[\'numusers\']}</strong>') . "#i", '<strong>{$stats[\'numusers\']}</strong><br />{$lang->advinvsys_stats_total} <strong>{$stats[\'totalinvs\']}</strong>');
        find_replace_templatesets("stats", "#" . preg_quote('<strong>{$repliesperthread}</strong>') . "#i", '<strong>{$repliesperthread}</strong><br />{$lang->advinvsys_stats_perusers} <strong>{$advinvsys_invsperusers}</strong>');
        find_replace_templatesets("advinvsys_standard_page", "#" . preg_quote('<span class="smalltext">[Version : {$version}, Release Date : {$date}]</span>') . "#i", '<img src="./images/advinvsys/version.png" alt="Version"/>');

        // SE SCHIMBA VERSIUNEA ACESTEI MODIFICARI
        $AISDevelop->versionChange('1.1.0', '5.2.0');
        
        // totul s-a realizat cu succes
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
