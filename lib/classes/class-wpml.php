<?php
/**
* WP-Property Compatibility with WPML
*
*
* @version 2.00
* @author Fadi Yousef <frontend-expert@outlook.com>
* @package WP-Property
* @subpackage Functions
*/

namespace UsabilityDynamics\WPP {

  class WPML{
  
  function __construct() {
  
    add_filter( 'wpp::get_properties::matching_ids',array($this, 'filtering_matching_ids') );
    add_action( 'wpp::above_list_table',array($this, 'display_languages' ) );
    add_action( 'wpp::save_settings',array($this, 'translate_property_types'),10,1 );
  }
  /*
  * get properity posts count by language code
  * @param $lang string
  * @author Fadi Yousef  frontend-expert@outlook.com
  */
  public function get_property_posts_count_bylang( $lang ){
    global $sitepress;
    $lang_now = $sitepress->get_current_language();
    $lang_changed = 0;
    if($lang_now != $lang){
    $sitepress->switch_lang($lang);
    $lang_changed = 1;
    }
    $args = array(
    'posts_per_page' => -1,
    'post_type' => 'property',
    'suppress_filters' => false
    );
    $result = new \WP_Query($args);
    if($lang_changed) $sitepress->switch_lang($lang_now);
    return $result->post_count;
  }
  /**
  * Display property Languages if WPML plugin is active
  *
  * @Author Fadi Yousef frontend-expert@outlook.com
  */
  public function display_languages(){
    global $pagenow, $typenow;
    if( 'property' === $typenow && 'edit.php' === $pagenow )
    {
    $curr_lang = apply_filters( 'wpml_current_language', NULL );
    $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
    $all_count = 0;
    if ( !empty( $languages ) ) {?>
      <ul class="lang_subsubsub" style="clear:both">
      <?php foreach( $languages as $l ):
        $posts_count = $this->get_property_posts_count_bylang($l['language_code']);
        $all_count += intval($posts_count);
      ?>
        <li class="<?php echo 'language_'.$l['language_code']; ?>">
        <a href="<?php echo '?post_type=property&page=all_properties&lang='.$l['language_code']; ?>" class="<?php echo ($l['active']) ? 'current' : 'lang'; ?>"><?php echo $l['translated_name']; ?>
        <span class="count">(<?php echo $posts_count; ?>)</span>
        </a>
        </li>
      <?php endforeach;?>
      <li class="language_all"><a href="?post_type=property&page=all_properties&lang=all" 
    class="<?php if($curr_lang == 'all') echo 'current';  ?>"><?php echo __( 'All languages', 'sitepress' ).' ('.$all_count.')'; ?></a></li>
      </ul>
    <?php }	
    }	
  }

    /*
    * get properties IDs by meta key
    * @params:
    * meta_key string
    * @return array
    * @author Fadi Yousef frontend-expert@outlook.com
    */
    public function filtering_matching_ids($matching_ids){
    global $wpdb;
    $matching_ids = implode(',',$matching_ids);
    $sql_query = "SELECT post_id FROM {$wpdb->postmeta} 
    LEFT JOIN {$wpdb->prefix}icl_translations ON 
    ({$wpdb->postmeta}.post_id = {$wpdb->prefix}icl_translations.element_id) WHERE post_id IN ($matching_ids)";
    $sql_query .= " AND {$wpdb->prefix}icl_translations.language_code ='".ICL_LANGUAGE_CODE."' GROUP BY post_id";

    return $wpdb->get_col($sql_query);
    
    }
    /*
    * Add dynamic elements to translation
    * @params
    * @package_name - string // types under developer tab
    * @str_name - string // the element need translation
    * @author Fadi Yousef frontend-expert@outlook.com
    */
    public function translate_property_types($data){
    $package = array(
      'kind' => 'Property Types',
      'name' => 'custom-types',
    'title' => 'Property Types',
    );
    //echo '<pre>';print_r($data);echo '</pre>';exit();
    $types = $data['wpp_settings'][ 'property_types' ];
    foreach($types as $key => $type){
      do_action('wpml_register_string', $type , $key , $package , $type , 'LINE'); 
    }
    //echo '<pre>';print_r($types);echo '</pre>';exit();
    }
   
  }

}