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
  Ultima modificare a codului : 08.08.2012 00:24
 */

// Poate fi acesat direct fisierul?
if (!defined("IN_MYBB")) {
    die("This file cannot be accessed directly.");
}

// Se incarca fisierul de limba
$lang->load("advinvsys");

$sub_tabs = array(
    "main" => array(
        "title" => $lang->advinvsys_main,
        "link" => "index.php?module=config-advinvsys",
        "description" => $lang->advinvsys_main_description
    ),
    "modules" => array(
        "title" => $lang->advinvsys_modules,
        "link" => "index.php?module=config-advinvsys&amp;section=modules",
        "description" => $lang->advinvsys_modules_description
    ),
    "logs" => array(
        "title" => $lang->advinvsys_logs,
        "link" => "index.php?module=config-advinvsys&amp;section=logs",
        "description" => $lang->advinvsys_logs_description
    ),
    "stats" => array(
        "title" => $lang->advinvsys_stats,
        "link" => "index.php?module=config-advinvsys&amp;section=stats",
        "description" => $lang->advinvsys_stats_description
    ),
    "about" => array(
        "title" => $lang->advinvsys_about,
        "link" => "index.php?module=config-advinvsys&amp;section=about",
        "description" => $lang->advinvsys_about_description
    )
);

// se ofera utilizatorului posibilitatea de a adauga si alte pagini in meniu
$sub_tabs_array = array();

$sub_tabs_array = $plugins->run_hooks("advinvsys_admin_menu", $sub_tabs_array);

// daca exista elemente adaugate atunci se face o actualizare a meniului
if (count($sub_tabs_array) > 0) {
    // utilizatorul poate suprascrie unele pagini ?
    // implicit DA
    $sub_tabs = array_merge($sub_tabs, $sub_tabs_array);
}

