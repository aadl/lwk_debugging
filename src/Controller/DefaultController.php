<?php /**
 * @file
 * Contains \Drupal\lwk_debugging\Controller\DefaultController.
 */

namespace Drupal\lwk_debugging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Default controller for the lwk_debugging module.
 */
class DefaultController extends ControllerBase {
  public function lwk_test() {

    dblog("lwk_test ENTERED ------------------------");
   
    return [
    ];
  }


}
