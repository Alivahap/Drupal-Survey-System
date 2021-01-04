<?php
/**
 * @file
 * Contains \Drupal\rsvplist\Plugin\Block\RSVPBlock
 */
 namespace Drupal\surveysystem\Plugin\Block;
 
 use Drupal\Core\Block\BlockBase;
 use Drupal\Core\Session\AccountInterface;
 use Drupal\Core\Access\AccessResult;

class SurveyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
   
    return \Drupal::formBuilder()->getForm('Drupal\surveysystem\Form\PrimaryMenuForm');
  }

  
}

