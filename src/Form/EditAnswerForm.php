<?php

namespace Drupal\surveysystem\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
//özel olarak işaretlenmiş anket cevaplarını düzenleyen modüldür.
class EditAnswerForm extends FormBase {
  public function getFormId(){
    return 'edit_answer_form2';
  }
 //seçili anketin cevaplarını idsi ile birlikte dizi olarak döndürür.
  public function getAnswers(){
    $query = db_select('answers','t1');
    $query->fields('t1', ['answer_id','answer_name']);
    $query->condition('question_id',(int)base64_decode($_GET['update']), '=');
    $results = $query->execute()->fetchAll();
    $sayac=0;
    foreach ($results as $result) {
        $output[$sayac]=[
          'answer_id'=>$result->answer_id,
          'answer_name'=>$result->answer_name,
        ];
        $sayac++;
    }
    return $output;
  }
public function buildForm(array $form, FormStateInterface $form_state) {
 //Dizinin sayısı ve değerlerini aldım.
  $answers= $this->getAnswers();
  $num_answers =count($answers);
  //Cevapları sorudan ayıracak alan, 
  $form['answers_fieldset'] = [
    '#type' => 'fieldset',
    '#title' => $this->t(''),
    '#prefix' => '<div id="answers-fieldset-wrapper">',
    '#suffix' => '</div>',
  ];
  //sayfadaki toplam cevap sayısına göre form ekranına bastırırız.
  for ($i = 0; $i < $num_answers; $i++) {
    $form['answers_fieldset']['name'.$i] = [
      '#type' => 'textfield',
      '#default_value' => $answers[$i]['answer_name'],
      '#title' => $this->t( ($i+1) ."".'. Cevap '),
      '#required' => TRUE,
    ];
  }
  //anket düzenleme sayfasına geri döndürür.
  $form['back'] = [
    '#type' => 'submit',
    '#value' => $this->t('Geri Dön'),
    '#submit' => ['::PageTwoBack'],
    '#limit_validation_errors' => [],
  ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Düzenle'),
    ];
  return $form;
}
public function PageTwoBack(array &$form, FormStateInterface $form_state) {
    $url = \Drupal\Core\Url::fromRoute('surveysystem.edit');
    $form_state->setRedirectUrl($url);
}
//cevapları günceleyen metot.
public function submitForm(array &$form, FormStateInterface $form_state) {
    $answers= $this->getAnswers();
    $num_answers =count($answers);  
    for($i=0;$i<$num_answers ;$i++ ){
        $query = db_update('answers')
        ->fields(array(
        'answer_name' => $form_state->getValue('name'.$i),
        'question_id' => base64_decode( $_GET['update']),))
        ->condition('answer_id',$answers[$i]['answer_id'] , '=')
        ->execute();
    }
}

}



