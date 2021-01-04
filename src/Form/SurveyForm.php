<?php
namespace Drupal\surveysystem\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/*
*Farklı metotlarla yapmak için iki adımlı bir şekilde düzenledim.
* Normalde tüm anket özeliklerini farklı sayfalarda yapabilirdim 
*fakat farklı bir şey yapmak istedim. 
*
*Anket,yorum,anket sonucunun gösterildiği sayfadır.
*/
class SurveyForm extends FormBase {
  private $Questions;
  public function getQuestion(){
    return $this->Questions;
  }
  public function setQuestion($value ){
    $this->Questions=$value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anket_cevaplama5';
  }

  /**
   * {@inheritdoc}
   */
  //Anket ve soru tablolarını birleştirerek soruları veri tabanından çektim. 
  public function getSurveyValues($idforquestion ){
      $query = db_select('question','t1');
      $query->join('survey','t2','t1.survey_id=t2.survey_id');
      $query->fields('t1', ['id','question']);
      $query->condition('t1.survey_id', $idforquestion, '=');
      $results = $query->execute()->fetchAll();
      $sayac=0;
      foreach ($results as $result) {
        if ($result->id != 0 ) {
         $output[$sayac] = [
            'id' => $result->id,
            'question_name' => $result->question, 
                
          ];
          $sayac++;  
        }
      }
    return  $output;
  }
  //cevaplar tablosundan çevapları çektim.
  public function optionsget($question_id){
    $query = db_select('answers','t1');
    $query->fields('t1', ['answer_id','answer_name']);
    $query->condition('question_id', $question_id, '=');
    $results = $query->execute()->fetchAll();
      $sayac=0;
      foreach ($results as $result) {
        if ($result->answer_id != 0 ) {
          $output[$result->answer_id]=$result->answer_name;
          $sayac++;
        }
      }   
    return  $output;
  }
  //oyu iptal etme metodu.
  public function CancelVote(array $form, FormStateInterface $form_state){
    //kullanıcı id'si ve anket idsine göre sonuçlar silinir.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $num_deleted = db_delete('results')
    ->condition('survey_id', (int) base64_decode($_GET['update']))
    ->condition('user_id', (int)$user->id())
    ->execute();
      $url = \Drupal\Core\Url::fromRoute('surveysystem.start')
           ->setRouteParameters(array('update'=>$_GET['update'] ));
     $form_state->setRedirectUrl($url);
  }
  //Yorum yapma metotu
  public function CommentSubmit(array $form, FormStateInterface $form_state){
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    db_insert('comments')
    ->fields(array(
      'user_id' =>$user->id() ,
      'comment_content' => $form_state->getValue('usercontent'),
      'comment_aliance' => $form_state->getValue('useraliance'),
      'survey_id' => base64_decode($_GET['update']),
     ))->execute();
    }
  //Yapılan yorumları çekme metodu.
    public function getComments(){
      $query = db_select('comments','t1');
      $query->fields('t1', ['comment_aliance','comment_content']);
      $query->condition('survey_id',(int)base64_decode($_GET['update']), '=');
      $results = $query->execute()->fetchAll();
        $sayac=0;
        foreach ($results as $result) {
            $output[$sayac] = [
              'comment_aliance' => $result->comment_aliance,
              'comment_content' => $result->comment_content,   
            ];
            $sayac++;
        }
      return  $output;
    }
    //formdan gönderilen soru idsine göre cevaplar formda gösterilir.
    public function getAnswerInfo($questionid){
      $query = db_select('answers','t1');
      $query->fields('t1', ['answer_id','answer_name']);
      $query->condition('question_id',$questionid, '=');
      $results = $query->execute()->fetchAll();
      $sayac=0;
      foreach ($results as $result) {
          $output[$sayac]=[
            'answer_id'=>$result->answer_id,
            'answer_name'=>$result->answer_name,
          ];
          $sayac++;
      }
      return  $output;
    }
    //anket id'sine göre soruların isimlerini dönderir.
    public function getQuestionInfo(){
      $query = db_select('question','t1');
      $query->fields('t1', ['id','question']);
      $query->condition('survey_id', (int)base64_decode($_GET['update']), '=');
      $results = $query->execute()->fetchAll();
      $sayac=0;
      foreach ($results as $result) {
          $output[$sayac]=[
            'id'=>$result->id,
            'name'=>$result->question
          ];
          $sayac++;
      }
      return  $output;
    }
  //answer tablosundaki id numarsına göre toplma cevap sayısını döndürür.
    public function getResultInfo($answer_id){
   $result = db_query('SELECT COUNT(answer_id) FROM results WHERE answer_id=:nid', array(':nid' => (int) $answer_id) )->fetchField();
   return  $result; 
    }
  //Oy seçimi yapıldıktan sonra Anket Sonuçu ve yorumlarını döndürür. 
  public function ResultPage(array $form, FormStateInterface $form_state){
    $form['Result_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Anket ile ilgili sonuçlara anket bittiği zaman ulaşabilirsiniz. "),
    ];
    $form['Result_fieldset']['detailsAllcomment'] = [
      '#type' => 'details',
      '#title' => $this->t('Yorumlar'),
      '#description' => $this->t(''),
    ];
    $form['Result_fieldset']['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Yorum Yap'),
      '#description' => $this->t(''),
    ];
    //anket sonuçlarını anket kapandıktan sonra gösteriyorum.
   if($this->controlTime() >= 0 ){
    $form['Result_fieldset']['detailsSurvey'] = [
      '#type' => 'details',
      '#title' => $this->t('Anket Sonuçları'),
      '#description' => $this->t(''),
    ];
    $questioninfo=$this->getQuestionInfo();
    for($i=0;$i<count($questioninfo);$i++){
      $answerinfo =$this->getAnswerInfo($questioninfo[$i]['id']);
      
      $form['Result_fieldset']['detailsSurvey'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Soru:'.($i+1)." ".$questioninfo[$i]['name'] ),
      ];
      for($j=0;$j<count($answerinfo);$j++){
          $resultinfo =$this->getResultInfo($answerinfo[$j]['answer_id']);
          
          $form['Result_fieldset']['detailsSurvey'][$i]['size'][$i][$j] = [
            '#type' => 'range',
            '#title' => $this->t(($j+1).".) ".$answerinfo[$j]['answer_name'] ." cevabına ".$resultinfo." kere cevap verildi."),
            '#min' => 0,
            '#step' => 1,
            '#max' => 100,
            '#default_value' => (int)$resultinfo,
            '#disabled' => TRUE,
            '#description' => $this->t(''),
          ];

        }
    }
    #anket zamanı henüz dolmamışsa oy iptal edilebilinir.
    }else if($this->controlTime() < 0){
      $form['Result_fieldset']['cancelvote'] = [
        '#type' => 'submit',
        '#value' => $this->t('Oyumu İptal et'),
        '#submit' => ['::CancelVote'],
        '#size' => 25,
        ];
    }
  //anket ile ilgili yapılmış yorumları çekeriz.
  $comments =$this->getComments();
  for($i=0;$i<count($comments);$i++){
    $form['Result_fieldset']['detailsAllcomment']['description'][$i] = [
      '#type' => 'item',
      '#title' =>$comments[$i]['comment_aliance'],
      '#markup' => $this->t($comments[$i]['comment_content']),
    ];
  }
    //Yorum alanı
    $form['Result_fieldset']['details']['useraliance'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Takma adınız'),
      '#description' => $this->t('yorumda kullanmak istediğiniz takma adınızı giriniz.'),
      '#default_value' => $form_state->getValue('useraliance', ''),
      
    ];
    $form['Result_fieldset']['details']['usercontent'] = [
    '#type' => 'textarea',
    '#title' => $this->t('Text'),
    '#description' => $this->t(''),
    '#default_value' => $form_state->getValue('usercontent', ''),
  ];

