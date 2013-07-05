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
  Ultima modificare a codului : 16.04.2012 18:45
 */

// Din aceasta functie se apeleaza alte lucruri 
function task_advinvsys_task($task) {
    global $mybb, $db, $lang, $cache, $plugins;

    // se incarca fisierul de limba
    $lang->load('advinvsys');

    $plugins->run_hooks('advinvsys_task_start');

    // se apeleaza functiile implicite
    $updated = advinvsys_task_givecredit();

    // daca s-a actualizat cel putin un rand in tabel se adauga un log in sistem
    if ($updated > 0 && function_exists('advinvsys_add_log'))
        advinvsys_add_log($lang->advinvsys_task_givecredittype, 
                $lang->sprintf($lang->advinvsys_task_givecredit, $updated), 0);

    $plugins->run_hooks('advinvsys_task_end');

    // se sterg din baza de date toate log-urile mai vechi de x zile ( x = 30 )
    $db->delete_query('advinvsys_logs', 'date <= ' . intval(TIME_NOW - 30 * 86400));
    
    // se adauga si un log in sistemul intern
    add_task_log($task, $lang->advinvsys_task_ran);
}

// Prin intermediul acestei functii se acorda invitatii in functie de perioada petrecuta pe forum
function advinvsys_task_givecredit() {
    global $db, $mybb, $plugins;

    // care este unitatea de timp dorita?
    $invs_type = intval($mybb->settings['advinvsys_setting_timeonlinetype']) * 3600;

    // cate invitatii se acorda pe unitatea de timp
    $invs_howmany = abs(number_format(floatval($mybb->settings['advinvsys_setting_timeonlinehow']), 2));

    // daca valoarea setarii e 0 atunci se dezactiveaza aceasta caracteristica
    if ($invs_howmany == 0)
        return 0;

    $plugins->run_hooks('advinvsys_task_timeonline');

    // totul se va face dintr-o singura interogare
    // ne asiguram ca nu se depaseste numarul maxim de invitatii per group!
    $db->write_query("UPDATE " . TABLE_PREFIX . "users u 
        INNER JOIN " . TABLE_PREFIX . "usergroups AS g ON u.usergroup = g.gid SET 
        u.invitations = IF(g.max_invitations > -1,
            LEAST(g.max_invitations, u.invitations + {$invs_howmany} * (GREATEST(0, u.timeonline - u.last_timeonline) DIV {$invs_type})),
            u.invitations + {$invs_howmany} * (GREATEST(0, u.timeonline - u.last_timeonline) DIV {$invs_type})
        ), u.last_timeonline = u.last_timeonline + {$invs_type} * (GREATEST(0, u.timeonline - u.last_timeonline) DIV {$invs_type}) 
        WHERE u.timeonline >= u.last_timeonline + {$invs_type}");
    // nu putem realiza si log-uri in pacate daca facem ceva dintr-o singura interogare
    // in schimb functia va returna numarul de randuri actualizate
    return $db->affected_rows();
}

?>