<?php

 namespace Drupal\surveysystem\Controller;
 
 use Drupal\Core\Controller\ControllerBase;
 
 class FirstController extends ControllerBase {
   public function content() {
     return array(
       '#type' => 'markup',
       '#markup' => t('deneme amaçlı'),
       );
   }

 }
 