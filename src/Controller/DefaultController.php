<?php /**
 * @file
 * Contains \Drupal\ums_cardfile\Controller\DefaultController.
 */

namespace Drupal\ums_cardfile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Default controller for the ums_cardfile module.
 */
class DefaultController extends ControllerBase {
  public function home() {
    // return [
    //   '#type' => 'markup',
    //       '#markup' => $this->t('Hello World!'),
    // ];

    return [
      '#theme' => 'ums_cardfile_home'
    ];
  }

  public function artists($filter = '') {
    dblog('artists: ENTERED');
    $rows = array();
    $db = \Drupal::database();
    $page = pager_find_page();
 
    dblog('artists:   $page = ', $page);
    $per_page = 50;
    $offset = $per_page * $page;
    
    dblog('artists: $offset = ', $offset);

    $query = $db->select('ums_artists', 'artists')
    ->fields('artists', ['aid','name','name_plain','alias','notes','photo_nid']);

    if (NULL != $filter) {
      $query->condition('name_plain', $db->escapeLike($filter) . "%", 'like');
    }
    $query->orderBy('name_plain');

    $num_rows = $query->countQuery()->execute()->fetchField();
    dblog('artists: num_rows = ', $num_rows, '$filter = ', $filter);

    $artists = $query->range($offset, $per_page)->execute()->fetchAll();
    
    //dblog('artists: BEFORE total');
    //$total = $db->query("SELECT COUNT(*) as total FROM ums_artists")->fetch()->total;
    //dblog('artists: AFTER $total = ', $total);

    $pager = pager_default_initialize($num_rows, $per_page);

    dblog('artists:', count($artists));
    dblog('pager:', $pager);


    foreach ($artists as $artist) {
      dblog('FOREACH - artist = ', $artist);
      if ($artist->photo_nid) {
        $photo_links = array();
        foreach (explode(',', $artist->photo_nid) as $photo_nid) {
          $photo_nid = trim($photo_nid);
          $photo_links[] = \Drupal::l('Photo', Url::fromUri('internal:/node/' . $photo_nid));
        }
        $photo_links = implode(', ', $photo_links);
      } else {
        $photo_links = '';
      }
      $artist->photo_links = $photo_links;

       dblog('FOREACH END OF LOOP - artist = ', $artist);
     
    }



    return [
      '#theme' => 'ums_cardfile_artists',
      '#artists' => $artists,
      '#filter' => $filter,
      '#pager' => [
        '#type' => 'pager',
        '#quantity' => 5
      ],
      '#cache' => [ 'max-age' => 0 ]
    ];

    dblog('artists:', count($artists));
         
  }
}
