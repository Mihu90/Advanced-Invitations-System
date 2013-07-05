<?php
/*
 * This is a simple test.
 */
if(!defined("IN_MYBB")) {
    	die("This file cannot be accessed directly.");
}
if(!defined("AIS_VERSION")) {
    	die("This file cannot be accessed directly.");
}

// Now we have to add some hooks
// "advinvsys_admin_menu" : helps you to add a new item in Admin
// CP menu of "Advanced Invitations System"
$plugins->add_hook("advinvsys_admin_menu", "ais_helloworld_menu");

/**
 * <b>ROMANIAN :</b> Aceasta functie returneaza o serie de informatii
 * legate de autorul si versiunea acestei modificari. Vectorul
 * returnat trebuie sa respecte formatul descris in acest exemplu
 * , altfel pot aparea erori.
 * @return Array with some information about mod
 */
function ais_helloworld_info()
{
	return array(
		"name"		   => "Hello World",
		"description"  => "\"Hello World\" it is the first plugin maked in order to learn how to do other mods for \"Advanced Invitations System\".",
		"website"	   => "http://mybb.ro",
		"author"       => "MyBB Rom&#226;nia Team",
		"authorsite"   => "http://mybb.ro",
		"version"	   => "1.0",
		"compatibility"=> "10*"
	);
}

/**
 * <b>ROMANIAN :</b> Aceasta functie este apelata in momentul in care
 * incercati sa activati aceasta modificare din sectiunea "Module"
 * din panoul de administrare. In cadrul acestei functii pot fi
 * realizate teste de compatibilitate, introduceri de setari cat
 * si creari de sabloane sau tabele pentru lucrul cu baze de date.
 */
function ais_helloworld_activate()
{
    // Here you can add some tables or some settings  
}

/**
 * <b>ROMANIAN :</b> Aceasta functie este apelata in momentul in care
 * incercati sa dezactivati modificarea din panoul de administrare.
 */
function ais_helloworld_deactivate()
{
    // Here you can remove some tables or some settings 
}

/**
 * <b>ROMANIAN :</b> Functia permite introducerea unui nou item in meniul
 * sistemului din panoul administrare. Asadar functia va permite sa
 * adaugati propria dvs. pagina in sectiunea de administrare a modi
 * ficarii.
 */
function ais_helloworld_menu(&$sub_menu)
{
    $sub_menu['helloworld'] = array(
            "title"         => "Hello World",
            "link"          => "index.php?module=config-advinvsys&amp;section=plugins-helloworld",
            "description"   => "This page it is only for test."
    );   
}

/**
 * <b>ROMANIAN :</b> Functia permite va fi apelata in momentul in care
 * administratorul doreste sa intre pe pagina adaugata de modifi
 * care din Admin CP. Tot ce se afiseaza in aceasta functie se va
 * afisa si pe pagina de administrare. Ar mai fi de mentionat faptul
 * ca aceasta metoda se ala in stransa legatura cu cea de adaugare
 * a unei legaturi (item) in meniul de administrare.
 */
function ais_helloworld_admin()
{
    global $lang, $page, $sub_tabs;
    
    $page->add_breadcrumb_item($lang->advinvsys_modname);
    $page->add_breadcrumb_item("Hello World!");
    $page->output_header("Hello World!");
    
    $page->output_nav_tabs($sub_tabs, 'helloworld');
    
    echo '<center>Hello World!</center>';

    $page->output_footer();    
}
?>