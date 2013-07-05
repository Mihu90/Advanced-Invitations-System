<?php

/**
 * Functia prin care se face actualizarea versiunii 1.1.0 la 1.1.1
 * fara a fi nevoie de a reinstala aplicatia
 * */
function upgrade_110_111_run() 
{
    global $db, $AISDevelop;

    try {
        // SE ADAUGA NOILE SETARI
        $settings = array();
        $settings[] = array(
            "sid" => "NULL",
            "name" => "setting_newpointsenable",
            "title" => "[NewPoints] Is this gateway enabled? :",
            "description" => "Can a user buy invitations using <b>NewPoints</b> plugin? (Default : No)",
            "optionscode" => "yesno",
            "value" => "0"
        );
        $AISDevelop->addSettings('', $settings, -1, -1);

        // SE SCHIMBA VERSIUNEA ACESTEI MODIFICARI
        $AISDevelop->versionChange('1.1.1', '5.2.0');

        // totul s-a realizat cu succes
        return true;
    } catch (Exception $e) {
        return false;
    }
}

?>
