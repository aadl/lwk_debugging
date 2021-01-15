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
  public function cf_home() {
    return [
      '#theme' => 'ums_cardfile_home'
    ];
  }

  public function cf_artists($filter = '') {
    $rows = array();
    $db = \Drupal::database();
    $page = pager_find_page();
 
    dblog('artists: ENTERED, $filter = ' . $filter . ', $page = ', $page);
    $per_page = 50;
    $offset = $per_page * $page;
    $query = $db->select('ums_artists', 'artists')
      ->fields('artists', ['aid','name','name_plain','alias','notes','photo_nid']);

    if (NULL != $filter) {
      $query->condition('name_plain', $db->escapeLike($filter) . "%", 'like');
    }
    $query->orderBy('name_plain');

    $num_rows = $query->countQuery()->execute()->fetchField();
    $artists = $query->range($offset, $per_page)->execute()->fetchAll();
    $pager = pager_default_initialize($num_rows, $per_page);

    foreach ($artists as $artist) {
      if ($artist->photo_nid) {
        $photo_links = array();
        foreach (explode(',', $artist->photo_nid) as $photo_nid) {
          $photo_nid = trim($photo_nid);
          $photo_links[] = ums_cardfile_create_link('Photo', 'node/' . $photo_nid);
        }
        $photo_links = implode(', ', $photo_links);
      } else {
        $photo_links = '';
      }
      $artist->photo_links = $photo_links;
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
  }

  public function cf_artist($aid = 0) {
    $db = \Drupal::database();

    $artist = _ums_cardfile_get_artist($aid);
    if ($artist['aid']) {
      return [
        '#theme' => 'ums_cardfile_artist',
        '#artist' => $artist,

        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      drupal_set_message("Unable to find artist with ID:$aid");
      drupal_goto('cardfile/artists');
    }
  }

  function cf_venues() {
    dblog('cf_venues: ENTERED');
    $db = \Drupal::database();
    $venues = $db->query('SELECT * FROM ums_venues ORDER BY name')->fetchAll();
    dblog('cf_venues: venues=', $venues);

    $rows = [];
    foreach ($venues as $venue) {
      dblog('cf_venues: -------------------- venue=', $venue);
      $link = ums_cardfile_create_link('X', "cardfile/venues/delete/$venue->vid");
      dblog('$link = ', $link);
      $rows[] = ['name' => $venue->name, 
                'delete_link' => '[' . ums_cardfile_create_link('X', "cardfile/venues/delete/$venue->vid") . ']'
              ];
    }
    dblog('cf_venues: rows=', $rows);
    
    return [
        '#theme' => 'ums_cardfile_venues',
        '#rows' => $rows,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }
}
