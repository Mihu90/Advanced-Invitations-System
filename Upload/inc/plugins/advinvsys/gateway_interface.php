<?php

/*
  @author     : Surdeanu Mihai ;
  @date       : 19 februarie 2012 ;
  @version    : 1.0.0 ;
  @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
  @description: Modificare permite introducerea unui sistem de invitatii pe forumul tau!
  @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
  @copyright  : Licenta speciala. Pentru mai multe detalii te rugam sa citesti sectiunea Licenta din cadrul fisierului
  ReadME.pdf care poate fi gasit in acest pachet. Iti multumim pentru intelegere!
  ====================================
  Ultima modificare a codului : 19.02.2011 19:20
 */

interface interfaceGateway {

    public function check($array, $db, $mybb, $lang, $plugins, $AISDevelop);
}

?>
