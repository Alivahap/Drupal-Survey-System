<?php
/**
* @file
* Contains \Drupal\\rsvplist\Form\RSVPform .
*/

namespace Drupal\surveysystem\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;



use Drupal\Component\Utility\Html;
use Drupal\Core\Queue\QueueGarbageCollectionInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Database\Connection;

use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
//Anket düzenleme modülü,sadece özel olarak işaretlenmiş 
//anketler gözükür
class EditSurveyForm extends FormBase {
  public function getFormId(){
    return 'survey_form_to7';
  }
//Anketin açık mı kapalımı onun bilgisini döndürür.  
public function IsOpen($survey_time){
  $survery_time = strtotime(date("Y-m-d")) -($survey_time);
  $result="Açık";
  if ( $survery_time > 0 ) {
    $result="Kapalı";
  }
return $result;
}
//özel olarak işaretlenmiş anketleri döndürür.
public function retrieveSurvey() {
    $query = \Drupal::database()->select('survey', 'u');
    $query->fields('u', ['survey_id','survey_name','survey_time']);
    $query->condition('checked',1);
    //5 sorudan sonra sayfalama işlemini yapar. 
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

public function buildForm(array $form, FormStateInterface $form_state) {
//anket bilgilerini diziye attım.
$output=$this->retrieveSurvey();
$form['status_fieldset'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Anket Düzenleme'),
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
$form['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => $this->t('Sil'),
  '#description' => $this->t(''),
 ];
 $form['show'] = [
  '#type' => 'submit',
  '#value' => $this->t('Soruları gör'),
  '#submit' => ['::showQuestion'],
];
  return $form;
}
//soruları ayarlamak için EditQuestionForm sayfasına yönlendirdim.
public function showQuestion(array &$form, FormStateInterface $form_state) {
  $selected=  $form_state->getValue('table');
  $url = \Drupal\Core\Url::fromRoute('surveysystem.editquestion')
  ->setRouteParameters(array('edit'=>base64_encode($selected)));
   $form_state->setRedirectUrl($url);
}
//Seçili anketi siler.
public function submitForm(array &$form, FormStateInterface $form_state) {
  $selected=  $form_state->getValue('table');
  db_delete('survey')
  ->condition('survey_id', (int)$selected)
  ->execute();
  drupal_set_message($selected);
}
//anket seçimini kontrol eder.
public function validateForm(array &$form, FormStateInterface $form_state) {
  $selected=$form_state->getValue('table');
  if( $selected==0){
    $form_state->setErrorByName('table', $this->t('Anket seçmeyi unutunuz. '));
  }
}

}



