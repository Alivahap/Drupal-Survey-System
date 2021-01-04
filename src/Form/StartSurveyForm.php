<?php

namespace Drupal\surveysystem\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
class StartSurveyForm extends FormBase {
  public function getFormId() {
    return 'AnketDuzenle_form4';
  }
  // Isopen metotu anketin açık olup olmadığı bilgisini döndürür. 
  public function IsOpen($survey_time){
    $survery_time = strtotime(date("Y-m-d")) -($survey_time);
    $result="Açık";
    if ( $survery_time > 0 ) {
      $result="Kapalı";
    }
    return $result;
  }
   //Anket bilgilerini dönderen metot.
  //Pager değişkeni tabloyu sayfa sayfa göstermek için kullandığım değişkendir. 
  public function retrieveSurvey() {
    $query = \Drupal::database()->select('survey', 'u');
    $query->fields('u', ['survey_id','survey_name','survey_time']);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(5);
    $results = $pager->execute()->fetchAll();
    foreach ($results as $result) {
        $output[$result->survey_id] = [
          'id' => $result->survey_id,     
          'survey_name' => $result->survey_name,
          'survey_time' => date("d-M-Y",(string)$result->survey_time),
          'issurvey_open' =>$this->IsOpen($result->survey_time),
         
        ];
    }
    return $output;
  }
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    //anket bilgilerini diziye attım.
    $output=$this->retrieveSurvey();
    $form['status_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t(''),
      '#collapsible' => TRUE,
    ];
    $header = [
      'id' => t('Sıralama'),
      'survey_name' => t('Anket'),
      'survey_time' => t('Bitiş Zamanı'),
      'issurvey_open' =>t('Anket Açık/Kapalı'),
    ];
   $form['status_fieldset']['table'] = [
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $output,
    '#default_value' =>'',
    '#multiple' => FALSE, 
    '#empty' => t('Anket bulunamadı'),
    ];

    $form['pager'] = array(
      '#type' => 'pager'
    );
   
     $form['Uptade'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cevaplamaya Başla'),
      '#submit' => ['::StartAnswering'],
    ];
      return $form;
  }
  //Anket cevaplamak için SurveyForm sayfasına gönderir
  public function StartAnswering(array &$form, FormStateInterface $form_state) {
    $selected=$form_state->getvalue('table');
    drupal_set_message($selected);
    $url = \Drupal\Core\Url::fromRoute('surveysystem.survey')
    ->setRouteParameters(array('update'=>base64_encode($selected)));
     $form_state->setRedirectUrl($url);
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
   
  }
  //boş geçilmemesini önler.
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected=$form_state->getValue('table');
    if( $selected==0){
      $form_state->setErrorByName('table', $this->t('Anket seçmeyi unutunuz. '));
    }
  }
}
