<?php
namespace Drupal\surveysystem\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
class EditQuestionForm extends FormBase {
  public function getFormId(){
    return 'edit_question_form2';
  }
// seçili anketin sorularını ve oluşturlma tarihini dönderen metotdur.
public function retrieveSurvey() {
    $query = \Drupal::database()->select('question', 'u');
    $query->fields('u', ['id', 'question','created']);
    $query->condition('survey_id',\base64_decode($_GET['edit']));
   //5 sorudan sonra tabloyu 2.sayfaya atan metot. 
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(5);
    $results = $pager->execute()->fetchAll();
    foreach ($results as $result) {
        $output[$result->id] = [
          'id' => $result->id,     
          'question_name' => $result->question,
          'question_time' => date("d-M-Y",(string)$result->created),
        ];
    }
    return $output;
}

public function buildForm(array $form, FormStateInterface $form_state) {
//anket bilgilerini diziye attım.
$output=$this->retrieveSurvey();
$form['status_fieldset'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Soru Düzenleme Formu'),
  '#collapsible' => TRUE,
];
$header = [
  'id' => t('Sıralama'),
  'question_name' => t('Sorular'),
  'question_time' => t('Oluşturulma Tarihi'),
];
$form['status_fieldset']['table'] = [
'#type' => 'tableselect',
'#header' => $header,
'#options' => $output,
'#default_value' =>'',
'#multiple' => FALSE, 
'#empty' => t('Soru bulunamadı'),
];
$form['pager'] = array(
  '#type' => 'pager'
);
$form['show'] = [
  '#type' => 'submit',
  '#value' => $this->t('Cevapları Düzenle '),
  '#submit' => ['::showAnswer'],
];
 $form['back'] = [
  '#type' => 'submit',
  '#value' => $this->t('Geri Dön'),
  '#submit' => ['::PageTwoBack'],
  '#limit_validation_errors' => [],
];
$form['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => $this->t('Sil'),
  '#description' => $this->t(''),
 ];
  return $form;
}
//seçili tablodaki question id'sini EditAnswerForm'a gönderir.
public function showAnswer(array &$form, FormStateInterface $form_state){
    $selected=$form_state->getvalue('table');
      $url = \Drupal\Core\Url::fromRoute('surveysystem.editanswer')
    ->setRouteParameters(array('update'=>base64_encode($selected) ));
     $form_state->setRedirectUrl($url);
}
//seçili soruyu siler.
public function submitForm(array &$form, FormStateInterface $form_state) {
  $selected=  $form_state->getValue('table');
  db_delete('question')
  ->condition('id', (int)$selected)
  ->execute();
  drupal_set_message($selected);
}
//soru seçimini kontrol eder.
public function validateForm(array &$form, FormStateInterface $form_state) {
  $selected=$form_state->getValue('table');
  if( $selected==0){
    $form_state->setErrorByName('table', $this->t('Soru seçmeyi unutunuz. '));
  }
}
//anket sayfasına geri döndürür.
public function PageTwoBack(array &$form, FormStateInterface $form_state) {
  $url = \Drupal\Core\Url::fromRoute('surveysystem.edit');
   $form_state->setRedirectUrl($url);
}

}



