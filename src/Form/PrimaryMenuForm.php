<?php

namespace Drupal\surveysystem\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
//Anket forumlarına ulaşmak için PrimaryMenu bloku
class PrimaryMenuForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_PrimaryMenuForm_survery_form8';
  }
  /**
   * {@inheritdoc}
   * 
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Anket ile ilgili sayfalara buradan erişebilirsiniz.'),
    ];
    $form['actions']['extra_actions'] = [
      '#type' => 'dropbutton',
      '#links' => [
        'simple_form' => [
          'title' => $this->t('Anket İşlemleri'),
        ],
        'Startsurvey' => [
          'title' => $this->t('Anketler'),
          'url' => Url::fromRoute('surveysystem.start'),
        ],
        'AddSurvey' => [
          'title' => $this->t('Anket Ekleme'),
          'url' => Url::fromRoute('surveysystem.add'),
        ],
        'EditSurvey' => [
          'title' => $this->t('Özel Anketleri Düzenleme'),
          'url' => Url::fromRoute('surveysystem.edit'),
        ],
      ],
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  
  }
}
