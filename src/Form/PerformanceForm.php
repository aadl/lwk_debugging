<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\PerformanceForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class PerformanceForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0) {
    
    $perf = _ums_cardfile_get_performance($pid);
    if ($perf['pid']) {

      $form['info'] = [
        '#value' => '<h2>Editing Info for Performance of ' . $perf['work']['title'] . ' at ' .
                  $perf['event']['venue'] . ' on ' . $perf['event']['date'] . '</h2>',
      ];
        $form['pid'] = [
        '#type' => 'value',
        '#value' => $pid,
        ];
      $form['weight'] = [
        '#type' => 'textfield',
        '#title' => t('Performance Order'),
        '#default_value' => $perf['weight'],
        '#size' => 8,
        '#maxlength' => 8,
        '#description' => "Number corresponding to this performance's order in the event",
      ];
      $form['notes'] = [
        '#type' => 'textarea',
        '#title' => 'Performance Notes',
        '#default_value' => $perf['notes'],
        '#description' => 'Enter notes about this specific repertoire performance, e.g. "Encore"',
      ];
      $form['youtube_url'] = [
        '#type' => 'textarea',
        '#title' => t('YouTube URL(s)'),
        '#default_value' => $perf['youtube_url'],
        '#description' => 'Enter youtube URLs separated with a comma????????',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
      ];

      return $form;
    } else {
      \Drupal::messenger()->addMessage("Unable to find performance with ID: $pid", 'error');
      return new RedirectResponse('/cardfile');
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pid = $form_state->getValue('pid');

    $perf = [];
    $perf['pid'] = $pid;
    $perf['notes'] = $form_state->getValue('notes');
    $perf['youtube_url'] = $form_state->getValue('youtube_url');
    $perf['weight'] = $form_state->getValue('weight');

    ums_cardfile_save('ums_performances', $perf, 'pid');
    \Drupal::messenger()->addMessage('Updated Performance');
    $form_state->setRedirect('ums_cardfile.performance', ['pid' => $pid]);
  }
}