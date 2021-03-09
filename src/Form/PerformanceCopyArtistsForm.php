<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\PerformanceCopyArtistsForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PerformanceCopyArtistsForm extends FormBase {
  
  public function getFormId() {
    return 'performance_copy_artists_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0) {
    dblog('PerformanceCopyArtistsForm: buildForm ENTERED - pid', $pid);

    $performance = _ums_cardfile_get_performance($pid);

    dblog('PerformanceCopyArtistsForm: performance: ', $performance);

    $form = [
      '#attributes' => ['class' => 'form-width-exception']
    ];

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Copy Performance Artists from other Repertoire'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#prefix' => '<div id="LWK-PerformanceCopyArtistsForm',
      '#suffix' => '</div>',
    ];

    // $form['collapsible']['table'] = [
    //   '#prefix' => '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
    //   '#suffix' => '</tr></table>',
    // ];

    $form['collapsible']['pid'] = [
      '#type' => 'value',
      '#value' => $pid,
    ];

    $other_pids = [];
    foreach ($performance['event']['performances'] as $other_performance) {
      if ($other_performance['pid'] != $pid) {
        $other_performance = _ums_cardfile_get_performance($other_performance['pid']);
        $description = '<strong>' . $other_performance['work']['title'] . '</strong>';
        foreach ($other_performance['artists'] as $artist) {
          $description .= '<br />' . $artist['name'] . ' (' . $artist['role'] . ')';
        }
        $other_pids[$other_performance['pid']] = $description;
      }
    }
    dblog('PerformanceCopyArtistsForm: other_pids: ', $other_pids);

    $form['collapsible']['source_pid'] = [
      '#type' => 'radios',
      '#title' => 'Select Performance as source of Performance Artists',
      '#options' => $other_pids,
    ];

    $form['collapsible']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Copy Artists',
    ];
      
    dblog('PerformanceCopyArtistsForm - RETURNING' ); // $form:', $form);

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('PerformanceCopyArtistsForm: submitForm ENTERED');
    $db = \Drupal::database();

    $pid = $form_state->getValue('pid');
    $source_pid = $form_state->getValue('source_pid');

    // Copy all performance artists from source pid to new pid
    $artist_performances = $db->query("SELECT * FROM ums_artist_performances WHERE pid = $source_pid")->fetchAll();
    foreach ($artist_performances as $artist_perf) {
      $artist_perf->pid = $pid;
      ums_cardfile_save('ums_artist_performances', $artist_perf, NULL);
    }
    drupal_set_message("Copied Repertoire Artists to the Performance");
  }
}