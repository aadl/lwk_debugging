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
    $artists = $query->range($offset, $per_page)->execute()->fetchAll();
    
    
    
  
    
    // $limit = (isset($offset) && isset($per_page) ? " LIMIT $offset, $per_page" : '');
    dblog('artists: BEFORE total');
    $total = $db->query("SELECT COUNT(*) as total FROM ums_artists")->fetch()->total;
    dblog('artists: AFTER $total = ', $total);

 
    // $query = 'SELECT * FROM ums_artists ORDER BY name_plain limit '. $per_page;
    // if ($filter) {
    //   $query = "SELECT * FROM ums_artists WHERE name_plain LIKE $filter% ORDER BY name_plain";
    // }
    // $artists = $db->query($query)->fetchAll();

    $pager = pager_default_initialize($total, $per_page);

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
      '#pager' => [
        '#type' => 'pager',
        '#quantity' => 5
      ],
      '#cache' => [ 'max-age' => 0 ]
    ];

    dblog('artists:', count($artists));
         
  }
}
