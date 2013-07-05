<?php

/*
  @author     : Surdeanu Mihai ;
  @date       : 16 aprilie 2012 ;
  @version    : 1.0.1 ;
  @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
  @description: Modificare permite introducerea unui sistem de invitatii pe forumul tau!
  @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
  @copyright  : Licenta speciala. Pentru mai multe detalii te rugam sa citesti sectiunea Licenta din cadrul fisierului
  ReadME.pdf care poate fi gasit in acest pachet. Iti multumim pentru intelegere!
  ====================================
  Ultima modificare a codului : 16.04.2012 19:18
 */

// Poate fi acesat direct fisierul?
if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

// Daca modificarea nu e instalata atunci nu se mai intra in "if"
if (advinvsys_is_installed()) {
    global $db, $plugins, $mybb, $AISDevelop;

    // incercam sa utilizam cache-ul
    $rules = $mybb->cache->read('advinvsys_rules');
    // e corect cache-ul ?
    if (!$rules || !is_array($rules)) {
        // daca nu se va face update la cache
        $array = array();
        $query = $db->simple_select('advinvsys_incomes', 
                'iid,fid,gid,type,invitations,additional', 'enabled = \'1\'');
        while ($row = $db->fetch_array($query)) {
            $array[$row['iid']] = $row;
        }
        // se face actualizarea cache-ului cu informatiile dorite
        $mybb->cache->update('advinvsys_rules', $array);
    }

    // in final se va utiliza tot cache-ul
    $conversion_table = array(
        'newthread' => array(
            'hook' => 'datahandler_post_insert_thread',
            'type' => 1
        ),
        'newreply' => array(
            'hook' => 'datahandler_post_insert_post',
            'type' => 1
        ),
        'newpoll' => array(
            'hook' => 'polls_do_newpoll_process',
            'type' => 2
        ),
        'pollpervote' => array(
            'hook' => 'polls_vote_process',
            'type' => 2
        ),
        'threadrate' => array(
            'hook' => 'ratethread_process',
            'type' => 2
        ),
        'deletepost' => array(
            'hook' => 'class_moderation_delete_post',
            'type' => 3
        ),
        'deletethread' => array(
            'hook' => 'class_moderation_delete_thread',
            'type' => 4
        ),
        'newregistration' => array(
            'hook' => 'member_do_register_end',
            'type' => 5
        ),
        'newreputation' => array(
            'hook' => 'reputation_do_add_process',
            'type' => 6
        )
    );
    // se pot adauga noi reguli de crestere a numarului de invitatii
    $conversion_table = $plugins->run_hooks('advinvsys_rule_do_add_start', $conversion_table);

    if (is_array($rules) && count($rules) > 0) {
        // pentru fiecare regula obtinuta
        foreach ($rules as $row) {
            // exista tipul in tabela de conversie si se acorda / iau invitatii
            if (!isset($conversion_table[$row['type']]) || floatval($row['invitations']) == 0)
                continue;

            $plugins->run_hooks('advinvsys_rule_do_add_check');

            $func = 'advinvsys_income_' . intval($row['iid']);
            if (function_exists($func))
                continue;

            // carligul
            $hook = $conversion_table[$row['type']]['hook'];
            $type = $conversion_table[$row['type']]['type'];

            // se creaza functia atasata carligului
            if ($type == 1) {
                eval('function ' . $func . '() {
                global $db, $mybb, $fid, $post, $AISDevelop;
                if ($mybb->input[\'action\'] != \'do_' . $row['type'] . '\' || $mybb->input[\'savedraft\'] || $post[\'savedraft\'])
                    return;
                if (!$mybb->user[\'uid\'])
                    return;
                $fids = explode(",", "' . $row['fid'] . '");
                $gids = explode(",", "' . $row['gid'] . '");
                if (!in_array($fid, $fids) || !in_array($mybb->user[\'usergroup\'], $gids))
                    return;
                $amount = floatval("' . $row['invitations'] . '");
                $AISDevelop->addInvitations(\'uid\', $mybb->user[\'uid\'], $amount);
                }');
            } else if ($type == 2) {
                eval('function ' . $func . '() {
                global $db, $mybb, $fid, $AISDevelop;
                if (!$mybb->user[\'uid\'])
                    return;
                $fids = explode(",", "' . $row['fid'] . '");
                $gids = explode(",", "' . $row['gid'] . '");
                if (!in_array($fid, $fids) || !in_array($mybb->user[\'usergroup\'], $gids))
                    return;
                $amount = floatval("' . $row['invitations'] . '");
                $AISDevelop->addInvitations(\'uid\', $mybb->user[\'uid\'], $amount);
                }');
            } else if ($type == 3) {
                eval('function ' . $func . '($pid) {
                global $db, $mybb, $fid, $post, $AISDevelop;
                if (!$mybb->user[\'uid\'])
                    return;
                $fids = explode(",", "' . $row['fid'] . '");
                $gids = explode(",", "' . $row['gid'] . '");
                $user = get_user($post[\'uid\']);
                if (!in_array($fid, $fids) || !in_array($user[\'usergroup\'], $gids))
                    return;
                $amount = floatval("' . $row['invitations'] . '");
                $AISDevelop->addInvitations(\'uid\', $post[\'uid\'], -$amount);
                }');
            } else if ($type == 4) {
                eval('function ' . $func . '($tid) {
                global $db, $mybb, $AISDevelop;
                if (!$mybb->user[\'uid\'])
                    return;
                $fids = explode(",", "' . $row['fid'] . '");
                $gids = explode(",", "' . $row['gid'] . '");
                $thread = get_thread($tid);
		$fid = $thread[\'fid\'];
                $post = get_post($thread[\'firstpost\']);
                $user = get_user($post[\'uid\']);
                if (!in_array($fid, $fids) || !in_array($user[\'usergroup\'], $gids))
                    return;
                $amount = floatval("' . $row['invitations'] . '");
                $AISDevelop->addInvitations(\'uid\', $thread[\'uid\'], -$amount);
                if ($thread[\'poll\'] != 0) {
                    $amount1 = floatval("' . $row['additional'] . '");
                    $AISDevelop->addInvitations(\'uid\', $thread[\'uid\'], -$amount1);
                }
                }');
            } else if ($type == 5) {
                eval('function ' . $func . '() {
                global $db, $mybb, $AISDevelop;
                $amount = floatval("' . $row['invitations'] . '");
                $AISDevelop->addInvitations(\'username\', $mybb->input[\'username\'], $amount);
                if ($mybb->input[\'referrername\']) {
                    $amount1 = floatval("' . $row['additional'] . '");
                    if ($amount1 != 0)
                        $AISDevelop->addInvitations(\'username\', $mybb->input[\'referrername\'], $amount1);
                }
                }');
            } else if ($type == 6) {
                eval('function ' . $func . '() {
                global $db, $mybb, $reputation, $AISDevelop;
                $amount = floatval("' . $row['invitations'] . '");
                $reputation = $reputation[\'reputation\'];
                $AISDevelop->addInvitations(\'uid\', $reputation[\'uid\'], floatval($reputation * $amount));
                $amount1 = floatval("' . $row['additional'] . '");
                if ($amount1 != 0)
                    $AISDevelop->addInvitations(\'uid\', $reputation[\'adduid\'], $amount1);
                }');
            } else {
                // se ofera posibilitatea adaugarii unui carlig
                $plugins->run_hooks('advinvsys_rule_do_add_end');
            }

            // este adaugat carligul
            $plugins->add_hook($hook, $func);
        }
    }
}
?>
