<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\PerformanceDeleteArtistConfirmForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;



class WorkDeleteArtistConfirmForm extends ConfirmFormBase {
  
  public function getFormId() : string {
    return 'work_delete_artists_confirm_form';
  }

  protected $aid;
  protected $pid;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wid = 0, $aid = 0) {
    dblog('WorkDeleteArtistConfirmForm: buildForm ENTERED, $wid =', $wid, ', aid =', $aid);
    $this->wid = $wid;
    $this->aid = $aid;
    $work = _ums_cardfile_get_work($wid);
    $artist = _ums_cardfile_get_artist($aid);
    $artist_name = $artist['name'];
    $work_title = $work['title'];

    // $form = [
    //   '#attributes' => ['class' => 'form-width-exception']
    // ];

    $form['engineered_header'] = [
      '#prefix' => '<h3>Are you sure you want to remove ' . $artist_name . ' as a creator from ' . $work_title . '?</h3>',
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('ums_cardfile.work', ['wid' => $this->wid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Work - Delete Artist');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('NOTE. This action cannot be undone');

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) { 
    $db = \Drupal::database();
    $sql_delete_statement = "DELETE FROM ums_artist_works WHERE aid = $this->aid AND wid = $this->wid";
    $db->query($sql_delete_statement);

    drupal_set_message("Artist has been deleted");

    $form_state->setRedirect('ums_cardfile.work', ['wid' => $this->wid]);
  }
}