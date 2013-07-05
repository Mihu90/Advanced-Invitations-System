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
  Ultima modificare a codului : 16.04.2012 18:26
 */

define("IN_MYBB", 1);

require_once "../../../global.php";
include "./gateway_interface.php";

class PaypalGateway implements interfaceGateway {
    
    /**
     * Metoda parseaza un text
     * @param type $text : Textul ce se va parsa
     * @param type $uid : Despre cine e vorba
     * @param type $mybb : Se trimite si variabila $mybb
     * @return type : Se returneaza textul parsat
     */
    private function parse_text($text, $uid, $mybb) {
        // se obtin detalii legate de utilizatorul al carui id e cel 'uid'
        $user = get_user($uid);
        // ce inlocuiri vom efectua?
        $replaces = array(
            '{GATEWAY}' => 'Paypal',
            '{USER_NAME}' => $user['username'],
            '{USER_EMAIL}' => $user['email'],
            '{USER_INVITATIONS}' => $user['invitations'],
            '{BOARD_NAME}' => $mybb->settings['bbname'],
            '{BOARD_URL}' => $mybb->settings['bburl']
        );
        // se fac inlocuirile
        foreach ($replaces as $key => $value)
            $text = str_replace($key, $value, $text);
        // se returneaza varianta finala a text-ului
        return $text;
    }
    
    /**
     * Metoda care verifica daca a aparut o plata Paypal
     * @param type $array
     * @param type $db
     * @param type $mybb
     * @param type $lang
     * @return type 
     */
    public function check($array, $db, $mybb, $lang, $plugins) {
        if (!$array['txn_id']) {
            header("Status: 404 Not Found");
            return;
        } else {
            header("Status: 200 OK");
        }
        
        $header = "";
        $emailtext = "";
        
        // citeste POST-ul de la Paypal si adauga comanda 
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exits = true;
        }
        
        foreach ($array as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }
        
        // se trimite cererea catre Paypal
        $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        $fp = fsockopen('www.paypal.com', 80, $errno, $errstr, 30);
        
        // este validata cererea de Paypal?
        if (!$fp) {
            return;
        } else {
            // daca cererea a fost validata
            fputs($fp, $header . $req);
            
            while (!feof($fp)) {
                $res = fgets($fp, 1024);
                
                // este totul OK?
                if (strcmp($res, "VERIFIED") == 0) {
                    // se incarca fisierul de limba
                    $lang->load('advinvsys');
                    
                    $status = $db->escape_string($array['payment_status']);
                    $receiver = $db->escape_string($array['receiver_email']);
                    
                    // cine a primit banii?
                    if ($receiver != $mybb->settings['advinvsys_setting_paypalemail'] 
                            || $status != "Completed") {
                        return;
                    }
                    
                    // se intorc unele informatii importante legate de plata
                    $txn_id = $db->escape_string($array['txn_id']);
                    
                    // in mod implicit o invitatie costa 1
                    $amount = 1.00;
                    
                    // se calculeaza numarul de invitatii pe care le va primi persoana implicata
                    if (floatval($mybb->settings['advinvsys_setting_paypalcost']) > 0)
                        $amount = $AISDevelop->truncNumber($array['mc_gross']) / floatval($mybb->settings['advinvsys_setting_paypalcost']);
                    
                    if (is_numeric($array['item_number'])) {
                        $data = intval($array['item_number']);
                        $type = 'uid';
                    } else {
                        $data = $db->escape_string($array['item_number']);
                        $type = 'email';
                    }
                    
                    // se face actualizarea numarului de invitatii ale utilizatorului
                    if ($type == 'uid') {
                        $db->write_query("UPDATE " . TABLE_PREFIX . "users SET invitations = invitations + {$amount} 
                            WHERE uid = '" . $data . "'");
                        
                        // se trimite si un mesaj privat?
                        if ($mybb->settings['advinvsys_setting_smssendpm'] == 1 && function_exists('advinvsys_send_pm')) {
                            $message = $mybb->settings['advinvsys_setting_smssendpmm'];
                            $message = $this->parse_text($message, $data, $mybb);
                            $pm = array(
                                "subject" => $lang->sprintf($lang->advinvsys_fortumo_pm_subject, ''),
                                "message" => $message,
                                "touid" => $uid,
                                "receivepms" => true
                            );
                            // se trimite mesajul privat
                            $AISDevelop->sendPM($pm, -1);
                        }
                        
                        // se introduce un log in sistem ?
                        $AISDevelop->addLog($lang->advinvsys_fortumo_log0, 
                            $lang->sprintf($lang->advinvsys_fortumo_log1, $amount, 'Paypal - TxnID : '.$txn_id), 
                            $uid);
                    } else {
                        // atunci type-ul e 'email'
                        // se genereaza o invitatie ce va fi adaugata si in baza de date
                        $hash = $AISDevelop->generateInvitation(-1, $data, true);
                        
                        // ce mesaj se va trimite?
                        $message = $mybb->settings['advinvsys_setting_smssendemail'];
                        
                        // mesajul este parsat
                        $replaces = array(
                            '{INVITATION_CODE}' => $hash,
                            '{GATEWAY}' => 'Paypal',
                            '{BOARD_NAME}' => $mybb->settings['bbname'],
                            '{BOARD_URL}' => $mybb->settings['bburl'],
                            '{REGISTRATION_URL}' =>  $mybb->settings['bburl'] . '/member.php?action=register&code=' . $hash
                        );
                        
                        // se fac inlocuirile
                        foreach ($replaces as $key => $value)
                            $message = str_replace($key, $value, $message);
                        
                        // se incearca trimiterea email-ului
                        my_mail($data, $lang->advinvsys_fortumo_mail, $message);
                        
                        // daca s-a trimis atunci se introduce si un log in baza de date
                        $AISDevelop->addLog($lang->advinvsys_fortumo_log0, 
                            $lang->sprintf($lang->advinvsys_fortumo_log2, $data, 'Paypal - TxnID : '.$txn_id), 
                            -1);
                    }
                } else
                if (strcmp($res, "INVALID") == 0) {
                    // ceva nu e bine
                    return;
                }
            }
            return;
        }
        fclose($fp);
    }
}

$paypal = new PaypalGateway();
$paypal->check($_POST, $db, $mybb, $lang, $plugins, $AISDevelop);
?>
