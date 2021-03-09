<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\PerformanceDeleteArtistConfirmForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;



class PerformanceDeleteArtistConfirmForm extends ConfirmFormBase {
  
  public function getFormId() : string {
    return 'performance_delete_artists_confirm_form';
  }

  protected $aid;
  protected $pid;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0, $aid = 0) {
    $this->pid = $pid;
    $this->aid = $aid;
    $performance = _ums_cardfile_get_performance($pid);
    $artist = _ums_cardfile_get_artist($aid);
    $artist_name = $artist['name'];
    $performance_title = $performance['work']['title'];

    $form = [
      '#attributes' => ['class' => 'form-width-exception']
    ];

    $form['engineered_header'] = [
      '#prefix' => '<h3>Are you sure you want to remove ' . $artist_name . ' from this performance of ' . $performance_title . '?</h3>',
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('ums_cardfile.performance', ['pid' => $this->pid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Performance - Delete Artist');
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
    $sql_delete_statement = "DELETE FROM ums_artist_performances WHERE aid = $this->aid AND pid = $this->pid";
    $db->query($sql_delete_statement);

    drupal_set_message("Artist has been deleted");

    $form_state->setRedirect('ums_cardfile.performance', ['pid' => $this->pid]);
  }
}