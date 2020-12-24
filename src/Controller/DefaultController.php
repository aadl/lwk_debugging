<?php /**
 * @file
 * Contains \Drupal\ums_cardfile\Controller\DefaultController.
 */

namespace Drupal\ums_cardfile\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the ums_cardfile module.
 */
class DefaultController extends ControllerBase {
  
  public function home() {
    // return [
    //   '#type' => 'markup',
    //       '#markup' => $this->t('Hello World!'),
    // ];
    ksm('#####');
    return [
      '#theme' => 'ums_cardfile_home'
    ];
  }
}