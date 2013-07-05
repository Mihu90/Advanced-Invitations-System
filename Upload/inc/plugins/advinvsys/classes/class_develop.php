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
  Ultima modificare a codului : 08.08.2012 10:59
 */

class AISDevelop
{
    const AUTO_SEARCH_GROUPID = -1;
    const DISPLAY_ORDER_END = -1;
    const DISPLAY_ORDER_BEGIN = 1;
    
    // lista cu metode care au fost dezvoltate si pot fi folosite
    protected $methods = array();
    // setari implicite
    protected $setting = array(
        'DAY_SECONDS' => 86400,
        'NR_OF_DECS' => 2,
        'INV_EXPR_DAYS' => 7,
        'TEMP_VERS' => '1600'
    );
    // lista de date pe care le verificam
    protected $listchk = array(
        'cid' => 'is_numeric',
        'uid' => 'is_numeric',
        'username' => 'is_string'
    );

    
    /**
     * Constructorul implicit al acestei clase
     **/
    function __construct($methods = array())
    {
        // se adauga metodele pe care dezvoltatorul le va putea utiliza
        $default = array(
            'alterColumn',
            'addTemplates', 'addSettings',
            'addLog', 'removeLog',
            'addInvitations', 'checkInvitations', 'checkInvitationSent', 'checkInvitationExpired',
            'generateInvitation',
            'parseBody',
            'sendPM',
            'truncNumber',
            'versionChange'
        );
        $this->registerMethod($default);
        
        // se adauga metodele secundare
        $this->registerMethod($methods);
    }
    
    /**
     * Toate functiile care se apeleaza trec prin aceasta metoda...
     **/
    public function __call($method, $args)
    {
        // verificam daca metoda se afla in lista noastra?!
        if (in_array($method, $this->methods)) 
            return call_user_method_array($method, $this, $args);
        else
            return false;
    }
    
    /**
     * Metoda seteaza o proprietate data pentru un membru
     **/
    public function __set($property, $value)
    {
        $this->setting[$property] = $value;
    }
    
    /**
     * Metoda inregistreaza noi functii care pot fi rulate de catre un
     * plugin...
     **/
    private function registerMethod($method)
    {
        if (is_array($method))
            $this->methods = array_merge($this->methods, $method);
        else
            $this->methods[] = $method;
    }
    
    /**
     * Metoda verifica ca o anumita valoare se aiba un anumit tip
     **/
    protected function checkType($type, $value)
    {
        // verificam daca tipul si valoarea sunt corecte
        if (!isset($this->listchk[$type]) ||
                !call_user_func($this->listchk[$type], $value))
            return false;
        else
            return true;
    }
    
    /**
     * Metoda primeste ca si argument un numar si returneaza numarul
     * cu doar X zecimale.
     **/
    protected function truncNumber($number)
    {
        return number_format(floatval($number), $this->setting['NR_OF_DECS']);
    }
    
    // DE AICI MAI JOS SUNT DEFINITE FUNCTIILE IMPLICITE
    
