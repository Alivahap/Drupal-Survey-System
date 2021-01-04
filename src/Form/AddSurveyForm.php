<?php
namespace Drupal\surveysystem\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * İki adımlı soru ekleme modülü düzenledim.
 *Birinci Adım, Anket ismini, ikinci adım anket soru cavaplarını 
 *kullanıcıdan alıyor
 * 
 */
class AddSurveyForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_addsurvey_form1';
  }
  /**v
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //sayfa numarasına bakıyorum, eğer 2. sayfaya geçmişsek
    //PageTwo fonksiyonundaki form elemanları gösterilir.
    if ($form_state->has('page_num') && $form_state->get('page_num') == 2) {
      return self::PageTwo($form, $form_state);
    }
    //Varsayılan sayfa numarası 1. sayfadır.
    $form_state->set('page_num', 1);
    //Anket ile ilgili form elemanları
    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Anket ismini giriniz'),
    ];
    $form['survey_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anket'),
      '#description' => $this->t('anket ismi ekleme'),
      '#default_value' => $form_state->getValue('survey_name', ''),
      '#required' => TRUE,
    ];
    $form['expiration'] = [
      '#type' => 'date',
      '#title' => $this->t('Anket Süresi'),
      '#default_value' => date_default_timezone_get('Europe/Istanbul'),
      '#description' =>' ',
      '#required' => TRUE,
    ];
    //Özel olarak işaretlenmiş anketler için koydum.
    $form['special'] = array(
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Özel olarak işaretle'),
    );
    $form['actions'] = [
      '#type' => 'actions',
    ];
    //Formumuzun ikinci sayfasına geçmesi için koyduğum button
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Anketi Ekle'),
      //MultistepFormNextSubmit() ile bir sonraki sayfaya geçeriz
      '#submit' => ['::MultistepFormNextSubmit'],
      // MultistepFormNextValidate() ile anket alanının boş geçilmemesini sağlarız.
      '#validate' => ['::MultistepFormNextValidate'],
    ];
    return $form;
  }
  //1.Sayfa'daki anket isminin kontrolünü yapar boş değerlere izin vermeyiz. 
  public function MultistepFormNextValidate(array &$form, FormStateInterface $form_state) {
   
   $survery_time = strtotime(date("Y-m-d")) -strtotime($form_state->getValue('expiration'));
    
   if ($survery_time >= 0) {
      $form_state->setErrorByName('expiration', $this->t('Lütfen bu alanı şimdiki zamandan küçük girmeyiniz'));
    }
   
  }
  // Sonraki butonuna tıkladığım zaman ikinci sayfaya geçmem için gerekli olan
  // page_values değişkenini 2 yaparak, 2. sayfaya geçmiş oluruz.
  public function MultistepFormNextSubmit(array &$form, FormStateInterface $form_state) {
   //kullanıcı idsini çektim.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    db_insert('survey')
    ->fields(array(
      'survey_name' => $form_state->getValue('survey_name'),
      //tarihi string olarak kayıt ettim.
      'survey_time' =>strtotime($form_state->getValue('expiration')),
      //özel anketler için seçim yaptım.
      'checked' =>$form_state->getValue('special'),
      'user_id' =>$user->id(),
     ))
    ->execute();
    $form_state
      ->set('page_values', [
        //submit işleminden sonra birinci sayfa değişkenleri kayıt edilir.
        'survey_name' => $form_state->getValue('survey_name'),
      ])
      ->set('page_num', 2)
      // Sayfa numarsını iki yaparız ve yeniden form elemanlarını yüklemeyi
      //onaylarız
      ->setRebuild(TRUE);
  }
  //2.Sayfanın form elemalarını eklediğimiz metot.
  //2.sayfa çevap ekleme formudur.
  public function PageTwo(array &$form, FormStateInterface $form_state) {  
  $form['description'] = [
    '#type' => 'item',
    '#title'=>$form_state->getValue('survey_name'),
    '#markup' => $this->t('Eklemek istediğiniz kadar soru giriniz.'),
  ];
  // Cevap sayısını alıyoruz yoksa varsayılan cevapları ekliyoruz.
  $num_answers = $form_state->get('num_answers');
   // Kaç tane cevap eklemesini istediyorsak form başlagıcında gösteririz.
  if ($num_answers === NULL) {
    $name_field = $form_state->set('num_answers', 2);
    $num_answers = 2;
  }
 //form elemanları
  $form['question'] = array(
    '#title' => t('anket sorusunu giriniz'),
    '#type' => 'textfield',
    '#size' => 25,
    '#default_value' => '',  
    '#description' => t("zorunludur"),
    '#required' => TRUE,
  );
  //Cevapları sorudan ayıracak alan, 
  $form['#tree'] = TRUE;
  $form['answers_fieldset'] = [
    '#type' => 'fieldset',
    '#title' => $this->t(''),
    '#prefix' => '<div id="answers-fieldset-wrapper">',
    '#suffix' => '</div>',
  ];
  //sayfadaki toplam cevap sayısına göre form ekranına bastırırız.
  for ($i = 0; $i < $num_answers; $i++) {
    $form['answers_fieldset']['name'][$i] = [
      '#type' => 'textfield',
      '#default_value' => '',
      '#title' => $this->t( ($i+1) ."".'. Cevap '),
    ];
  }
  $form['answers_fieldset']['actions'] = [
    '#type' => 'actions',
  ];
  //Ajax ile Cevap ekle butonuna tıklayınca sayfanın tamamı yüklenmez
  //istediğimiz alanı yükleriz. callback fonksiyonuna form elamının adını
  // geri döndürürüz.
  $form['answers_fieldset']['actions']['add_name'] = [
    '#type' => 'submit',
    '#value' => $this->t('Cevap Ekle'),
    '#submit' => ['::addOne'],
    '#ajax' => [
      'callback' => '::addmoreCallback',
      'wrapper' => 'answers-fieldset-wrapper',
    ],
  ];
  // Cevap sayısı ikiden fazla ise cevapları silmesi için buton ekleriz.
  //Cevap sil butonuna tıkladığımız zaman eklenmiş olan cevapı sileriz.
  if ($num_answers > 2) {
    $form['answers_fieldset']['actions']['remove_answers'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cevap sil'),
      '#submit' => ['::removeCallback'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'answers-fieldset-wrapper',
      ],
    ];
  }
//ilk sayfaya dönmek için kullandığım button.
    $form['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      // Custom submission handler for 'Back' button.
      '#submit' => ['::PageTwoBack'],
      // We won't bother validating the required 'color' field, since they
      // have to come back to this page to submit anyway.
      '#limit_validation_errors' => [],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Soruyu Ekle'),
    ];

    return $form;
  }
  //page_values değişkenini 1'e set ederek ilk sayfaya dönmemizi sağlayan metot.
  public function PageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state 
      ->setValues($form_state->get('page_values'))
      ->set('page_num', 1)
      ->setRebuild(TRUE);
  }
  //sorumuzun 2'den fazla cevap seçeneği varsa birini silmemize yarayan ajax metotu
  //2. sayfadaki çizgili alandaki yeri yeniden yükleyerek silme işlemini yapar.
  public function removeCallback(array &$form, FormStateInterface $form_state) {
   $answers_field = $form_state->get('num_answers');
    if ($answers_field > 2) {
     $remove_button = $answers_field - 1;
     $form_state->set('num_answers', $remove_button);
    }
   $form_state->setRebuild();
  }
  //Soruya bir çevap eklemek için kullandığımız metot.
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_answers');
    $add_button = $name_field + 1;
    $form_state->set('num_answers', $add_button);
   $form_state->setRebuild();
  }
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['answers_fieldset'];
  }
 /**
   * {@inheritdoc}
   */
  //veri tabanına soruları ekleyen metot.
  public function submitForm(array &$form, FormStateInterface $form_state) {
   //Anket ismini kullanıcıya göstermek için çekeriz.
   $last_id = db_query('SELECT MAX(survey_id) FROM survey')->fetchField();
    //Hangi kullanıcının oylama yaptığını göstermek için kullanıcı id'sini çekeriz
    //ve veri tabanına ekleriz.
    db_insert('question')
      ->fields(array(
        'question' => $form_state->getValue('question'),
        'survey_id' => $last_id,
        'created' => time(),
       ))->execute();
      $answers_values = $form_state->getValue(['answers_fieldset', 'name']);
      $last_question_id= db_query('SELECT MAX(id) FROM question')->fetchField();
      for($i=0;$i< count($answers_values);$i++ ){
          db_insert('answers')
          ->fields(array(
           'answer_name' => $answers_values[$i],
          'question_id' =>  $last_question_id,
           ))->execute();
       }
    //kullanıcın soru eklemesi için ikinci sayafada kalmasını sağlarız.
    $form_state
    ->setValues($form_state->get('page_values'))
    ->set('page_num', 2)
    ->setRebuild(TRUE);
    
    drupal_set_message(t('Tekrar soru ekleyebilirsiniz'));
    //Cevapları gösteririz.
    $output = $this->t('Cevaplarınız: @answer', [
      '@answer' => implode(', ', $answers_values),
    ]);
    $this->messenger()->addMessage($output);

  }
}
