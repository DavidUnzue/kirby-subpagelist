<?php
/**
 * Field plugin Subpagelist
 *
 * @package   Kirby CMS
 * @author    Flo Kosiol <git@flokosiol.de>
 * @link      http://flokosiol.de
 * @version   1.0.5
 */

class SubpagelistField extends BaseField {

  /**
   * Assets
   */
  public static $assets = array(
    'css' => array(
      'subpagelist.css',
    ),
  );

  /**
   * Set field property and default value if required
   *
   * @param string $option
   * @param mixed  $value
   */
  public function __set($option, $value) {

    /* Set given value */
    $this->$option = $value;

    /* Validation */
    switch($option) {
      case 'flip':
        if(!is_bool($value))
          $this->flip = false;
        break;
    }
  }

  /**
   * Generate label markup
   *
   * @return string
   */
  public function label() {

    return null;

  }

  /**
   * Generate field content markup
   *
   * @return string
   */
  public function content() {

    $wrapper = new Brick('div');
    $wrapper->addClass('subpagelist');

    $children = $this->subpages();

    // add pagination to the subpages
    $limit = ($this->limit()) ? $this->limit() : 10000;
    $children = $children->paginate($limit, array('page' => get('page')));

    // use existing snippet to build the list
    // @see panel/app/controllers/views/pages.php
    $subpages = new Snippet('pages/sidebar/subpages', array(
      'title'      => strip_tags(parent::label()),
      'page'       => $this->page(),
      'subpages'   => $children,
      'addbutton'  => !api::maxPages($this, $this->subpages()->max()),
      'pagination' => $children->pagination(),
    ));

    // use template with defined vars
    $wrapper->html(tpl::load(__DIR__ . DS . 'template.php', array('subpages' => $subpages)));
    return $wrapper;

  }


  /**
   * Get subpages
   *
   * @return object
   */
  public function subpages() {

    $field = &$this;
    $subpages = $this->page()->children();

    // Check for filters
    if (isset($this->filter) && is_array($this->filter)) {
      $filter = $this->filter();

      // only visible subpages
      if (isset($filter['visible']) && $filter['visible'] == TRUE) {
        $subpages = $subpages->visible();
      }

      // only invisible subpages
      if (isset($filter['visible']) && $filter['visible'] == FALSE) {
        $subpages = $subpages->invisible();
      }

      // only specific template
      if (!empty($filter['template'])) {
        $subpages = $subpages->filterBy('template',$filter['template']);
      }

      // only specific field value
      if (isset($filter['field'])) {
        if (isset($filter['field']['name']) && isset($filter['field']['value'])) {
            if (isset($filter['field']['compared_to'])) {
              $subpages = $subpages->filterBy($filter['field']['name'],$filter['field']['value'], $filter['field']['compared_to']);
            } else {
              $subpages = $subpages->filterBy($filter['field']['name'],$filter['field']['value']);
            }
        }
      }
    }

    // reverse order
    if (isset($this->flip) && $this->flip == TRUE) {
      $subpages = $subpages->flip();
    }

    // sorting options
    if (isset($this->sort)) {
      $sort_options = explode(" ", $this->sort);
      if (count($sort_options) >= 2) {
        $field = $sort_options[0];
        $direction = $sort_options[1];
      } else {
        $field = $this->sort;
        $direction = 'desc';
      }
      $method = SORT_REGULAR;
      $subpages = $subpages->sortBy($field, $direction, $method);
    }

    return $subpages;
  }

}
