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
  Ultima modificare a codului : 16.04.2012 18:22
 */

define("IN_MYBB", 1);

require_once "../../../global.php";
include "./gateway_interface.php";

class SmsGateway implements interfaceGateway {

    /**
     * Metoda este folosita pentru a ne asigura ca codul secret trasnmis e corect
     * @param type $params_array : Parametrii ce au sosit prin POST
     * @param type $secret : Cod secret de verificat
     * @return type : True daca totul e bine sau false altfel
     */
    private function check_signature($params_array, $secret) {
        // se face o sortare dupa chei
        ksort($params_array);
        // se formeaza un nou string
        $str = '';
        foreach ($params_array as $k => $v) {
            if ($k != 'sig') {
                $str .= "$k=$v";
            }
        }
        $str .= $secret;
        $signature = md5($str);
        // se returneaza rezultatul
        return ($params_array['sig'] == $signature);
    }

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
            '{GATEWAY}' => 'SMS-Fortumo',
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
     * Metoda este folosita la validarea unei adrese de e-mail
     * @param type $email : Adresa de email
     * @return boolean : True daca e valida sau false altfel
     */
    private function validate_email($email) {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) 
                ? true : false;
    }

    /**
     *
     * @param type $array
     * @param type $db
     * @param type $mybb
     * @param type $lang
     * @return type 
     */
    public function check($array, $db, $mybb, $lang, $plugins, $AISDevelop) {
        // se incarca fisierul de limba
        $lang->load('advinvsys');
        
        // este un element din vector setat pe false?
        foreach ($array as $key => $value) {
            if ($value === false)
                return;
        }
        
        // verificam daca ip-ul care a executat cererea este autentic
        $ips = array('81.20.151.38', '81.20.148.122', '79.125.125.1', '209.20.83.207');
        if (!isset($array['ip']) || !in_array($array['ip'], $ips)) {
            return;
        }
        
        // se fac mai departe verificari asupra semnaturii digitale
        if (!isset($array['secret']) || !$this->check_signature($_GET, $array['secret'])) {
            return;
        }
        
        // se fac teste asupra existentei pluginului si a activarii acestuia
        if ($mybb->settings['advinvsys_setting_enable'] != 1 || $mybb->settings['advinvsys_setting_smsenable'] != 1 ||
                (isset($array['service']) && $array['service'] != $mybb->settings['advinvsys_setting_smsid'])) {
            echo $lang->advinvsys_fortumo_noservice;
            return;
        }
        
        // verificam daca numele de utilizator exista in baza de date
        if (isset($array['username']) && $db->num_rows($user = $db->simple_select('users', 
                'uid', 'username = \'' . $array['username'] . '\'')) > 0) {
            // daca totul e in regula se adauga invitatiile cumparate
            $amount = ($AISDevelop->truncNumber($mybb->settings['advinvsys_setting_smsinvs']) > 0)
                ? $AISDevelop->truncNumber($mybb->settings['advinvsys_setting_smsinvs']) : 1;
            $uid = $db->fetch_field($user, 'uid');
            
            // se face actualizarea numarului de invitatii ale utilizatorului
            $db->write_query("
                UPDATE " . TABLE_PREFIX . "users
                SET invitations = invitations + {$amount} 
                WHERE uid = '" . $uid . "'
            ");
            
            // se trimite si un mesaj privat?
            if ($mybb->settings['advinvsys_setting_smssendpm'] == 1 && function_exists('advinvsys_send_pm')) {
                $message = $mybb->settings['advinvsys_setting_smssendpmm'];
                $message = $this->parse_text($message, $uid, $mybb);
                $pm = array(
                    "subject" => $lang->sprintf($lang->advinvsys_fortumo_pm_subject, $array['username']),
                    "message" => $message,
                    "touid" => $uid,
                    "receivepms" => true
                );
                // se trimite mesajul privat
                $AISDevelop->sendPM($pm, -1);
            }
            
            // se introduce un log in sistem ?
            $AISDevelop->addLog($lang->advinvsys_fortumo_log0, 
                $lang->sprintf($lang->advinvsys_fortumo_log1, $amount, 'SMS-Fortumo'), 
                $uid);
            
            // totul s-a realizat cu succes
            echo $lang->advinvsys_fortumo_payment_succes;
            return;
        } else {
            // altfel se poate ca un utilizator ca ceara o invitatie pe email
            if (isset($array['username'])) {
                // se fac cateva inlocuiri
                $array['username'] = str_replace('[at]', '@', $array['username']);
                $array['username'] = str_replace('[dot]', '.', $array['username']);
                
                // verificam daca mesajul sau este o adresa de e-mail valida
                if (!$this->validate_email($array['username'])) {
                    echo $lang->advinvsys_fortumo_novalid;
                    return;
                }
                
                // totul e bun si frumos
                // intotdeauna va primi o invitatie
                // se genereaza o invitatie ce va fi adaugata si in baza de date
                $hash = $AISDevelop->generateInvitation(-1, $array['username'], true);
                
                // urmeaza sa ii trimitem un email cu codul invitatiei
                $message = $mybb->settings['advinvsys_setting_smssendemail'];
                
                // mesajul este parsat
                $replaces = array(
                    '{INVITATION_CODE}' => $hash,
                    '{GATEWAY}' => 'SMS-Fortumo',
                    '{BOARD_NAME}' => $mybb->settings['bbname'],
                    '{BOARD_URL}' => $mybb->settings['bburl'],
                    '{REGISTRATION_URL}' =>  $mybb->settings['bburl'] . '/member.php?action=register&code=' . $hash
                );
                
                // se fac inlocuirile
                foreach ($replaces as $key => $value)
                    $message = str_replace($key, $value, $message);
                
                // se incearca trimiterea email-ului
                my_mail($array['username'], $lang->advinvsys_fortumo_mail, $message);
                
                // daca s-a trimis atunci se introduce si un log in baza de date
                $AISDevelop->addLog($lang->advinvsys_fortumo_log0, 
                    $lang->sprintf($lang->advinvsys_fortumo_log2, $array['username'], 'SMS-Fortumo'), 
                    -1);
                
                // totul s-a realizat cu succes
                echo $lang->sprintf($lang->advinvsys_fortumo_payment_succes1, $hash);
                return;
            } else {
                echo $lang->advinvsys_fortumo_wrongmessage;
                return;
            }
        }
    }

}

$sender = (isset($_GET['sender'])) ? $db->escape_string(htmlspecialchars($_GET['sender'])) : false;
$price = (isset($_GET['price'])) ? $db->escape_string(htmlspecialchars($_GET['price'])) : false;
$username = (isset($_GET['message'])) ? $db->escape_string(htmlspecialchars($_GET['message'])) : false;
$service_id = (isset($_GET['service_id'])) ? $db->escape_string(htmlspecialchars($_GET['service_id'])) : false;

$array = array(
    'ip' => $_SERVER['REMOTE_ADDR'],
    'price' => $price,
    'username' => $username,
    'secret' => $mybb->settings['advinvsys_setting_smssecret'],
    'sender' => $sender,
    'service' => $service_id
);

$sms = new SmsGateway();
$sms->check($array, $db, $mybb, $lang, $plugins, $AISDevelop);
?>