    /**
     * Metoda altereaza sau insereaza daca nu exista o coloana intr-o
     * tabela a bazei de date
     **/
    protected function alterColumn($table, $column, $data, $insert = false)
    {
        global $db;
        
        try {
            if($db->field_exists($column, $table)) {
                // se face o trunchiere
                $db->write_query("
                    ALTER TABLE `" . TABLE_PREFIX . "{$table}`
                    CHANGE `{$column}` {$column}
                    {$data};
                ");
            } else if($insert) {
                // daca nu exista se adauga in baza de date
                $db->write_query("
                    ALTER TABLE `" . TABLE_PREFIX . "{$table}`
                    ADD `{$column}` {$data};
                ");
            }
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
    
    /**
     * Metoda insereaza un sablon in baza de date...
     **/
    protected function addTemplates($templates, $ids = false)
    {
	global $db;
        // daca se insereaza id-uri pentru actualizari
        if ($ids) {
            if (!function_exists('advinvsys_get_templates'))
                return false;
            
            $temps = advinvsys_get_templates($templates);
            
            foreach ($temps as $template) {
                $insert = array(
                    "title" => $template['title'],
                    "template" => $db->escape_string($template['template']),
                    "sid" => "-2",
                    "version" => $this->setting['TEMP_VERS'],
                    "status" => "",
                    "dateline" => TIME_NOW
                );
                $db->insert_query("templates", $insert);
            }
            
            return true;
        } else if (is_array($templates)) {
            foreach ($templates as $template) {
                $insert = array(
                    "title" => 'advinvsys_' . $template['title'],
                    "template" => $db->escape_string($template['template']),
                    "sid" => "-2",
                    "version" => $this->setting['TEMP_VERS'],
                    "status" => "",
                    "dateline" => TIME_NOW
                );
                $db->insert_query("templates", $insert);
            }
            
            return true;
        }
        
        // $templates trebuie neaparat sa fie vector...
        return false;
    }
    
    /**
     * Metoda permite adaugarea unor setari in sistem...
     */
    protected function addSettings($module, $settings, $gid = -1, $disporder = 1)
    {
	global $db, $mybb;

        if (!is_array($settings))
            return false;
    
        if ($disporder >= 0)
            $position = $disporder;
        else {
            $select = ($gid == -1) ? ', gid' : '';
            // se va cauta pozitia pe care facem afisarea
            $query = $db->write_query("
                SELECT MAX(disporder) AS disp {$select}
                FROM " . TABLE_PREFIX . "settings
                WHERE name LIKE 'advinvsys_setting_%'
            ");
            if ($row = $db->fetch_array($query)) {
                $position = (int)$row['disp'] + 1;
                $gid = ($gid == -1) ? (int)$row['gid'] : $gid;
            } else {
                $position = 0;
            }
        }
        
        // se creaza un nume preferential pentru setare
        if ($module == "")
            $path = "";
        else
            $path = $module . "_";
        
        // se introduce fiecare setare in baza de date
        foreach ($settings as $setting)
        {
            // daca setarea exista deja in baza de date atunci ea este updatata
            if (array_key_exists('advinvsys_' . $path . $setting['name'], $mybb->settings)) {
                $update_setting = array(
                    "title" => $db->escape_string($setting['title']),
                    "description" => $db->escape_string($setting['description']),
                    "optionscode" => $db->escape_string($setting['optionscode']),
                    "value" => $db->escape_string($setting['value']),
                    "disporder" => $position++
                );
            
            // se realizeaza procesul de actualizare
            $db->update_query("settings", $update_setting,
                "name = 'advinvsys_" . $path . $db->escape_string($setting['name']) . "'");
            } else {
                if ($gid == -1) {
                    // cautam automat
                    $query = $db->simple_select('settinggroups', 'gid',
                        "name = 'advinvsys_group'", array('limit' => 1));
                    if ($row = $db->fetch_array($query))
                        $gid = (int)$row['gid'];
                }
            
                // daca nu exista in baza de date atunci se insereaza setarea
                $insert_setting = array(
                    "name" => 'advinvsys_' . $path . $db->escape_string($setting['name']),
                    "title" => $db->escape_string($setting['title']),
                    "description" => $db->escape_string($setting['description']),
                    "optionscode" => $db->escape_string($setting['optionscode']),
                    "value" => $db->escape_string($setting['value']),
                    "disporder" => $position++,
                    "gid" => (int)$gid,
                );
            
                // se realizeaza procesul de inserare a unei setari in baza de date
                $db->insert_query("settings", $insert_setting);
            }
        }
        
        // se reconstruiesc toate setarile
        rebuild_settings();
    }
    
    /**
     * Functia de mai jos introduce un log in sistem
     **/
    protected function addLog($type, $data, $user = 0, $now = false)
    {
        global $db;
        
        // se creaza randul care va fi introdus in baza de date
        $log = array(
            "uid" => intval($user),
            "type" => $db->escape_string($type),
            "data" => $db->escape_string($data),
            "date" => TIME_NOW,
        );

        // este adaugat acum log-ul in sistem?
        if ($now)
            return $db->insert_query('advinvsys_logs', $log);
        else {
            // inserarea nu se face la apelul functiei
            // in acest fel creste viteza de executie scriptului
            $db->shutdown_query("
                INSERT INTO " . TABLE_PREFIX . "advinvsys_logs (`uid`,`type`,`data`,`date`)
                VALUES('" . @implode("','", array_values($log)) . "')
            ");
        }
    }

    /**
     * Functia sterge toate jurnalele din sistem ce au id-urile specificate.
     **/
    protected function removeLog($ids = array(), $now = false)
    {
        global $db;
        
        // daca id-urile nu exista sau nu formeaza un vector
        if (empty($ids) || !is_array($ids))
            return false;

        // cand are loc stergerea propriu-zisa?!
        if ($now)
            $db->write_query("
                DELETE FROM " . TABLE_PREFIX . "advinvsys_logs
                WHERE lid IN (" . join(',', $ids) . ")
            ");
        else
            $db->shutdown_query("
                DELETE FROM " . TABLE_PREFIX . "advinvsys_logs
                WHERE lid IN (" . join(',', $ids) . ")
            ");

        return true;
    }
    
    // Functia adauga sau ia un numar de invitatii pentru un utilizator primit
    // ca si parametru. De asemenea ea adauga si un log in sistem (daca e cazul).
    protected function addInvitations($type, $value, $number, $reset = false, $log = true, $now = false)
    {
        global $db, $lang, $plugins;
        
        // se fac cateva teste
        if (!is_numeric($number) || floatval($number) == 0)
            return false;

        // verificari amanuntite
        if (!$this->checkType($type, $value))
            return false;

        $plugins->run_hooks('advinvsys_invitations_do_add_start');

        // se trunchiaza numarul de invitatii
        $nr = $this->truncNumber($number);
        // se aduna sau se scade numarul de invitatii
        $sign = (floatval($number) > 0) ? '+' : '-';

        // se reseteaza numarul de invitatii sau se adauga / scade acest numar?!
        if ($reset)
            $action = $nr;
        else {
            // care este actiunea?
            $action = "invitations {$sign} " . abs($nr);
        }

        // verificam care este numarul maxim de invitatii permise pe grup
        $max_invites = -1; // numar infinit
        
        $query = $db->write_query("
            SELECT g.max_invitations AS max
            FROM " . TABLE_PREFIX . "usergroups g
            LEFT JOIN " . TABLE_PREFIX . "users u ON (u.usergroup = g.gid) 
            WHERE {$type} = '" . $value . "' LIMIT 1
        ");

        if ($row === $db->fetch_array($query))
            $max_invites = $this->truncNumber($row['max']);

        $set = '';
        
        // in functie de baza de date se fac prelucrari
        switch($db->type)
        {
            case "sqlite":
                if ($max_invites >= 0)
                    $set = "MIN(MAX(0, $action), $max_invites)";
                else
                    $set = "MIN(0, $action)";
                break;
            default:
                if ($max_invites >= 0)
                    $set = "LEAST(GREATEST(0, $action), $max_invites)";
                else
                    $set = "GREATEST(0, $action)";
        }

        // se introduce acum in sistem?
        if ($now) {
            // ne asiguram ca numarul de invitatii este intre marginile stabilite
            $db->write_query("
                UPDATE " . TABLE_PREFIX . "users
                SET invitations = {$set}
                WHERE {$type} = '" . $value . "'"
            );
        } else {
            // se va executa mai tarziu
            $db->shutdown_query(
                "UPDATE " . TABLE_PREFIX . "users
                SET invitations = {$set}
                WHERE {$type} = '" . $value . "'"
            );
        }

        // se scrie un log in sistem?
        if ($log) {
            // se incarca fisierul de limba
            $lang->load("advinvsys");

            // i s-au luat sau a primit invitatii
            if ($sign == '+')
                $data = $lang->sprintf($lang->advinvsys_income_log0,
                    "{$type} = {$value}", 
                    $lang->advinvsys_income_log0_r,
                    abs($nr)
                );
            else
                $data = $lang->sprintf($lang->advinvsys_income_log0,
                    "{$type} = {$value}", 
                    $lang->advinvsys_income_log0_t,
                    abs($nr)
                );

            // se adauga log-ul
            $this->addLog($lang->advinvsys_income_logt, $data, 0);
        }

        $plugins->run_hooks('advinvsys_invitations_do_add_end');

        // se returneaza raspunsul "true"
        return true;
    }

    // Functia verifica daca o anumita persoana are un anumit numar de invitatii
    // Daca are functia va returna numarul de invitatii pe care le are, altfel
    // va returna "false"
    protected function checkInvitations($type, $value, $amount)
    {
        global $db, $plugins;
     
        // daca nu e valoare numerica
        if (!is_numeric($amount))
            return false;
   
        // verificari amanuntite
        if (!$this->checkType($type, $value))
            return false;  

        $plugins->run_hooks('advinvsys_invitations_do_check_start');

        // daca se cere determinarea numarului de invitatii ale utilizatorului curent
        // nu vom mai face o interogare a bazei de date ci pur si simplu testam
        // cu ceea ce avem deja...
        if ($mybb->user[$type] == $value)
            return ($mybb->user['invitations'] >= $this->truncNumber($amount))
                ? $mybb->user['invitations']
                : false;   
            
        // se realizeaza o noua interogare
        $query = $db->simple_select('users', 'invitations', $type . ' = \'' . $value . '\' 
            AND invitations >= ' . $this->truncNumber($amount), array('limit' => 1));

        $plugins->run_hooks('advinvsys_invitations_do_check_end'); 

        if ($row = $db->fetch_array($query))
            return $this->truncNumber($row['invitations']);
         else
            return false;
    }
    
    // Functia verifica daca nu cumva s-a trimis deja o invitatie catre un anumit
    // email. In acest fel se evita trimiterea a doua sau mai multe invitatii pe
    // aceeasi adresa de email!
    protected function checkInvitationSent($type, $value)
    {
        global $db, $mybb;
        
        // verificari amanuntite
        if (!$this->checkType($type, $value))
            return true;

        // dupa cate zile o invitatie este considerata expirata?
        $expdays = (is_numeric($mybb->settings['advinvsys_setting_dateexpr']))
            ? abs($mybb->settings['advinvsys_setting_dateexpr'])
            : $this->setting['INV_EXPR_DAYS'];
        $expr = $expdays * intval($this->setting['DAY_SECONDS']);

        $query = $db->simple_select('advinvsys_codes', 'cid', 'date > ' .
            intval(TIME_NOW - $expr) . ' AND ' . $type . ' = \'' . $value . '\'', 
            array('limit' => 1));

        return ($db->num_rows($query) == 1) ? true : false;
    }
    
    // Functia verifica daca exista invitatii expirate si le sterge din baza, 
    // acordand credite (invitatii) celor care le-au creat
    protected function checkInvitationExpired()
    {
        global $db, $mybb, $plugins;
        
        // dupa cate zile o invitatie este considerata expirata?
        $expdays = (is_numeric($mybb->settings['advinvsys_setting_dateexpr']))
            ? abs($mybb->settings['advinvsys_setting_dateexpr'])
            : $this->setting['INV_EXPR_DAYS'];
        $expr = $expdays * intval($this->setting['DAY_SECONDS']);

        $limit = 50; // se va putea schimba printr-o modificare

        $this->plugins->run_hooks('advinvsys_invitations_do_expire_start');

        $query = $db->simple_select('advinvsys_codes', 'cid,did', 'date <= ' .
            intval(TIME_NOW - $expr), array('limit' => $limit));

        $cids = array();
        while ($row = $db->fetch_array($query)) {
            array_push($cids, $row['cid']);

            // o primeste doar daca e utilizator si setarea e coresp.
            if ($mybb->settings['advinvsys_setting_giveexpr'] == 1 &&
                    intval($row['did']) > 0) {

                $plugins->run_hooks('advinvsys_invitations_do_expire_middle');

                // utilizatorul care a trimis invitatia o va primi inapoi
                $this->addInvitations('uid', $row['did'], 1, false, false);
            }
        }

        $plugins->run_hooks('advinvsys_invitations_do_expire_end');

        // verificam daca exista invitatii de sters
        if (count($cids) > 0) {
            // se sterg toate intrarile
            $db->delete_query('advinvsys_codes',
                'cid IN (' . implode(',', $cids) . ')');
        }
    }
    
    /**
     * Functia urmatoare are rolul de a genera o invitatie pentru cineva...
     **/
    protected function generateInvitation($uid = 0, $email = '', $save_to_db = false, $crypt = '')
    {
        global $db, $plugins;
        
        // sunt incluse si cateva caractere speciale
        $length = mt_rand(1, 10);

        // caractere speciale
        $chars = '!@#$%^&*()';
        $size = strlen($chars);

        $string = '';
        // se creaza string-ul aditional
        for ($i = 0; $i < $length; ++$i)
            $string .= $chars[rand(0, $size - 1)];

        // cheia finala
        if ($crypt == '' || !function_exists($crypt))
            $key = md5(TIME_NOW . mt_rand(10000, 99999) . $string);
        else
            $key = call_user_func($crypt, TIME_NOW . mt_rand(10000, 99999) . $string);

        $plugins->run_hooks('advinvsys_invitations_do_generate');

        // realizam si o salvare a invitatiei in baza de date?
        if ($save_to_db) {
            // ce se va introduce in baza de date?
            $insert_array = array(
                'did' => intval($uid),
                'code' => $key,
                'date' => TIME_NOW
            );
            
            // adresa de email e buna?
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                $insert_array['email'] = '';
            else
                $insert_array['email'] = $email;

            // se introduce invitatia in baza de date
            $id = $db->insert_query('advinvsys_codes', $insert_array);
        }

        // se returneaza rezultatul cryptat
        return $key;
    }
    
    /**
     * Functia parseaza un mesaj ce urmeaza a fi trimis prin email!
     **/
    function parseBody($text, $key, $message = '')
    {
        global $mybb, $plugins;

        // ce inlocuiri vom efectua?
        $replaces = array(
            '{INVITER_USERNAME}' => $mybb->user['username'],
            '{INVITATION_CODE}' => $key,
            '{REGISTRATION_URL}' => $mybb->settings['bburl'] . '/member.php?action=register&code=' . $key,
            '{BOARD_URL}' => $mybb->settings['bburl'],
            '{BOARD_NAME}' => $mybb->settings['bbname'],
            '{USER_MESSAGE}' => $message
        );

        $replaces = $plugins->run_hooks('advinvsys_parsemail_do_replace', $replaces);

        // se fac inlocuirile
        foreach ($replaces as $key => $value)
            $text = str_replace($key, $value, $text);

        // se returneaza varianta finala a text-ului
        return $text;
    }
    
    /**
     * Metoda permite verificarea existentei unui plugin in cadrul unui forum 
     **/
    function isInstalled($plugin)
    {
        global $db;
        
        $funcname = $plugin . '_settings';
        if ($db->table_exists($funcname)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Functia permite realizarea schimbarii unei versiuni a modificarii...
     **/
    protected function versionChange($ais, $php)
    {
        try {
            $file = fopen(MYBB_ROOT . 'inc/plugins/advinvsys/system_version.php', 'w');
            // ce se va scrie in fisier?
            $data = "<?php\nif (!defined('IN_MYBB'))\n\tdie('This file cannot be accessed directly.');\ndefine('AIS_VERSION', '{$ais}');\ndefine('AIS_MIN_PHP', '{$php}');\n?>";
            // se scrie efectiv in fisier
            fwrite($file, $data);
            // se inchide fisierul
            fclose($file);
            // daca s-a putut scrie atunci se intoarce "true"
            return true;
        } catch (Exception $e) {
            // la aparitia unei erori se intoarce "false"
            return false;
        }
    }
    
    /**
     * Prin intermediul acestei functii se poate trimite un mesaj privat
     * unui utilizator de pe forum.
     **/
    private function sendPM($pm, $fromid = 0)
    {
        global $db, $lang, $mybb;
        
        if ($mybb->settings['enablepms'] == 0)
            return false;

        if (!is_array($pm))
            return false;

        if (!$pm['subject'] || !$pm['message'] || !$pm['touid'] || !$pm['receivepms'])
            return false;

        // se incarca fisierul de limba cu mesaje
        $lang->load('messages');

        // se include datahandler-ul necesar trimiterii unui mesaj privat
        require_once MYBB_ROOT . "inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        $subject = $pm['subject'];
        $message = $pm['message'];
        $toid = $pm['touid'];

        if (is_array($toid))
            $recipients_to = $toid;
        else
            $recipients_to = array($toid);

        $recipients_bcc = array();

        if (intval($fromid) == 0)
            $fromid = intval($mybb->user['uid']);
        elseif (intval($fromid) < 0)
            $fromid = 0;

        $pm = array(
            "subject" => $subject,
            "message" => $message,
            "icon" => -1,
            "fromid" => $fromid,
            "toid" => $recipients_to,
            "bccid" => $recipients_bcc,
            "do" => '',
            "pmid" => ''
        );

        $pm['options'] = array(
            "signature" => 0,
            "disablesmilies" => 0,
            "savecopy" => 0,
            "readreceipt" => 0
        );

        $pm['saveasdraft'] = 0;
        $pmhandler->admin_override = 1;
        $pmhandler->set_data($pm);

        if ($pmhandler->validate_pm()) {
            $pmhandler->insert_pm();
        } else {
            return false;
        }
        return true;
    }
}
?>
