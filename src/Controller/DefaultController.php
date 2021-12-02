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

    dblog("lwk_test ENTERED -------------------------------------------------------------------------");
    //dblog(arborcat_pickup_locations());
    $guzzle = \Drupal::httpClient();
    $api_url = \Drupal::config('arborcat.settings')->get('api_url');
    dblog('api_url=', $api_url);

    $locations = json_decode($guzzle->get("$api_url/locations")->getBody()->getContents());
    dblog($locations);

    return [
    ];


    // Testing \Drupal:messenger
    $values['title'] = "UNIT TESTING messenger methods";
    \Drupal::messenger()->addStatus(t($values['title'] . ' status added'));
    \Drupal::messenger()->addMessage(t($values['title'] . ' message added'));
    \Drupal::messenger()->addWarning(t($values['title'] . ' warning added'));
    \Drupal::messenger()->addError(t($values['title'] . ' error added'));

    // Testing loading nodes using Node::load

    \Drupal::messenger()->addMessage(t('Nodes Loaded = ' . lwk_debugging_test_node_load())); ;

    // Testing db_query update
    // OLD:  db_query("INSERT INTO {accountfix_log} (timestamp, uid, path) VALUES (%d, %d, '%s')", time(), $user->uid, $_GET['q']);
    
    
    global $user;
    dblog('global $user->uid', $user->uid);
    //dblog('global $user->id', $user->id());
    $user = \Drupal::currentUser();
    dblog('\Drupal::currentUser $user->uid', $user->uid);
    dblog('\Drupal::currentUser $user->id', $user->id());
    // $db = \Drupal::database();
    // $result = $db->insert('comment')->fields([
    //   'timestamp' => time(),
    //   'uid' => $user->uid,
    //   'path' => \Drupal::request()->query->get('q')
    // ])->execute();
   
    return [
    ];
  }


}