  $form['Result_fieldset']['details']['comment'] = [
    '#type' => 'submit',
    '#value' => $this->t('Yorum yap'),
    '#submit' => ['::CommentSubmit'],
  ];
    return $form;
  }
  //Anketin tarihine bakılarak zaman bilgisi çekilir ve şimdiki zaman ile çıkarılır.
  public function controlTime(){
    $result = db_query('SELECT survey_time FROM survey WHERE survey_id=:nid', array(':nid' => (int)base64_decode($_GET['update']) ) )->fetchField();
    $survery_time = strtotime(date("Y-m-d")) -($result);
    return  $survery_time; 
  }
  //kullanıcı id'si ve anket id'sine bakarak results tablosundan bulunan sonuçlar
  //kullanıcıya gösteririz.
  public function controlUser(){
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $query = db_select('results','t1');
    $query->fields('t1', ['answer_id','question_id']);
    $query->condition('survey_id',(int)base64_decode($_GET['update']), '=');
    $query->condition('user_id',$user->id(), '=');
    $results = $query->execute()->fetchAll();
    $sayac=0;
    foreach ($results as $result) {
      if ($result->answer_id != 0 ) {
        $output[$result->answer_id]=$result->question_id;
        $sayac++;
      }
    }
  return  $sayac;
  }
  //Ana Formumuzdur. Bu forumda anketler gösterilir.  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $controluser= $this->controlUser();
    $controltime= $this->controlTime();
    //kullanıcı ve anket zamanı kontrol edilir.
    if ($controluser >= 1 || $controltime >= 0 ) {
      return self::ResultPage($form, $form_state);
    }
    $survey_id=(int)base64_decode($_GET['update']);
     
    $form['survery_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t(""),
      '#prefix' => '<div id="answers-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    //anket bilgisi çekilir.
    $last_result= $this->getSurveyValues($survey_id);
    //anketdeki sorular çekilir.
    $this->setQuestion($last_result);
    for($i=0;$i<count($this->getQuestion());$i++ ){
    //ilgili sorulara göre cevaplar çekilir.
    $question_answers=$this->optionsget($last_result[$i]['id']);
    $form['survery_fieldset']['colour_select'.$i] = [
      '#type' => 'radios',
      '#title' => t($last_result[$i]['question_name']),
      '#options' => $question_answers,
      '#required' => TRUE,
    ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Gönder'), 
    ];
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  //sorulara id'sine göre results tablosuna seçili cevapları ekler. 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $questions=$this->getQuestion(); 
    for($i=0;$i<count($questions);$i++){
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      db_insert('results')
      ->fields(array(
        'answer_id' =>$form_state->getValue('colour_select'.$i),
        'question_id' =>$questions[$i]['id'],
        'survey_id' =>base64_decode($_GET['update']) ,
        'user_id' =>$user->id(),
       ))->execute();
    }
  }

}