// PAGINILE DE ADMINISTRARE
if (strpos($mybb->input['section'], "plugins-") !== false) {
    // se va rula o pagina a unui plugin al acestei modificari

    // vedem mai intai ce plugin vom rula
    $plugin = strstr($mybb->input['section'], '-');
    $plugin = str_replace('-', '', $plugin);
    
    // se poate deja sa fie inclus fisierul modificarii
    if (!in_array(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$plugin}.php", get_included_files())) {
        // exista modificarea pe server ?
        if (file_exists(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$plugin}.php")) {
            
            // se include fisierul
            require_once MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$plugin}.php";
            
            // se incearca apelul functiei
            if (function_exists("ais_{$plugin}_admin")) {
                call_user_func("ais_{$plugin}_admin");
            } else {
                // altfel se va sari la pagina principala
                admin_redirect('index.php?module=config-advinvsys');
            }
        }
    } else {
        // se incearca apelul functiei
        if (function_exists("ais_{$plugin}_admin")) {
            call_user_func("ais_{$plugin}_admin");
        } else {
            // altfel se va sari la pagina principala
            admin_redirect('index.php?module=config-advinvsys');
        }
    }
} else if (strcmp($mybb->input['section'], "logs") == 0) {
    if (!$mybb->input['action']) {
        
        $page->add_breadcrumb_item($lang->advinvsys_modname);
        $page->add_breadcrumb_item($lang->advinvsys_logs);
        
        // se prelucreaza datele
        if ($mybb->request_method == 'post') {
            verify_post_check($mybb->input['my_post_key']);
            
            // conditiile de lucru
            $conditions = advinvsys_build_condition_array();
            // ce se intampla daca nu e definita nicio conditie?
            if (count($conditions) == 0) {
                flash_message($lang->advinvsys_logs_noconditions, 'error');
                // se face redirect
                admin_redirect("index.php?module=config-advinvsys&amp;section=logs");
            }
            
            // vector cu conditiile prelucrate
            $sql = array();
            // convertire
            $sql_tests = array(
                'eq' => " = ",
                'neq' => " <> ",
                'empty' => " = ''",
                'notempty' => " <> ''",
                'null' => " IS NULL",
                'notnull' => " IS NOT NULL",
                'gt' => " > ",
                'lt' => " < ",
                'lte' => " <= ",
                'gte' => " >= ",
                'in' => " IN",
                'nin' => " NOT IN",
                'like' => " LIKE ",
                'nlike' => " NOT LIKE "
            );
            
            // pentru fiecare conditie din lista trimisa
            foreach ($conditions as $condition) {
                // verificam daca testul e "in" sau "nin"
                if ($condition['test'] == "in" || $condition['test'] == "nin") {
                    $values = array();
                    if (preg_match_all("/[^,\"']+|\"([^\"]*)\"|'([^']*)'/si", $condition['value'], $matches)) {
                        $condition['value'] = array();
                        foreach ($matches[0] as $value) {
                            $condition['value'][] = str_replace(array("\"", "'"), "", $value);
                        }
                    }
                }
                // alte campuri
                $field = $condition['field'];
                $sql_test = isset($sql_tests[$condition['test']]) ? $sql_tests[$condition['test']] : " = ";
                $field_type = isset($fields[$condition['field']]) ? $fields[$condition['field']]['type'] : "string";

                $values = $condition['value'];
                if (!is_array($values)) {
                    $values = array($values);
                }
                // se prelucreaza valorile introduse in functie de tipul campului
                $clean_values = array();
                switch ($field_type) {
                    case 'date':
                        // daca e de acest tip se poate sa fie numeric sau sa fie precizata o data relativa
                        foreach ($values as $value) {
                            if (is_numeric($condition['value'])) {
                                $clean_values[] = intval($condition['value']);
                            } else {
                                $clean_values[] = strtotime($condition['value']);
                            }
                        }
                        break;
                    case 'int':
                        foreach ($values as $value) {
                            $clean_values[] = intval($value);
                        }
                        break;
                    case 'string':
                        foreach ($values as $value) {
                            // linia urmatoare are rolul de a securiza eventualele atacuri de tip SQL Injection
                            $clean_values[] = "'" . $db->escape_string($value) . "'";
                        }
                        break;
                }
                
                // teste aditionale
                if ($condition['test'] == 'in' || $condition['test'] == 'nin') {
                    $clean_values = "(" . implode(',', $clean_values) . ")";
                } elseif ($condition['test'] == 'empty' || $condition['test'] == 'notempty' || $condition['test'] == 'null' || $condition['test'] == 'notnull') {
                    $clean_values = "";
                } else {
                    $clean_values = str_replace('*', '%', $clean_values[0]);
                }
                
                // testele SQL
                $sql[] = $field . $sql_test . $clean_values;
            }
            
            // se construieste interogarea si se ruleaza
            $where = implode(" AND ", $sql);
            $db->delete_query("advinvsys_logs", $where);
            $rows = intval($db->affected_rows());
            if ($rows == 1)
                flash_message($lang->advinvsys_logs_prune_one, 'success');
            else
                flash_message($lang->sprintf($lang->advinvsys_logs_prune_more, 
                        $rows), 'success');
            
            // se face un redirect
            admin_redirect("index.php?module=config-advinvsys&amp;section=logs");
        }
        $page->extra_header .= '
            <style type="text/css">
            a.advinvsys_add_filter, a.advinvsys_delete_filter, a.advinvsys_get_help {
                display: block; height: 16px; width: 16px; text-indent: -999px; text-decoration: none; overflow: hidden; padding: 0px; margin: 0px;
            }
            a.advinvsys_add_filter {
                background: transparent url(../images/advinvsys/add.png);
            }
            a.advinvsys_delete_filter {
                background: transparent url(../images/advinvsys/delete.png);
            }
            a.advinvsys_get_help {
                background: transparent url(../images/advinvsys/help.png);
                cursor: help;
                float: right;
            }
            .advinvsys_field, .advinvsys_test, .advinvsys_value {
                width: 100%;
            }
            </style>
            <script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
            <script type="text/javascript">
                var advinvsys_deleteFilter = function(e) {
                    var deleteRow = this.up(1);
                    Effect.Fade(deleteRow, {\'duration\': 1, \'afterFinish\': function(){ deleteRow.remove(); }});
                    Event.stop(e);
                };
                Event.observe(document, "dom:loaded", function() {
                    // evenimentul pentru adaugarea unui filtru la stergerea log-urilor
                    Event.observe(\'advinvsys_add_filter\', \'click\', function(e) {
                        var addRow = this.up(1);
                        var cloneRow = addRow.cloneNode(true);
                        cloneRow.removeClassName(\'first\');
                        cloneRow.hide();
                        cloneRow.select(".first").invoke(\'removeClassName\', \'first\');
                        var fieldValue = addRow.select(\'.advinvsys_field\')[0].value;
                        var testValue = addRow.select(\'.advinvsys_test\')[0].value;
                        cloneRow.select(\'.advinvsys_field\')[0].value = fieldValue;
                        cloneRow.select(\'.advinvsys_test\')[0].value = testValue;
                        cloneRow.select(\'.advinvsys_add_filter\').invoke(\'replace\',\'<a href="#" class="advinvsys_delete_filter">&nbsp;</a>\');
                        cloneRow.select(\'.advinvsys_delete_filter\').invoke(\'observe\', \'click\', advinvsys_deleteFilter);
                        addRow.select(\'.advinvsys_field\').each(function(f){ f.value = \'\'; });
                        addRow.select(\'.advinvsys_test\').each(function(f){ f.value = \'\'; });
                        addRow.select(\'.advinvsys_value\').each(function(f){ f.value = \'\'; });
                        addRow.insert({\'after\': cloneRow});
                        Effect.Appear(cloneRow, {\'duration\': 1});
                        Event.stop(e);
	                });
                    // evenimentul pentru stergerea unui rand din filtru
                    $$(\'.advinvsys_delete_filter\').invoke(\'observe\', \'click\', advinvsys_deleteFilter);
                    // evenimentul pentru a apare casuta de ajutor
                    $$(\'.advinvsys_get_help\').invoke(\'observe\', \'click\', function(e) {
                        if($(this.rel)) {
                            Effect.toggle(this.rel, \'Blind\');
                        }
                        Event.stop(e);
                    });
                    $$(\'.advinvsys_close_help\').invoke(\'observe\', \'click\', function(e) {
                        Effect.toggle(this.up(3), \'Blind\');
                        Event.stop(e);
                    });
                    // in mod implicit casuta de ajutor nu este afisata pe ecran
                    $$(\'.advinvsys_prune_help\').invoke(\'hide\');
                });	   
            </script>';
        
        $page->output_header($lang->advinvsys_logs);
        
        // se afiseaza meniul orizontal
        $page->output_nav_tabs($sub_tabs, 'logs');
        
        // se realizeaza paginarea in vederea afisarii tabelului
        $per_page = 10; // in mod implicit
        if ($mybb->input['page'] && intval($mybb->input['page']) > 1) {
            $mybb->input['page'] = intval($mybb->input['page']);
            $start = ($mybb->input['page'] * $per_page) - $per_page;
        } else {
            $mybb->input['page'] = 1;
            $start = 0;
        }
        
        // acum paginarea este in regula, se trece la obtinerea datelor din tabel
        $query = $db->simple_select("advinvsys_logs", "COUNT(lid) as logs");
        // variabila ce retine numarul de randuri obtinute din interogare
        $total_rows = $db->fetch_field($query, "logs");
        
        // se construieste tabelul se urmeaza sa fie afisat
        $table = new Table;
        $table->construct_header($lang->advinvsys_logs_user, array('width' => '15%'));
        $table->construct_header($lang->advinvsys_logs_type, array('width' => '10%'));
        $table->construct_header($lang->advinvsys_logs_info, array('width' => '50%'));
        $table->construct_header($lang->advinvsys_logs_date, array('width' => '15%', 'class' => 'align_center'));
        $table->construct_header($lang->advinvsys_logs_acts, array('width' => '10%', 'class' => 'align_center'));
        // se creaza interogarea
        $query = $db->write_query("
                SELECT u.*, l.*
                FROM " . TABLE_PREFIX . "advinvsys_logs l
                LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = l.uid)
                ORDER BY l.date DESC LIMIT {$start}, {$per_page}
            ");
        // se creaza tabelul rand cu rand
        while ($log = $db->fetch_array($query)) {
            if (intval($log['uid']) > 0)
                $table->construct_cell(build_profile_link(htmlspecialchars_uni($log['username']), intval($log['uid'])), array('class' => 'align_center'));
            else
                $table->construct_cell('System', array('class' => 'align_center'));
            $table->construct_cell($db->escape_string($log['type']), array('class' => 'align_center'));
            $table->construct_cell($db->escape_string($log['data']));
            $table->construct_cell(my_date($mybb->settings['dateformat'], intval($log['date']), '', false) . ", " . my_date($mybb->settings['timeformat'], intval($log['date'])), array('class' => 'align_center'));
            $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=logs&amp;action=delete&amp;lid=" . intval($log['lid']) . "\">{$lang->advinvsys_delete}</a>", array('class' => 'align_center'));
            $table->construct_row();
        }
        
        // in cazul in care nu a existat niciun rand intors din baza de date atunci se afiseaza un mesaj central
        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->advinvsys_logs_without, array('class' => 'align_center', 'colspan' => 5));
            $table->construct_row();
        }
        
        // in final se afiseaza tabelul pe ecranul utilizatorului
        $table->output($lang->advinvsys_logs);
        
        // se realizeaza paginarea
        echo draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=config-advinvsys&amp;section=logs&amp;page={page}");
        
        echo '<div id="advinvsys_prune_help" class="advinvsys_prune_help">';
        $form_container = new FormContainer('<a name="help">' . $lang->advinvsys_help_log_title . '</a><span style="float:right">[<a href="#" class="advinvsys_close_help" >' . $lang->advinvsys_close . '</a>]</span>');
        $form_container->output_cell($lang->advinvsys_help_log_content);
        $form_container->construct_row();
        $form_container->end();
        echo '</div>';
        
        // formular prin care pot fi sterse o serie de log-uri
        $form = new Form("index.php?module=config-advinvsys&amp;section=logs", "post", "logs");
        // se genereaza o cheie de tip post
        echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        // numele formularului	
        $form_container = new FormContainer($lang->advinvsys_logs_prune . "<a href=\"#help\" class=\"advinvsys_get_help\" rel=\"advinvsys_prune_help\">&nbsp;</a>");
        $form_container->output_row_header($lang->advinvsys_logs_prune_field, array("style" => "width: 35%"));
        $form_container->output_row_header($lang->advinvsys_logs_prune_test, array("style" => "width: 35%"));
        $form_container->output_row_header($lang->advinvsys_logs_prune_value, array("style" => "width: 28%"));
        $form_container->output_row_header("&nbsp;", array("class" => "align_center", "style" => "width: 2%"));
        // se returneaza toate campurile posbilie
        $fields = advinvsys_logs_get_fields();
        $field_select = array('' => '');
        foreach ($fields as $key => $field) {
            $field_select[$key] = $field['title'];
        }
        // se genereaza list-ul cu campurile
        $form_container->output_cell(
                $form->generate_select_box('field[]', $field_select, array(), array('class' => 'advinvsys_field'))
        );
        // se genereaza list-ul cu posibile teste
        $form_container->output_cell(
                $form->generate_select_box('test[]', advinvsys_get_tests(), array(), array('class' => 'advinvsys_test'))
        );
        // se genereaza textbox-ul cu valoarea de test	
        $form_container->output_cell(
                $form->generate_text_box('value[]', "", array('class' => 'advinvsys_value'))
        );
        // in fine, urmeaza si imaginea	
        $form_container->output_cell(
                '<a href="#" class="advinvsys_add_filter" id="advinvsys_add_filter">&nbsp;</a>', array('style' => "text-align:center")
        );
        $form_container->construct_row();
        // se afiseaza formularul pe ecran			
        $form_container->end();
        // butoanele din cadrul formularului		
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        $form->end();
        
        // se afiseaza subsolul paginii
        $page->output_footer();
    } elseif ($mybb->input['action'] == 'delete') {
        if ($mybb->input['no']) {
            // userul nu a mai confirmat
            admin_redirect("index.php?module=config-advinvsys&amp;section=logs");
        }
        // se verifica cererea
        if ($mybb->request_method == "post") {
            // daca codul cererii nu e corect atunci se afiseaza o eroare pe ecran
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys&amp;section=logs");
            }
            
            // exista id-ul update-ului specificat in cerere in sistem?
            if (!($version = $db->fetch_array($db->simple_select('advinvsys_logs', 
                    'type', 'lid = ' . intval($mybb->input['lid']), array('limit' => 1))))) {
                flash_message($lang->advinvsys_errors_invalid_id, 'error');
                admin_redirect('index.php?module=config-advinvsys&amp;section=logs');
            } else {
                // daca se ajunge pe aceasta ramura inseamna ca se poate sterge update-ul
                $db->delete_query('advinvsys_logs', "lid = '" . intval($mybb->input['lid']) . "'");
                
                // se afiseaza pe ecran un mesaj precum totul s-a realizat cu succes
                flash_message($lang->sprintf($lang->advinvsys_logs_deleted, 
                        intval($mybb->input['lid'])), 'success');
                admin_redirect('index.php?module=config-advinvsys&amp;section=logs');
            }
        } else {
            // pagina de confirmare
            $page->add_breadcrumb_item($lang->advinvsys_modname);
            $page->add_breadcrumb_item($lang->advinvsys_logs, 
                    'index.php?module=config-advinvsys&amp;section=logs');
            
            // se afiseaza antetul paginii	
            $page->output_header($lang->advinvsys_confirmation_page);
            
            // se converteste inputul la intreg
            $mybb->input['lid'] = intval($mybb->input['lid']);
            
            $form = new Form("index.php?module=config-advinvsys&amp;section=logs&amp;action=delete&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->advinvsys_confirmation_mess}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->advinvsys_button_yes, array('class' => 'button_yes'));
            echo $form->generate_submit_button($lang->advinvsys_button_no, array("name" => "no", 'class' => 'button_no'));
            echo "</p>\n";
            echo "</div>\n";
            $form->end();
            
            // in fine se afiseaza si subsolul paginii
            $page->output_footer();
        }
    }
} else if (strcmp($mybb->input['section'], "modules") == 0) {
    
    // se incarca un alt fisier de limba
    $lang->load("config_plugins", false, true);
    
    // pagina de vizualizare a modulelor din cadrul modificarii
    if (!$mybb->input['action']) {
        // se importa un modul?
        if ($mybb->request_method == "post") {
            if (!$_FILES['local_file'] && !$mybb->input['url']) {
                $errors[] = $lang->advinvsys_import_missing_url;
            }
            if (!$errors) {
                // exista deja un fisier incarcat?
                if ($_FILES['local_file']['error'] != 4) {
                    // probleme la incarcarea fisierului
                    if ($_FILES['local_file']['error'] != 0) {
                        $errors[] = $lang->advinvsys_import_uploadfailed . $lang->advinvsys_import_uploadfailed_detail;
                        switch ($_FILES['local_file']['error']) {
                            case 1: // UPLOAD_ERR_INI_SIZE
                                $errors[] = $lang->advinvsys_import_uploadfailed_php1;
                                break;
                            case 2: // UPLOAD_ERR_FORM_SIZE
                                $errors[] = $lang->advinvsys_import_uploadfailed_php2;
                                break;
                            case 3: // UPLOAD_ERR_PARTIAL
                                $errors[] = $lang->advinvsys_import_uploadfailed_php3;
                                break;
                            case 6: // UPLOAD_ERR_NO_TMP_DIR
                                $errors[] = $lang->advinvsys_import_uploadfailed_php6;
                                break;
                            case 7: // UPLOAD_ERR_CANT_WRITE
                                $errors[] = $lang->advinvsys_import_uploadfailed_php7;
                                break;
                            default:
                                $errors[] = $lang->sprintf($lang->advinvsys_import_uploadfailed_phpx, $_FILES['local_file']['error']);
                                break;
                        }
                    }
                    if (!$errors) {
                        // s-a gasit fisierul temporar
                        if (!is_uploaded_file($_FILES['local_file']['tmp_name'])) {
                            $errors[] = $lang->advinvsys_import_uploadfailed_lost;
                        }
                        
                        // se obtine continutul fisierului
                        $contents = @file_get_contents($_FILES['local_file']['tmp_name']);
                        
                        // se sterge fisierul temporar, daca acest lucru este posibil
                        @unlink($_FILES['local_file']['tmp_name']);
                        
                        // exista continut nevid?
                        if (!trim($contents)) {
                            $errors[] = $lang->advinvsys_import_uploadfailed_nocontents;
                        }
                    }
                } elseif (!empty($mybb->input['url'])) {
                    // se intoarce continutul fisierului de la adresa web specificata
                    $contents = @fetch_remote_file($mybb->input['url']);
                    
                    if (!$contents) {
                        $errors[] = $lang->advinvsys_import_local_file;
                    }
                } else {
                    // UPLOAD_ERR_NO_FILE
                    $errors[] = $lang->advinvsys_import_uploadfailed_php4;
                }

                // daca in acest moment nu exista erori se trece la crearea fisierului modulului
                if (!$errors) {
                    // un carlig este adaugat
                    $plugins->run_hooks('advinvsys_admin_modules_do_make');
                    
                    // se face crearea modulului
                    $result = advinvsys_make_module($contents);
                    
                    if ($result) {
                        flash_message($lang->advinvsys_import_success, 'success');
                        admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
                    } else {
                        $errors[] = $lang->advinvsys_import_error_make;
                    }
                }
            }
        }
        
        // pagina principala
        $page->add_breadcrumb_item($lang->advinvsys_modname);
        $page->add_breadcrumb_item($lang->advinvsys_modules);
        
        // se afiseaza antetul paginii
        $page->extra_header .= '
            <style type="text/css">
            a.advinvsys_help_dev {
                display: block; height: 16px; width: 16px; text-indent: -999px; text-decoration: none; overflow: hidden; padding: 0px; margin: 0px;
            }
            a.advinvsys_help_dev {
                background: transparent url(../images/advinvsys/dev.png);
                cursor: help;
                float: right;
            }
            </style>
            <script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
	        <script type="text/javascript">
            Event.observe(document, "dom:loaded", function() {
                // evenimentul pentru a apare casuta de ajutor
                $$(\'.advinvsys_help_dev\').invoke(\'observe\', \'click\', function(e) {
                    if($(this.rel)) {
                        Effect.toggle(this.rel, \'Blind\');
                    }
                    Event.stop(e);
                });
                $$(".advinvsys_close_dev").invoke("observe", "click", function(e) {
                    Effect.toggle(this.up(3), "Blind");
                    Event.stop(e);
                });
                // in mod implicit casuta de ajutor nu este afisata pe ecran
                $("advinvsys_module_dev").hide();
            });	   
	        </script>';
        $page->output_header($lang->advinvsys_modules);
        // se afiseaza meniul orizontal
        $page->output_nav_tabs($sub_tabs, 'modules');

        // se intorc toate posibilele actualizari
        $upgrades = advinvsys_get_upgrades();
        
        // tabelul cu date de actualizare
        $table = new Table;
        // se construieste antetul tabelului
        $table->construct_header($lang->advinvsys_upgrades_name, array('width' => '70%'));
        $table->construct_header($lang->advinvsys_upgrades_controls, array('width' => '30%', 'class' => 'align_center'));
        // cat timp exista upgrade-uri
        if (!empty($upgrades)) {
            
            // pentru fiecare actualizare in parte
            foreach ($upgrades as $upgrade) {
                $codename = str_replace(".php", "", $upgrade['file']);
                
                $from = array();
                $to = array();
                $id = 2;
                while ($id >= 0) {
                    $from[] = intval($upgrade['from'] / pow(10, $id)) % 10; 
                    $to[] = intval($upgrade['to'] / pow(10, $id)) % 10; 
                    $id--;
                }
                
                // se adauga un nou rand in tabel
                $table->construct_cell('<a href="http://mybb.ro" target="_blank"><b>' . 
                        $lang->advinvsys_upgrades_name . '</b></a> (v' . 
                        @implode('.', $from) . ' => v' . @implode('.', $to) . ')<br /><i><small>' . 
                        $lang->created_by . ' MyBB Rom&#226;nia Team</small></i>');
                $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=modules&amp;action=run&amp;upgrade_file=" . $codename . "&amp;my_post_key={$mybb->post_code}\" target=\"_self\">{$lang->advinvsys_upgrades_run}</a>", array('class' => 'align_center'));
                $table->construct_row();
            }
        } else {
            // nu exista actualizari disponibile pentru versiunea ta de AIS
            $table->construct_cell($lang->advinvsys_upgrades_no, 
                    array('colspan' => 2, 'class' => 'align_center'));
            $table->construct_row();
        }
        
        // in cele din urma se afiseaza si tabelul
        $table->output($lang->advinvsys_upgrades_title);

        // se citesc datele din cache
        $modules_cache = $cache->read("advinvsys_modules");
        
        // se preiau modulele active din cache
        $active_modules = $modules_cache['active'];
        
        // se intoarce lista cu module de pe server
        $modules_list = advinvsys_get_modules();
        
        // se creaza continutul ajutator
        echo '<div id="advinvsys_module_dev" class="advinvsys_module_dev">';
        $form_container = new FormContainer('<a name="help">' . $lang->advinvsys_get_help . '</a><span style="float:right">[<a href="#" class="advinvsys_close_dev" >' . $lang->advinvsys_close . '</a>]</span>');
        $form_container->output_cell($lang->advinvsys_modules_development);
        $form_container->construct_row();
        $form_container->end();
        echo '</div>';
        
        // un alt carlig
        $plugins->run_hooks('advinvsys_admin_modules_start');
        
        // se creaza tabelul principal de pe pagina
        $table = new Table;
        $table->construct_header($lang->advinvsys_module, array("width" => "70%"));
        $table->construct_header($lang->controls, array("colspan" => 2, "class" => "align_center", "width" => "30%"));
        
        // daca lista cu module nu este goala...	
        if (!empty($modules_list)) {
            // pentru fiecare modul din lista
            foreach ($modules_list as $module) {
                
                // se include modulul
                require_once MYBB_ROOT . "inc/plugins/advinvsys/plugins/" . $module;
                
                // se intoarce doar numele fisierului, fara extensie
                $codename = str_replace(".php", "", $module);
                
                // un alt carlig
                $plugins->run_hooks('advinvsys_admin_modules_file');
                
                // functia cu informatiile despre modul...
                $infofunc = "ais_" . $codename . "_info";
                if (!function_exists($infofunc)) {
                    continue;
                }
                
                // se intorc datele din cadrul functiei cu informatiile
                $moduleinfo = $infofunc();
                if ($moduleinfo['website']) {
                    $moduleinfo['name'] = "<a href=\"" . $moduleinfo['website'] . "\" target=\"_blank\">" . $moduleinfo['name'] . "</a>";
                }
                if ($moduleinfo['authorsite']) {
                    $moduleinfo['author'] = "<a href=\"" . $moduleinfo['authorsite'] . "\" target=\"_blank\">" . $moduleinfo['author'] . "</a>";
                }
                
                // este modulul compatibil?
                if (!advinvsys_is_compatible($moduleinfo['compatibility'])) {
                    $compatibility_warning = "<span style=\"color: red;\">" . $lang->sprintf($lang->plugin_incompatible, AIS_VERSION) . "</span>";
                } else {
                    // totul este OK!
                    $compatibility_warning = "";
                }
                
                // se adauga un nou rand in tabel cu modulul
                $table->construct_cell("<strong>{$moduleinfo['name']}</strong> ({$moduleinfo['version']})<br /><small>{$moduleinfo['description']}</small><br /><i><small>{$lang->created_by} {$moduleinfo['author']}</small></i>");
                
                // modulul este activat
                if ($active_modules[$codename]) {
                    $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=modules&amp;action=deactivate&amp;module_name={$codename}&amp;my_post_key={$mybb->post_code}\">{$lang->deactivate}</a>", array("class" => "align_center"));
                    $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=modules&amp;action=export&amp;module_name={$codename}&amp;my_post_key={$mybb->post_code}\">Export</a>", array("class" => "align_center"));
                } else {
                    // modulul nu este activat
                    $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=modules&amp;action=activate&amp;module_name={$codename}&amp;my_post_key={$mybb->post_code}\">{$lang->activate}</a>", array("class" => "align_center"));
                    $table->construct_cell("<a href=\"index.php?module=config-advinvsys&amp;section=modules&amp;action=delete&amp;module_name={$codename}&amp;my_post_key={$mybb->post_code}\">{$lang->advinvsys_delete}</a>", array("class" => "align_center"));
                }
                
                // se afiseaza randul
                $table->construct_row();
            }
        }
        
        // nu exista module care sa fie afisate...
        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->advinvsys_modules_without, array('colspan' => 3, 'class' => 'align_center'));
            $table->construct_row();
        }
        
        // se afiseaza tabelul creat pe ecran
        $table->output($lang->advinvsys_modules . "<a href=\"#help\" class=\"advinvsys_help_dev\" rel=\"advinvsys_module_dev\">&nbsp;</a>");

        $plugins->run_hooks('advinvsys_admin_modules_url');

        // exista erori?
        if ($errors) {
            $page->output_inline_error($errors);
            if ($mybb->input['import'] == 1) {
                $import_checked[1] = "";
                $import_checked[2] = "checked=\"checked\"";
            } else {
                $import_checked[1] = "checked=\"checked\"";
                $import_checked[2] = "";
            }
        } else {
            $import_checked[1] = "checked=\"checked\"";
            $import_checked[2] = "";
        }

        // se genereaza formularul
        $form = new Form("index.php?module=config-advinvsys&amp;section=modules", "post", "", 1);
        $actions = '<script type="text/javascript">
                function checkAction(id) {
                    var checked = \'\';
                    $$(\'.\'+id+\'s_check\').each(function(e) {
                        if(e.checked == true) {
                            checked = e.value;
                        }
                    });
                    $$(\'.\'+id+\'s\').each(function(e) {
                        Element.hide(e);
                    });
                    if($(id+\'_\'+checked)) {
                        Element.show(id+\'_\'+checked);
                    }
                }
                </script>
<dl style="margin-top: 0; margin-bottom: 0; width: 35%;">
<dt><label style="display: block;"><input type="radio" name="import" value="0" ' . $import_checked[1] . ' class="imports_check" onclick="checkAction(\'import\');" style="vertical-align: middle;" /> ' . $lang->advinvsys_import_file . '</label></dt>
<dd style="margin-top: 0; margin-bottom: 0; width: 100%;" id="import_0" class="imports">
<table cellpadding="4">
    <tr>
        <td>' . $form->generate_file_upload_box("local_file", array('style' => 'width: 230px;')) . '</td>
    </tr>
</table>
</dd>	
<dt><label style="display: block;"><input type="radio" name="import" value="1" ' . $import_checked[2] . ' class="imports_check" onclick="checkAction(\'import\');" style="vertical-align: middle;" /> ' . $lang->advinvsys_import_url . '</label></dt>
<dd style="margin-top: 0; margin-bottom: 0; width: 100%;" id="import_1" class="imports">
<table cellpadding="4">
<tr>
    <td>' . $form->generate_text_box("url", $mybb->input['file']) . '</td>
</tr>
</table></dd>
</dl>
<script type="text/javascript">checkAction(\'import\');</script>';
        $form_container = new FormContainer($lang->advinvsys_import_a_module);
        $form_container->output_row($lang->advinvsys_import_from, $lang->advinvsys_import_from_description, $actions, 'file');
        $form_container->end();
        // se creaza butoanele
        $buttons[] = $form->generate_submit_button($lang->advinvsys_import_module);
        // se afiseaza butoanele formularului
        $form->output_submit_wrapper($buttons);
        $form->end();

        $plugins->run_hooks('advinvsys_admin_modules_end');

        // se afiseaza subsolul paginii
        $page->output_footer();
    } elseif ($mybb->input['action'] == 'run') {
        if ($mybb->input['no']) {
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        if ($mybb->request_method == "post") {
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
            }
            
            // ce fisier ce va folosi?
            $upgrade = $mybb->input['upgrade_file'];
            
            // se include fisierul ales
            require_once MYBB_ROOT . "inc/plugins/advinvsys/upgrades/" . $upgrade . ".php";
            
            // se apeleaza functia de rulare
            $runfunc = $upgrade . '_run';
            if (!function_exists($runfunc)) {
                continue;
            }
            
            $result = $runfunc();
            
            // daca $result = true atunci upgrade-ul s-a realizat cu succes
            if ($result) {
                flash_message($lang->advinvsys_upgrades_ran, 'success');
            } else {
                // altfel a aparut o eroare
                flash_message($lang->advinvsys_upgrades_error, 'error');
            }
            
            // in ambele cazuri se face aceeasi redirectionare...
            admin_redirect('index.php?module=config-advinvsys&amp;section=modules');
        } else {
            // se va afisa o pagina de confirmare
            $page->add_breadcrumb_item($lang->advinvsys_upgrades_title, 
                    'index.php?module=config-advinvsys&amp;section=modules');
            
            $page->output_header($lang->advinvsys_upgrades_title);
            
            // se curata sirul de caractere primit
            $mybb->input['upgrade_file'] = htmlspecialchars($mybb->input['upgrade_file']);
            
            $form = new Form("index.php?module=config-advinvsys&amp;section=modules&amp;action=run&amp;upgrade_file=" . $mybb->input['upgrade_file'] . "&amp;my_post_key={$mybb->post_code}", 'post');
            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->advinvsys_upgrades_confirm}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->advinvsys_button_yes, array('class' => 'button_yes'));
            echo $form->generate_submit_button($lang->advinvsys_button_no, array("name" => "no", 'class' => 'button_no'));
            echo "</p>\n";
            echo "</div>\n";
            $form->end();
        }
    } elseif ($mybb->input['action'] == "activate" || $mybb->input['action'] == "deactivate") {
        // se activeaza sau dezactiveaza un modul
        if (!verify_post_check($mybb->input['my_post_key'])) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        // se intoarce numele modului care va fi activat sau dezactivat
        $codename = $db->escape_string($mybb->input['module_name']);
        $codename = str_replace(array(".", "/", "\\"), "", $codename);
        
        // se formeaza fisierul modulului
        $file = basename($codename . ".php");
        
        // daca fisierul astfel format nu exista pe server atunci se intoarce o eroare
        if (!file_exists(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}")) {
            flash_message($lang->advinvsys_modules_invalid, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        $modules_cache = $cache->read("advinvsys_modules");
        $active_modules = $modules_cache['active'];
        
        // se include fisierul modulului
        require_once MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}";
        
        // modulul se activeaza sau se dezactiveaza?
        if ($mybb->input['action'] == "activate") {
            $message = $lang->advinvsys_modules_activated;
            
            // se realizeaza apelul functiei de activare
            if (function_exists("ais_{$codename}_activate")) {
                call_user_func("ais_{$codename}_activate");
            }
            
            // se adauga modulul in lista modulelor active
            $active_modules[$codename] = $codename;
        } else {
            
            // se va dezactiva un modul
            $message = $lang->advinvsys_modules_deactivated;
            
            // se realizeaza apelul functiei de dezactizare a modulului
            if (function_exists("ais_{$codename}_deactivate")) {
                call_user_func("ais_{$codename}_deactivate");
            }
            
            // se sterge modulul din lista modulelor active
            unset($active_modules[$codename]);
        }
        
        // se actualizeaza lista modulelor active de pe server
        $modules_cache['active'] = $active_modules;
        $cache->update("advinvsys_modules", $modules_cache);
        
        // se afiseaza mesajul pe ecran
        flash_message($message, 'success');
        admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
    } elseif ($mybb->input['action'] == "delete") {
        // se activeaza sau dezactiveaza un modul
        if (!verify_post_check($mybb->input['my_post_key'])) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        // se intoarce numele modului care va fi activat sau dezactivat
        $codename = $db->escape_string($mybb->input['module_name']);
        $codename = str_replace(array(".", "/", "\\"), "", $codename);
        
        // se formeaza fisierul modulului
        $file = basename($codename . ".php");
        
        // daca fisierul astfel format nu exista pe server atunci se intoarce o eroare
        if (!file_exists(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}")) {
            flash_message($lang->advinvsys_modules_invalid, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        // daca modulul este activ atunci se dezactiveaza si apoi se sterge
        $modules_cache = $cache->read("advinvsys_modules");
        // se preiau modulele active din cache
        $active_modules = $modules_cache['active'];
        
        // daca modulul exista in vector
        if (is_array($active_modules) && array_key_exists($codename, $active_modules)) {
            
            // se include modulul
            require_once MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}";
            
            // daca functia exista se dezactiveaza modulul
            if (function_exists("ais_{$codename}_deactivate")) {
                call_user_func("ais_{$codename}_deactivate");
            }
            unset($active_modules[$codename]);
            
            // se face o actualizare a cache-ului
            $modules_cache['active'] = $active_modules;
            $cache->update("advinvsys_modules", $modules_cache);
        }
        
        if (unlink(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}")) {
            flash_message($lang->sprintf($lang->advinvsys_modules_delete_success, $file), 'success');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        } else {
            flash_message($lang->sprintf($lang->advinvsys_modules_delete_error, $file), 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
    } elseif ($mybb->input['action'] == 'export') {
        // se verifica post check-ul
        if (!verify_post_check($mybb->input['my_post_key'])) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        $codename = $db->escape_string($mybb->input['module_name']);
        
        // se formeaza fisierul modulului
        $file = $codename . ".php";
        
        // daca fisierul astfel format nu exista pe server atunci se intoarce o eroare
        if (!file_exists(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}")) {
            flash_message($lang->advinvsys_modules_invalid, 'error');
            admin_redirect("index.php?module=config-advinvsys&amp;section=modules");
        }
        
        // care este continutul fisierului
        $content = @file_get_contents(MYBB_ROOT . "inc/plugins/advinvsys/plugins/{$file}");
        
        // se face exportul propriu-zis
        $xml = advinvsys_export_module($content);
        
        // se construieste antetul paginii ce se va afisa
        header("Content-disposition: attachment; filename=" . rawurlencode($codename) . "-module.xml");
        header("Content-type: application/octet-stream");
        header("Content-Length: " . strlen($xml));
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $xml;
    }
} else if (strcmp($mybb->input['section'], 'stats') == 0) {
    if ($mybb->input['action'] == 'do_graph') {
        // se include clasa de care avem nevoie pentru a crea grafice
        require_once  MYBB_ROOT.'inc/class_graph.php';
        
        // vectorul cu informatii
        $points = array();
        // eticheta principala
        $label = $lang->advinvsys_stats_label;
        
        // ce grafic se va crea?
        if ($mybb->input['type'] == "topinvs") {
            // campurile ce ne intereseaza
            $fields = array('uid', 'username', 'invitations');
            
            // se realizeaza interogarea bazei de date
            $query = $db->simple_select('users', implode(',', $fields), 'invitations > 0', 
                    array('order_by' => 'invitations', 'order_dir' => 'DESC', 'limit' => 10));
 
            // pentru fiecare rand obtinut
            while ($user = $db->fetch_array($query)) {
                $points[$user['username']] = intval($user['invitations']);
            }
            
            $points[''] = 0;
            
            // se sorteaza crescator
            $points = array_reverse($points);
        } else if ($mybb->input['type'] == "topreff") {
            // se realizeaza interogarea bazei de date
            $query = $db->write_query("SELECT invitation_refer AS refer, COUNT(uid) AS number 
                FROM " . TABLE_PREFIX . "users WHERE invitation_refer > 0
                GROUP BY invitation_refer ORDER BY number DESC LIMIT 10");
            
            // pentru fiecare rand obtinut
            while ($user = $db->fetch_array($query)) {
                $user1 = get_user(intval($user['refer']));
                $points[$user1['username']] = intval($user['number']);
            }
            
            $points[''] = 0;
            
            // se sorteaza crescator
            $points = array_reverse($points);
        } else {
            // se ofera posibilitatea de a se adauga si alte grafice prin carlige
            $plugins->run_hooks('advinvsys_admin_stats_do_graph');
        }
        
        // se creaza un nou grafic
        $graph = new Graph();
        
        // se adauga punctele pe grafic
        $graph->add_points(array_values($points));
        
        // se adauga etichetele de pe orizontala
        $graph->add_x_labels(array_keys($points));
        
        // este setat si un label...
        $graph->set_bottom_label($label);
        
        // se creaza imaginea
        $graph->render();
        
        // se afiseaza graficul
        $graph->output();
        
        // se iese fortat
        exit(0);
    }
    
    // sunt afisate o serie de statistici
    $page->add_breadcrumb_item($lang->advinvsys_modname);
    $page->add_breadcrumb_item($lang->advinvsys_stats);
    $page->output_header($lang->advinvsys_stats);
    
    // se afiseaza meniul orizontal
    $page->output_nav_tabs($sub_tabs, 'stats');

    $plugins->run_hooks('advinvsys_admin_stats_start');

    echo "<fieldset><legend>{$lang->advinvsys_stats_richest}</legend>\n";
    echo "<img src=\"index.php?module=config-advinvsys&amp;section=stats&amp;action=do_graph&amp;type=topinvs\" />\n";
    echo "</fieldset>\n";

    $plugins->run_hooks('advinvsys_admin_stats_middle');
    
    echo "<fieldset><legend>{$lang->advinvsys_stats_refs}</legend>\n";
    echo "<img src=\"index.php?module=config-advinvsys&amp;section=stats&amp;action=do_graph&amp;type=topreff\" />\n";
    echo "</fieldset>\n";
 
    $plugins->run_hooks('advinvsys_admin_stats_end');

    // se afiseaza subsolul paginii
    $page->output_footer();
} else if (strcmp($mybb->input['section'], "about") == 0) {
    $page->add_breadcrumb_item($lang->advinvsys_modname);
    $page->add_breadcrumb_item($lang->advinvsys_about);
    $page->output_header($lang->advinvsys_about);
    
    // se afiseaza meniul orizontal
    $page->output_nav_tabs($sub_tabs, 'about');

    // sectiunea din dreapta
    echo '<div><div class="float_right" style="width:50%;">';

    // se da posibilitatea de a se adauga si alti oameni in echipa
    $team_array = array();
    $team_array = $plugins->run_hooks('advinvsys_admin_about_team', $team_array);
    
    // vectorul cu echipa trebuie sa fie deja ordonat dupa cheie
    $team = array(
        'Baltzatu' => 'Beta Tester',
        'Lokki' => 'Beta Tester',
        'Mihu' => 'Main Developer',
        'Petre-Vitan' => 'Beta Tester'
    );

    // se adauga si ceilalti oameni care au contribuit la acesta modificare prin
    // modulele realizate de ei
    if (count($team_array) > 0) {
        
        // functia trebuie sa fie una speciala
        $team_array = array_filter($team_array, create_function('$value', 
                'return (in_array($value, array("Beta Tester", "Contributor", "Developer"))) ? 1 : 0;'));
        
        // nu se vor putea suprascrie cei care au dezvoltat aceasta aplicatie
        $team = array_merge($team, $team_array);
        
        // se sorteaza vectorul alfabetic dupa cheie
        ksort($team);
    }

    $table = new Table;
    // antetul tabelului
    $table->construct_header("<small>" . $lang->advinvsys_about_team_name . "</small>", array('width' => '50%', 'class' => 'align_center'));
    $table->construct_header("<small>" . $lang->advinvsys_about_team_contribution . "</small>", array('width' => '50%', 'class' => 'align_center'));

    // randurile tabelului
    foreach ($team as $name => $position) {
        $table->construct_cell('<strong>' . htmlspecialchars($name) . '</strong>', array('class' => 'align_center'));
        $table->construct_cell(htmlspecialchars($position), array('class' => 'align_center'));
        $table->construct_row();
    }
        
    // se afiseaza tabelul pe ecran    
    $table->output($lang->advinvsys_about_team);
    
    // se creaza tabelul cu licenta modificarii
    $table = new Table;
    $table->construct_header("<small>" . $lang->sprintf($lang->advinvsys_about_license_version, AIS_VERSION) . "</small>");
    $table->construct_cell("<p align=\"justify\" style=\"margin: 0px\">" . $lang->advinvsys_about_license_text . "</p>");
    $table->construct_row();
        
    // se afiseaza si al doilea tabel    
    $table->output($lang->advinvsys_about_license);
    
    // in fine se afiseaza si tabelul pentru donatii
    $table = new Table;
    $table->construct_cell('<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="D8N958QBWT5XY"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>', 
            array('width' => '30%', 'class' => 'align_center'));
    $table->construct_cell('<small>' . $lang->advinvsys_about_donate_body . '</small>', array('width' => '70%'));   
    $table->construct_row();  
    $table->output($lang->advinvsys_about_donate_title);
    
    $plugins->run_hooks('advinvsys_admin_about_right');

    // sectiunea din stanga   
    echo '</div><div class="float_left" style="width:48%;">';
    echo $lang->advinvsys_about_text_left;
    $plugins->run_hooks('advinvsys_admin_about_left');
    echo '</div></div>';

    // se afiseaza subsolul paginii
    $page->output_footer();
} else {
    $page->add_breadcrumb_item($lang->advinvsys_modname);
    $page->add_breadcrumb_item($lang->advinvsys_main);

    // lista de actiuni pentru reguli
    $list = array(
        'newthread' => 'New Thread',
        'newreply' => 'New Post',
        'newpoll' => 'New Poll',
        'pollpervote' => 'New Poll Vote',
        'threadrate' => 'New Thread Rate',
        'deletepost' => 'Delete Post',
        'deletethread' => 'Delete Thread',
        'newregistration' => 'New Registration',
        'newreputation' => 'New Reputation'
    );
    // unele campuri sunt speciale (necesita un camp aditional)
    $special = array(
        '"newregistration"' => '"user who reffered a new registration"',
        '"newreputation"' => '"user who give these reputation points"'
    );

    $plugins->run_hooks('advinvsys_admin_rule_action');

    if ($mybb->input['action'] == 'addincome') {
        if ($mybb->request_method == "post") {
            // este cererea autentica
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys&amp;action=addincome");
            }
            
            // unele campuri importante au ramas necompletate
            if (!$mybb->input['name'] || !$mybb->input['type'] ||
                    !is_numeric($mybb->input['additional']) || !is_numeric($mybb->input['invitations'])) {
                flash_message($lang->advinvsys_errors_missing_fields, 'error');
                admin_redirect("index.php?module=config-advinvsys&amp;action=addincome");
            }
            
            // se insereaza in baza de date
            $insert_query = array(
                'enabled' => intval($mybb->input['enabled']),
                'name' => $db->escape_string($mybb->input['name']),
                'type' => $db->escape_string($mybb->input['type']),
                'description' => $db->escape_string($mybb->input['description']),
                'fid' => @join(',', $mybb->input['fids']),
                'gid' => @join(',', $mybb->input['gids']),
                'invitations' => $AISDevelop->truncNumber($mybb->input['invitations']),
                'additional' => $AISDevelop->truncNumber($mybb->input['additional'])
            );
            
            $insert_query = $plugins->run_hooks('advinvsys_admin_rule_do_add_start', $insert_query);
            
            $id = $db->insert_query('advinvsys_incomes', $insert_query);
            
            // se actualizeaza si cache-ul
            if (function_exists('advinvsys_update_cache'))
                advinvsys_update_cache();
            
            // se adauga si un log in sistem
            $AISDevelop->addLog($lang->advinvsys_main_income_added_logt, 
                $lang->sprintf($lang->advinvsys_main_income_added_log, $id), 
                $mybb->user['uid']);
            
            // un alt carlig
            $plugins->run_hooks('advinvsys_admin_rule_do_add_end');
            
            // se afiseaza un mesaj daca totul s-a realizat cu succes
            flash_message($lang->advinvsys_main_income_added, 'success');
            admin_redirect("index.php?module=config-advinvsys");
        }
        
        // se sorteaza lista dupa valori (se mentine asocierea cu cheile!)
        uasort($list, create_function('$a,$b', 'return strcmp($a,$b);'));
        
        $page->extra_header .= '
        <script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
	<script type="text/javascript">
            Array.prototype.contains = function(element) {
                for (var i = 0; i < this.length; i++)
                    if (this[i] == element) 
                        return i;
                return -1;
            };
            Event.observe(document, "dom:loaded", function() {
                // evenimentul asociat introducerii unei noi valori "Income Rate"
                $(\'invitations\').observe(\'change\', function() {
                    if (this.value.match(/^(\+|\-)?\d{1,}(\.\d{1,2})?$/)) {
                        this.setStyle({ background: \'#BCF5A9\' });
                    } else {
                        this.setStyle({ background: \'#F5A9BC\' });
                    }
                });
                // evenimentul asociat campului aditional
                $(\'additional\').observe(\'change\', function() {
                    if (this.value.match(/^(\+|\-)?\d{1,}(\.\d{1,2})?$/)) {
                        this.setStyle({ background: \'#BCF5A9\' });
                    } else {
                        this.setStyle({ background: \'#F5A9BC\' });
                    }
                });
                // evenimentul pentru alegerea unei actiuni
                $(\'type\').observe(\'change\', function(e) {
                    // care dintre actiuni necesita campul aditional?
                    var keys = [' . join(',', array_keys($special)) . '].uniq();
                    var values = [' . join(',', array_values($special)) . '].uniq();
                    var i = keys.contains(this.value);    
                    if (i != -1) {
                        $(\'additional_field\').appear();
                        if ($(\'additional_rate\'))
                            $(\'additional_rate\').innerHTML = values[i];
                    } else {
                        $(\'additional\').value = \'0\';
                        $(\'additional_field\').fade();
                    }
                    if (this.value == "newregistration") {
                        $(\'fids\').disable();
                        $(\'gids\').disable();
                    } else {
                        $(\'fids\').enable();
                        $(\'gids\').enable();
                    }
                });
                // se ascunde campul aditional
                $(\'additional_field\').hide();
            });	   
	</script>';
        
        $page->output_header($lang->advinvsys_main);
        
        // se afiseaza meniul orizontal special
        $page->output_nav_tabs($sub_tabs, 'main');
        
        // daca nu e post atunci se afiseaza formularul
        $form = new Form("index.php?module=config-advinvsys&amp;action=addincome", "post");
        // este generat si un post key
        echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        $form_container = new FormContainer($lang->advinvsys_main_income_add);
        $form_container->output_row($lang->advinvsys_main_income_name . "<em>*</em>", $lang->advinvsys_main_income_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
        $form_container->output_row($lang->advinvsys_main_income_desc, $lang->advinvsys_main_income_desc_desc, $form->generate_text_area('description', '', array('id' => 'description')), 'description');
        $form_container->output_row($lang->advinvsys_main_income_type . "<em>*</em>", $lang->advinvsys_main_income_type_desc, $form->generate_select_box('type', $list, null, array('id' => 'type')), 'type');
        $form_container->output_row($lang->advinvsys_main_income_rate . "<em>*</em>", $lang->advinvsys_main_income_rate_desc, $form->generate_text_box('invitations', '1', array('id' => 'invitations')), 'invitations');
        $form_container->output_row($lang->advinvsys_main_income_addi, $lang->advinvsys_main_income_addi_desc, $form->generate_text_box('additional', '0', array('id' => 'additional')), 'additional', '', array('id' => 'additional_field'));
        $form_container->output_row($lang->advinvsys_main_income_forum . "<em>*</em>", $lang->advinvsys_main_income_forum_desc, $form->generate_forum_select('fids[]', 0, array('id' => 'fids', 'multiple' => true)), 'fids');
        $form_container->output_row($lang->advinvsys_main_income_group . "<em>*</em>", $lang->advinvsys_main_income_group_desc, $form->generate_group_select('gids[]', 0, array('id' => 'gids', 'multiple' => true)), 'gids');
        $form_container->output_row($lang->advinvsys_main_income_enable, $lang->advinvsys_main_income_enable_desc, $form->generate_yes_no_radio('enabled', 0, true), 'enabled');
        $form_container->end();
        
        // ce butoane vor fi valabile pentru acest formular
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        // asta e totul
    } else if ($mybb->input['action'] == 'editincome') {
        if ($mybb->request_method == "post") {
            if (!isset($mybb->input['my_post_key']) ||
                    $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys");
            }
            
            // verificam daca id-ul regulii specificate exista in sistem
            if ($db->num_rows($db->simple_select('advinvsys_incomes', 'iid', 'iid = \'' . intval($mybb->input['iid']) . '\'')) <= 0) {
                flash_message($lang->advinvsys_main_income_notexist, 'error');
                admin_redirect("index.php?module=config-advinvsys");
            }
            
            if (!$mybb->input['name'] || !$mybb->input['type'] ||
                    !is_numeric($mybb->input['additional']) || !is_numeric($mybb->input['invitations'])) {
                flash_message($lang->advinvsys_errors_missing_fields, 'error');
                admin_redirect("index.php?module=config-advinvsys");
            }
            
            // se construieste vectorul cu informatiile ce se vor actualiza
            $update_query = array(
                'enabled' => intval($mybb->input['enabled']),
                'name' => $db->escape_string($mybb->input['name']),
                'type' => $db->escape_string($mybb->input['type']),
                'description' => $db->escape_string($mybb->input['description']),
                'fid' => @join(',', $mybb->input['fids']),
                'gid' => @join(',', $mybb->input['gids']),
                'invitations' => $AISDevelop->truncNumber($mybb->input['invitations']),
                'additional' => $AISDevelop->truncNumber($mybb->input['additional'])
            );
            
            // inainte de actualizare este permisa inserarea unei alte functii
            $update_query = $plugins->run_hooks('advinvsys_admin_rule_do_edit_start', $update_query);
            $db->update_query('advinvsys_incomes', $update_query, 
                    'iid = \'' . intval($mybb->input['iid']) . '\'');
            
            // se actualizeaza si cache-ul
            if (function_exists('advinvsys_update_cache'))
                advinvsys_update_cache();
            
            // se adauga un log specific actiunii in baza de date
            $AISDevelop->addLog($lang->advinvsys_main_income_edited_logt, 
                $lang->sprintf($lang->advinvsys_main_income_edited_log, intval($mybb->input['iid'])), 
                $mybb->user['uid']);
            
            // un alt carlig
            $plugins->run_hooks('advinvsys_admin_rule_do_edit_end');
            
            // se afiseaza un mesaj daca totul s-a realizat cu succes
            flash_message($lang->advinvsys_main_income_edited, 'success');
            admin_redirect("index.php?module=config-advinvsys");
        }
        
        // altfel se afiseaza formularul de editare
        $query = $db->simple_select('advinvsys_incomes', '*', 
                'iid = \'' . intval($mybb->input['iid']) . '\'');
        $rule = $db->fetch_array($query);
        
        if (!$rule) {
            flash_message($lang->advinvsys_main_income_invalid, 'error');
            admin_redirect("index.php?module=config-advinvsys");
        }
        
        // se sorteaza lista dupa valori (se mentine asocierea cu cheile!)
        uasort($list, create_function('$a,$b', 'return strcmp($a,$b);'));
        
        // se adauga un antet special
        $page->extra_header .= '
        <script type="text/javascript" src="../jscripts/scriptaculous.js?load=effects"></script>
	<script type="text/javascript">
            Array.prototype.contains = function(element) {
                for (var i = 0; i < this.length; i++)
                    if (this[i] == element) 
                        return i;
                return -1;
            };
            Event.observe(document, "dom:loaded", function() {
                // evenimentul pentru alegerea unei actiuni
                $(\'type\').observe(\'change\', function(e) {
                    // care dintre actiuni necesita campul aditional?
                    var keys = [' . join(',', array_keys($special)) . '].uniq();
                    var values = [' . join(',', array_values($special)) . '].uniq();
                    var i = keys.contains(this.value);    
                    if (i != -1) {
                        $(\'additional_field\').appear();
                        if ($(\'additional_rate\'))
                            $(\'additional_rate\').innerHTML = values[i];
                    } else {
                        $(\'additional\').value = \'0\';
                        $(\'additional_field\').fade();
                    }
                    if (this.value == "newregistration") {
                        $(\'fids\').disable();
                        $(\'gids\').disable();
                    } else {
                        $(\'fids\').enable();
                        $(\'gids\').enable();
                    }
                });
                var keys = [' . join(',', array_keys($special)) . '].uniq();
                if (keys.contains("' . $rule['type'] . '") == -1) {
                    // se ascunde campul aditional
                    $(\'additional_field\').hide();
                }
                if ("' . $rule['type'] . '" == "newregistration") {
                    // unele campuri devin inactive
                    $(\'fids\').disable();
                    $(\'gids\').disable();
                }
            });	   
	</script>';
        $page->output_header($lang->advinvsys_main_income_edittitle);
        
        // se afiseaza meniul orizontal special
        $page->output_nav_tabs($sub_tabs, 'main');
        
        // trecem la crearea formularului
        $form = new Form("index.php?module=config-advinvsys&amp;action=editincome", "post");
        echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        echo $form->generate_hidden_field("iid", $rule['iid']);
        $form_container = new FormContainer($lang->advinvsys_main_income_edittitle);
        $form_container->output_row($lang->advinvsys_main_income_name . "<em>*</em>", $lang->advinvsys_main_income_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($rule['name']), array('id' => 'name')), 'name');
        $form_container->output_row($lang->advinvsys_main_income_desc, $lang->advinvsys_main_income_desc_desc, $form->generate_text_area('description', htmlspecialchars_uni($rule['description']), array('id' => 'description')), 'description');
        $form_container->output_row($lang->advinvsys_main_income_type . "<em>*</em>", $lang->advinvsys_main_income_type_desc, $form->generate_select_box('type', $list, $rule['type'], array('id' => 'type')), 'type');
        $form_container->output_row($lang->advinvsys_main_income_rate . "<em>*</em>", $lang->advinvsys_main_income_rate_desc, $form->generate_text_box('invitations', $AISDevelop->truncNumber($rule['invitations']), array('id' => 'invitations')), 'invitations');
        $form_container->output_row($lang->advinvsys_main_income_addi, $lang->advinvsys_main_income_addi_desc, $form->generate_text_box('additional', $AISDevelop->truncNumber($rule['additional']), array('id' => 'additional')), 'additional', '', array('id' => 'additional_field'));
        $form_container->output_row($lang->advinvsys_main_income_forum . "<em>*</em>", $lang->advinvsys_main_income_forum_desc, $form->generate_forum_select('fids[]', explode(',', $rule['fid']), array('id' => 'fids', 'multiple' => true)), 'fids');
        $form_container->output_row($lang->advinvsys_main_income_group . "<em>*</em>", $lang->advinvsys_main_income_group_desc, $form->generate_group_select('gids[]', explode(',', $rule['gid']), array('id' => 'gids', 'multiple' => true)), 'gids');
        $form_container->output_row($lang->advinvsys_main_income_enable, $lang->advinvsys_main_income_enable_desc, $form->generate_yes_no_radio('enabled', intval($rule['enabled']), true), 'enabled');
        $form_container->end();
        
        // sunt afisate si butoanele
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
        // in fine se afiseaza si subsolul paginii
        $page->output_footer();
    } else if ($mybb->input['action'] == 'deleteincome') {
        if ($mybb->input['no']) {
            // userul nu a mai confirmat
            admin_redirect("index.php?module=config-advinvsys");
        }
        // se verifica cererea
        if ($mybb->request_method == "post") {
            // daca codul cererii nu e corect atunci se afiseaza o eroare pe ecran
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys");
            }
            
            $plugins->run_hooks('advinvsys_admin_rule_do_delete');
            
            // daca se ajunge aici inseamna ca se poate sterge
            $db->delete_query('advinvsys_incomes', 'iid = ' . intval($mybb->input['iid']));
            
            // se actualizeaza si cache-ul
            if (function_exists('advinvsys_update_cache'))
                advinvsys_update_cache();
            
            // actiunea este jurnalizata
            $AISDevelop->addLog($lang->advinvsys_main_income_deleted_logt, 
                $lang->sprintf($lang->advinvsys_main_income_deleted_log, intval($mybb->input['iid'])), 
                $mybb->user['uid']);
            
            // se afiseaza pe ecran un mesaj precum totul s-a realizat cu succes
            flash_message($lang->advinvsys_main_income_deleted, 'success');
            admin_redirect('index.php?module=config-advinvsys');
        } else {
            // pagina de confirmare
            // se afiseaza antetul paginii
            $page->output_header($lang->advinvsys_confirmation_page);
            
            // se converteste inputul la intreg
            $mybb->input['iid'] = intval($mybb->input['iid']);
            
            $form = new Form("index.php?module=config-advinvsys&amp;action=deleteincome&amp;iid={$mybb->input['iid']}&amp;my_post_key={$mybb->post_code}", 'post');
            echo "<div class=\"confirm_action\">\n";
            echo "<p>" . $lang->advinvsys_main_income_deleteconfirm . "</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->advinvsys_button_yes, array('class' => 'button_yes'));
            echo $form->generate_submit_button($lang->advinvsys_button_no, array("name" => "no", 'class' => 'button_no'));
            echo "</p>\n";
            echo "</div>\n";
            $form->end();
            
            // in fine se afiseaza si subsolul paginii
            $page->output_footer();
        }
    } else if ($mybb->input['action'] == 'edituser') {
        if (!intval($mybb->input['uid']) || !($user = get_user(intval($mybb->input['uid'])))) {
            flash_message($lang->advinvsys_main_invalid_edituser, 'error');
            admin_redirect("index.php?module=config-advinvsys");
        }
        if ($mybb->request_method == "post" && isset($mybb->input['do_change'])) {
            if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
                $mybb->request_method = "get";
                flash_message($lang->advinvsys_errors_invalidreq, 'error');
                admin_redirect("index.php?module=config-advinvsys");
            }
            
            // se face actualizarea
            $AISDevelop->addInvitations('uid', intval($mybb->input['uid']), 
                    floatval($mybb->input['points']), true, false);
    
            // se introduce un log in baza de date
            $AISDevelop->addLog($lang->advinvsys_main_reset, 
                $lang->sprintf($lang->advinvsys_main_edituser_log, intval($mybb->input['uid']), 
                    $AISDevelop->truncNumber($mybb->input['points'])), 
                intval($mybb->input['uid']));
            
            // se afiseaza un mesaj pe ecran
            flash_message($lang->advinvsys_main_user_edited, 'success');
            admin_redirect("index.php?module=config-advinvsys");
        }
        $page->output_header($lang->advinvsys_main);
        
        // se afiseaza numarul de invitatii ale membrului ales si totodata i se permite administratorului
        // sa editeze acest numar
        $form = new Form("index.php?module=config-advinvsys&amp;action=edituser", "post");
        echo $form->generate_hidden_field("uid", intval($mybb->input['uid']));
        echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        echo $form->generate_hidden_field("do_change", 1);
        $form_container = new FormContainer($lang->advinvsys_main_edituser);
        $form_container->output_row($lang->advinvsys_main_edituser_points, $lang->advinvsys_main_edituser_points_desc, $form->generate_text_box('points', $AISDevelop->truncNumber($user['invitations']), array('id' => 'points')), 'points');
        $form_container->end();
        
        // se afiseaza butoanele disponibile in cadrul formularului
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        
        $form->end();
    } else if ($mybb->input['action'] == 'reset') {
        $page->output_header($lang->advinvsys_main);
        // cate sunt pe pagina?
        $mybb->input['per_page'] = intval($mybb->input['per_page']);
        if (!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'] 
                || !$mybb->input['per_page']) {
            $mybb->request_method = "get";
            flash_message($lang->advinvsys_errors_invalidreq, 'error');
            admin_redirect("index.php?module=config-advinvsys");
        }
        
        $invs = floatval($mybb->input['points']);
        $start = 0;
        
        if (intval($mybb->input['start']) > 0)
            $start = intval($mybb->input['start']);
        $per_page = 500;
        
        if (intval($mybb->input['per_page']) > 0)
            $per_page = intval($mybb->input['per_page']);
        
        // se realizeaza o interogare
        $query = $db->simple_select('users', 'COUNT(*) as users');
        
        // se obtin numarul total de utilizatori
        $total_users = $db->fetch_field($query, 'users');
        
        // se realizeaza a doua interogare
        $query = $db->simple_select('users', 'uid', '', array('order_by' => 'uid', 
            'order_dir' => 'ASC', 'limit' => "{$start}, {$per_page}"));
        while ($user = $db->fetch_array($query)) {
            // se face resetarea propriu-zisa
            $db->update_query('users', array('invitations' => $AISDevelop->truncNumber($invs)), 
                    'uid = \'' . $user['uid'] . '\'');
        }
        // daca numarul total de utilizatori depaseste numarul introdus de admin
        if ($total_users > $start + intval($mybb->input['per_page'])) {
            $form = new Form("index.php?module=config-advinvsys&amp;action=reset&amp;my_post_key={$mybb->post_code}", "post");
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
            echo $form->generate_hidden_field("start", $start + intval($mybb->input['per_page']));
            echo $form->generate_hidden_field("per_page", intval($mybb->input['per_page']));
            echo $form->generate_hidden_field("points", floatval($mybb->input['points']));
            echo "<div class=\"confirm_action\">\n";
            echo "<p>{$lang->advinvsys_main_continue}</p>\n";
            echo "<br />\n";
            echo "<p class=\"buttons\">\n";
            echo $form->generate_submit_button($lang->advinvsys_button_continue, array('class' => 'button_yes'));
            echo "</p>\n";
            echo "</div>\n";
            $form->end();
            
            // de aici mai jos de va intrerupe executia
            $page->output_footer();
            exit();
        }
        
        // se adauga un log in baza de date
        $AISDevelop->addLog($lang->advinvsys_main_reset, 
            $lang->advinvsys_main_reset_done, 
            $mybb->user['uid']);
        
        // se afiseaza un mesaj pe ecran
        flash_message($lang->advinvsys_main_reset_done, 'success');
        admin_redirect('index.php?module=config-advinvsys');
    } else {
        $page->extra_header .= '
        <style type="text/css">
            a.advinvsys_add_income {
                display: block; height: 16px; width: 16px; text-indent: -999px; text-decoration: none; overflow: hidden; padding: 0px; margin: 0px;
                background: transparent url(../images/advinvsys/add.png);
            }
        </style>';
        $page->output_header($lang->advinvsys_main);
        
        // se afiseaza meniul orizontal
        $page->output_nav_tabs($sub_tabs, 'main');
        
        // se realizeaza paginarea in vederea afisarii tabelului
        $per_page = 10; // in mod implicit
        if ($mybb->input['page'] && intval($mybb->input['page']) > 1) {
            $mybb->input['page'] = intval($mybb->input['page']);
            $start = ($mybb->input['page'] * $per_page) - $per_page;
        } else {
            $mybb->input['page'] = 1;
            $start = 0;
        }
        
        // acum paginarea este in regula, se trece la obtinerea datelor din tabel
        $query = $db->simple_select('advinvsys_incomes', 'COUNT(iid) as incomes');
        // variabila ce retine numarul de randuri obtinute din interogare
        $total_rows = $db->fetch_field($query, 'incomes');
        
        // se afiseaza tabelul cu regulile 'income'
        $table = new Table;
        $table->construct_header($lang->advinvsys_main_income_name, array('width' => '20%'));
        $table->construct_header($lang->advinvsys_main_income_desc, array('width' => '30%'));
        $table->construct_header($lang->advinvsys_main_income_type, array('width' => '15%'));
        $table->construct_header($lang->advinvsys_main_income_rate, array('width' => '15%'));
        $table->construct_header($lang->advinvsys_main_income_enabled, array('width' => '10%'));
        $table->construct_header($lang->advinvsys_main_income_options, array('width' => '10%', 'class' => 'align_center'));
        $query = $db->simple_select('advinvsys_incomes', '*', '', array('order_by' => 'iid', 'limit' => "{$start}, {$per_page}"));
        while ($income = $db->fetch_array($query)) {
            $table->construct_cell(htmlspecialchars_uni($income['name']), array('class' => 'align_center'));
            // este prea lunga descrierea?
            if (strlen($income['description']) > 37)
                $income['description'] = substr($income['description'], 0, 36) . '...';
            $table->construct_cell(htmlspecialchars_uni($income['description']), array('class' => 'align_center'));
            $table->construct_cell($list[$income['type']], array('class' => 'align_center'));
            $table->construct_cell($AISDevelop->truncNumber($income['invitations']) . $lang->advinvsys_invs, array('class' => 'align_center'));
            if (intval($income['enabled']) == 1) {
                $enabled = $lang->advinvsys_button_yes;
            } else {
                $enabled = $lang->advinvsys_button_no;
            }
            $table->construct_cell($enabled, array('class' => 'align_center'));
            $popup = new PopupMenu("incomes_{$income['iid']}", $lang->advinvsys_main_income_options);
            $popup->add_item($lang->advinvsys_edit, "index.php?module=config-advinvsys&amp;action=editincome&amp;iid=" . intval($income['iid']));
            $popup->add_item($lang->advinvsys_delete, "index.php?module=config-advinvsys&amp;action=deleteincome&amp;iid=" . intval($income['iid']));
            $table->construct_cell($popup->fetch(), array('class' => 'align_center'));
            $table->construct_row();
        }
        
        // daca nu sunt randuri atunci se afiseaza un mesaj
        if ($table->num_rows() == 0) {
            $table->construct_cell($lang->advinvsys_main_income_none, array('colspan' => 6, 'class' => 'align_center'));
            $table->construct_row();
        }
        
        $table->output($lang->advinvsys_main_income_title . "<div style='float: right;'><a href='index.php?module=config-advinvsys&amp;action=addincome' class='advinvsys_add_income' id='advinvsys_add_income'>&nbsp;</a></div>");
      
        // se realizeaza paginarea
        echo draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=config-advinvsys&amp;page={page}");
        
        // se afiseaza formularul prin care se poate reseta numarul de invitatii pentru fiecare membru in parte
        $form = new Form("index.php?module=config-advinvsys&amp;action=reset", "post");
        // se genereaza si un postkey pentru verificarea autenticitatii cererii	
        //echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        $form_container = new FormContainer($lang->advinvsys_main_reset);
        $form_container->output_row($lang->advinvsys_main_reset_per_page, $lang->advinvsys_main_reset_per_page_desc, $form->generate_text_box('per_page', 500, array('id' => 'per_page')), 'per_page');
        $form_container->output_row($lang->advinvsys_main_reset_invs, $lang->advinvsys_main_reset_invs_desc, $form->generate_text_box('points', 0, array('id' => 'points')), 'points');
        $form_container->end();
        
        // se afiseaza butoanele atasate formularului
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        
        $form->end();
        
        // se lasa spatiu o linie intre formulare
        echo "<br />";
        
        // se afiseaza formularul prin care se poate edita numarul de invitatii al unui utilizator
        $form = new Form("index.php?module=config-advinvsys&amp;action=edituser", "post");
        // se genereaza si un postkey pentru verificarea autenticitatii cererii
        echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
        $form_container = new FormContainer($lang->advinvsys_main_edituser);
        $form_container->output_row($lang->advinvsys_main_edituser_uid, $lang->advinvsys_main_edituser_uid_desc, $form->generate_text_box('uid', 0, array('id' => 'uid')), 'uid');
        $form_container->end();
        
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->advinvsys_button_submit);
        $buttons[] = $form->generate_reset_button($lang->advinvsys_button_reset);
        $form->output_submit_wrapper($buttons);
        
        $form->end();
    }
    // se afiseaza subsolul paginii
    $page->output_footer();
}

// Functia care actualizeaza cache-ul de reguli
function advinvsys_update_cache() {
    global $db, $mybb;

    $array = array();
    
    // se executa o interogare
    $query = $db->simple_select('advinvsys_incomes', 
            'iid,fid,gid,type,invitations,additional', 'enabled = \'1\'');
    
    while ($row = $db->fetch_array($query))
        $array[$row['iid']] = $row;

    // se face actualizarea
    $mybb->cache->update('advinvsys_rules', $array);
}

// Functia care construieste conditiile de stergere a log-urilor
function advinvsys_build_condition_array() {
    global $mybb;
    
    $array = array();
    
    $field_count = count($mybb->input['field']);
    
    for ($i = 0; $i < $field_count; $i++) {
        if (!empty($mybb->input['field'][$i])) {
            $array[] = array(
                'field' => $mybb->input['field'][$i],
                'test' => $mybb->input['test'][$i],
                'value' => $mybb->input['value'][$i]
            );
        }
    }
    return $array;
}

// Functia care intoarce campurile posibile la stergerea unor log-uri
function advinvsys_logs_get_fields() {
    global $lang;
    $fields = array(
        'uid' => array(
            'title' => $lang->advinvsys_logs_user,
            'type' => 'int'
        ),
        'type' => array(
            'title' => $lang->advinvsys_logs_type,
            'type' => 'string'
        ),
        'data' => array(
            'title' => $lang->advinvsys_logs_mess,
            'type' => 'string'
        ),
        'date' => array(
            'title' => $lang->advinvsys_logs_date,
            'type' => 'date'
        )
    );
    // se intorc rezultatele functiei	
    return $fields;
}

// Functia care intoarce testele posibile la stergerea mai multor informatii
function advinvsys_get_tests() {
    return array(
        '' => "",
        'eq' => "Equal",
        'neq' => "Not Equal",
        'null' => "Null",
        'notnull' => "Not Null",
        'empty' => "Empty",
        'notempty' => "Not Empty",
        'gt' => ">",
        'lt' => "<",
        'gte' => "=>",
        'lte' => "<=",
        'in' => "In",
        'nin' => "Not In",
        'like' => "Like",
        'nlike' => "Not Like"
    );
}

// Functia care intoarce toate modulele existente in directorul special al modificarii
function advinvsys_get_modules() {
    
    // vectorul cu module
    $modules_list = array();
    
    // se deschide directorul cu module
    $dir = @opendir(MYBB_ROOT . 'inc/plugins/advinvsys/plugins/');
    
    // se cauta in director
    if ($dir) {
        
        // pentru fiecare fisier din director
        while ($file = readdir($dir)) {
            
            // este fisier?
            if ($file == '.' || $file == '..')
                continue;
            
            // daca nu este director
            if (!is_dir(MYBB_ROOT . 'inc/plugins/advinvsys/plugins/' . $file)) {
                
                // se intoarce extensia fisierului gasit
                $ext = get_extension($file);
                
                // daca extensia fisierul este php atunci se adauga modulul in lista
                if ($ext == 'php') {
                    $modules_list[] = $file;
                }
            }
        }
        
        // are loc sortarea alfabetica a vectorului cu module
        @sort($plugins_list);
        
        // se inchide directorul de lucru
        @closedir($dir);
    }
    
    // se intoarce vectorul cu module
    return $modules_list;
}

// Functia care intoarce posibile upgrade-uri pentru aceasta modificare
function advinvsys_get_upgrades() {
    $upgrades_list = array();

    // ce director se va folosi?
    $dir = @opendir(MYBB_ROOT . 'inc/plugins/advinvsys/upgrades/');

    // se incepe procesul de citire a fisierelor
    if ($dir) {
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..')
                continue;
            
            if (!is_dir(MYBB_ROOT . 'inc/plugins/advinvsys/upgrades/' . $file)) {
                $ext = get_extension($file);
                if ($ext == 'php') {
                    // daca are un format corespunzator
                    if (preg_match('/upgrade_([0-9]{3})_([0-9]{3})\.php/i', $file, $matches) &&
                            version_compare($matches[1], str_replace('.', '', AIS_VERSION), '==')) {
                        $upgrades_list[] = array(
                            'file' => $file,
                            'from' => $matches[1],
                            'to' => $matches[2]
                        );
                    }
                }
            }
        }
        
        // se va sorta crescator dupa campul "to"
        $lambda = create_function('$a,$b', 
                'return (intval($a[\'to\']) - intval($b[\'to\']));');
        usort($upgrades_list, $lambda);
        
        // se inchide directorul de lucru
        @closedir($dir);
    }
    // se intoarce vectorul de actualizari
    return $upgrades_list;
}

// Functia care verifica compatibilitatea unui modul
function advinvsys_is_compatible($moduleinfo) {
    // este compatibil modulul cu versiunea curenta a modificarii?
    if (!$moduleinfo['compatibility'] || $moduleinfo['compatibility'] == "*") {
        return true;
    }
    
    $compatibility = explode(",", $moduleinfo['compatibility']);
    
    foreach ($compatibility as $version) {
        $version = trim($version);
        $version = str_replace("*", ".+", preg_quote($version));
        $version = str_replace("\.+", ".+", $version);
        if (preg_match("#{$version}#i", AIS_VERSION)) {
            return true;
        }
    }
    
    // daca se ajunge aici inseamna ca modulul nu e compatibil cu modificarea
    return false;
}

// Functia escapeaza codul HTML pentru a putea fi salvat intr-un fisier XML
function xmlentities($string) {
    return str_replace(array("<", ">", "\"", "'", "&"), 
            array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), $string);
}

// Functia care realizeaza procesul de import al unui modul dintr-un fisier XML
function advinvsys_make_module($xml) {
    global $mybb, $db;
    
    // se include clasa de prelucrare a unui fisier XML
    require_once MYBB_ROOT . "inc/class_xml.php";
    
    // se parseaza fisierul
    $parser = new XMLParser($xml);
    
    // se obtine arborele din fisierul XML
    $tree = $parser->get_tree();
    
    // daca arborele nu este vector atunci se returneaza "fals"
    if (!is_array($tree) || !is_array($tree['module'])) {
        return false;
    }
    
    // se obtine vectorul cu datele noului modul
    $module = $tree['module'];
    
    // se obtin atributele modulului
    $filename = $module['attributes']['name'];
    
    // se deschide fisierul in care se vor scrie informatiile
    if (!($file = fopen(MYBB_ROOT . "inc/plugins/advinvsys/plugins/" . basename($filename) . ".php", 'w'))) {
        return false;
    } else {
        $part_1 = "<?php\n/*\nThis file was generated with \"Advanced Invitations System\" modification.\n*/\n//BEGIN - SECURITY\nif(!defined(\"IN_MYBB\")) {\n\tdie(\"Direct initialization of this file is not allowed.\");\n}\n//END - SECURITY\n";
 
        // se scrie prima portiune de cod
        fwrite($file, $part_1);
        // urmeaza scrierea unor conditii special
        $part_2 = "//BEGIN - CONDITIONS\n";
        // se obtin functiile din cadrul fisierului XML
        if (!empty($module['conditions']['condition'])) {
            $conditions = $module['conditions']['condition'];
            if (is_array($conditions)) {
                // modulul are o singura functie?
                if (array_key_exists("attributes", $conditions)) {
                    $conditions = array($conditions);
                }
            }
            foreach ($conditions as $condition) {
                $part_2 .= "if(" . html_entity_decode($condition['attributes']['test']) . ")\n{" . $condition['value'] . "}\n";
            }
            $part_2 .= "//END - CONDITIONS\n";
        }
        
        // se scrie a doua portiune de cod
        fwrite($file, $part_2);
        // urmeaza scrierea datelor prinvind carligele modulului
        $part_3 = "//BEGIN - HOOKS\n";
        // se obtin functiile din cadrul fisierului XML
        if (!empty($module['hooks']['hook'])) {
            $hooks = $module['hooks']['hook'];
            if (is_array($hooks)) {
                // modulul are o singura functie?
                if (array_key_exists("attributes", $hooks)) {
                    $hooks = array($hooks);
                }
            }
            foreach ($hooks as $hook) {
                $part_3 .= "\$plugins->add_hook(\"" . $hook['attributes']['name'] . "\", \"" . $hook['value'] . "\");\n";
            }
            $part_3 .= "//END - HOOKS\n";
        }
        
        // se scrie a treia portiune de cod
        fwrite($file, $part_3);
        // in fine urmeaza a treia portiune de cod
        $part_4 = "//BEGIN - FUNCTIONS\n";
        // se obtin functiile din cadrul fisierului XML
        if (!empty($module['functions']['function'])) {
            $functions = $module['functions']['function'];
            if (is_array($functions)) {
                // modulul are o singura functie?
                if (array_key_exists("attributes", $functions)) {
                    $functions = array($functions);
                }
            }
            foreach ($functions as $function) {
                $part_4 .= "function ais_" . $filename . "_" . $function['attributes']['name'] . "(" . $function['attributes']['params'] . ")\n{\n" . $function['value'] . "\n}\n";
            }
            $part_4 .= "//END - FUNCTIONS\n?>";
        }
        
        // se scrie ultima portiune de cod
        fwrite($file, $part_4);
    }
    
    // se inchide fisierul de lucru
    fclose($file);
    // totul a decurs bine
    return true;
}

// Functia realizeaza procesul de exportare a unei modificari
function advinvsys_export_module($content) {
    
    // se incepe crearea fisierului XML
    $xml = "<?xml version=\"1.0\" encoding=\"{$lang->settings['charset']}\"?" . ">\r\n";
    $xml .= "<module author=\"" . $info['author'] . "\" name=\"" . $codename . "\" version=\"" . str_replace('.', '', $info['version']) . "\">\r\n";
    $xml .= "\t<conditions>\r\n";
    
    preg_match_all('/if[\s]*\((.*?)\)[\s]*{(.*?)}/s', $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        if (strcmp($match[1], "!defined(\"IN_MYBB\")") == 0)
            continue;
        $xml .= "\t\t<condition test=\"" . xmlentities($match[1]) . "\"><![CDATA[" . $match[2] . "]]></condition>\r\n";
    }
    $xml .= "\t</conditions>\r\n";
    
    $xml .= "\t<hooks>\r\n";
    preg_match_all('/\$plugins->add_hook\(["|\'](.*?)["|\'][\s]*,[\s]*["|\'](.*?)["|\']\);/s', $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $xml .= "\t\t<hook name=\"" . xmlentities($match[1]) . "\"><![CDATA[" . $match[2] . "]]></hook>\r\n";
    }
    $xml .= "\t</hooks>\r\n";
    
    $xml .= "\t<functions>\r\n";
    preg_match_all('/function\s*ais_helloworld_(.*?)\s*\((.*?)\)\s*{(.*?)}\s*(?=(function)|(\?\>))/s', $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $xml .= "\t\t<function name=\"" . $match[1] . "\" params=\"" . xmlentities($match[2]) . "\"><![CDATA[" . $match[3] . "]]></function>\r\n";
    }
    $xml .= "\t</functions>\r\n";
    
    $xml .= "</module>";
    
    // este returnat rezultatul
    return $xml;
}

?>