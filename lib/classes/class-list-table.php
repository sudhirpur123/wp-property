<?php
/**
 * Properties List Table class.
 *
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\List_Table')) {

    class List_Table extends \UsabilityDynamics\WPLT\WP_List_Table {

      /**
       * @param array $args
       */
      public function __construct( $args = array() ) {

        $this->args = wp_parse_args( $args, array(
          //singular name of the listed records
          'singular' => \WPP_F::property_label(),
          //plural name of the listed records
          'plural' => \WPP_F::property_label( 'plural' ),
          // Post Type
          'post_type' => 'property',
          'post_status' => 'all',
          'orderby' => 'ID',
          'order' => 'DESC',
        ) );

        //Set parent defaults
        parent::__construct( $this->args );

        add_filter( 'wplt_column_title_label', array( $this, 'get_column_title_label' ), 10, 2 );

      }

      public function get_columns() {
        $columns = apply_filters( 'wpp_overview_columns', array(
          'cb'            => '<input type="checkbox" />',
          'title'         => __( 'Title', ud_get_wp_property('domain') ),
          'property_type' => __( 'Type', ud_get_wp_property('domain') ),
          'overview'      => __( 'Overview', ud_get_wp_property('domain') ),
          'created'         => __( 'Added', ud_get_wp_property('domain') ),
          'modified'       => __( 'Updated', ud_get_wp_property('domain') ),
          'featured'      => __( 'Featured', ud_get_wp_property('domain') )
        ) );

        $meta = ud_get_wp_property( 'property_stats', array() );

        foreach( ud_get_wp_property( 'column_attributes', array() ) as $id => $slug ) {
          if( !empty( $meta[ $slug ] ) ) {
            $columns[ $slug ] = $meta[ $slug ];
          }
        }

        $columns[ 'thumbnail' ] = __( 'Thumbnail', ud_get_wp_property('domain') );

        return $columns;
      }

      /**
       * Sortable columns
       *
       * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
       */
      public function get_sortable_columns() {
        return array(
          'title'	 	=> array( 'title', false ),	//true means it's already sorted
          'created'	 	=> array( 'date', false ),	//true means it's already sorted
          'modified'	 	=> array( 'modified', false ),	//true means it's already sorted
        );
      }

      /**
       * Returns default value for column
       *
       * @param array $item
       * @param array $column_name
       * @return string
       */
      public function column_default( $item, $column_name ) {
        switch ($column_name) {
          default:
            //Show the whole array for troubleshooting purposes
            if (isset($item->{$column_name}) && is_string( $item->{$column_name} )) {
              return $item->{$column_name};
            } else {
              return '-';
            }
        }
      }

      /**
       * Return Created date
       *
       * @param $post
       * @return string
       */
      public function column_created( $post ){
        return get_the_date('',$post);
      }

      /**
       * Return Modified date
       *
       * @param $post
       * @return string
       */
      public function column_modified( $post ){;
        return get_post_modified_time(get_option('date_format'), null, $post, true);
      }

      /**
       * Return Property Type
       *
       * @param $post
       * @return mixed|string
       */
      public function column_property_type( $post ) {
        $property_types = ud_get_wp_property('property_types');
        $type = get_post_meta( $post->ID, 'property_type', true );
        if( !empty( $type ) && isset( $property_types[ $type ] ) ) {
          $type = $property_types[ $type ];
        }
        return !empty( $type ) ? $type : '-';
      }

      /**
       * Return Overview Information
       *
       * @param $post
       * @return mixed|string
       */
      public function column_overview( $post ) {
        $data = '';
        $attributes = ud_get_wp_property( 'property_stats' );
        $stat_count = 0;
        $hidden_count = 0;
        $display_stats = array();

        foreach( $attributes as $stat => $label ) {
          $values = isset( $post->$stat ) ? $post->$stat : array( '' );
          if ( !is_array( $values ) ) {
            $values = array( $values );
          }
          foreach ( $values as $value ) {
            $print_values = array();
            if ( empty( $value ) || strlen( $value ) > 15 ) {
              continue;
            }
            $print_values[ ] = apply_filters( "wpp_stat_filter_{$stat}", $value );
            $print_values = implode( '<br />', $print_values );
            $stat_count++;
            $stat_row_class = '';
            if($stat_count > 5) {
              $stat_row_class = 'hidden wpp_overview_hidden_stats';
              $hidden_count++;
            }
            $display_stats[ $stat ] = '<li class="' . $stat_row_class . '"><span class="wpp_label">' . $label . ':</span> <span class="wpp_value">' . $print_values . '</span></li>';
          }
        }

        if ( is_array( $display_stats ) && count( $display_stats ) > 0 ) {
          if ( $stat_count > 5 ) {
            $display_stats[ 'toggle_advanced' ] = '<li class="wpp_show_advanced" advanced_option_class="wpp_overview_hidden_stats">' . sprintf( __( 'Toggle %1s more.', 'wpp' ), $hidden_count ) . '</li>';
          }
          $data = '<ul class="wpp_overview_column_stats wpp_something_advanced_wrapper">' . implode( '', $display_stats ) . '</ul>';
        }
        return $data;
      }

      /**
       * Return Featured
       *
       * @param $post
       * @return mixed|string
       */
      public function column_featured( $post ) {
        $data = '';
        $featured = get_post_meta( $post->ID, 'featured', true );
        $featured = !empty( $featured ) && !in_array( $featured, array( '1', 'false' ) ) ? true : false;
        if ( current_user_can( 'manage_options' ) ) {
          if ( $featured ) {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle wpp_is_featured' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "' value='" . __( 'Featured', ud_get_wp_property('domain') ) . "' />";
          } else {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "'  value='" . __( 'Add to Featured', ud_get_wp_property('domain') ) . "' />";
          }
        } else {
          $data = $featured ? __( 'Featured', ud_get_wp_property('domain') ) : '';
        }
        return $data;
      }

      /**
       * Return Thumnail
       *
       * @param $post
       * @return mixed|string
       */
      public function column_thumbnail( $post ) {

        $data = '';

        $wp_image_sizes = get_intermediate_image_sizes();
        $thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
        if( $thumbnail_id ) {
          foreach( $wp_image_sizes as $image_name ) {
            $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
            $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
          }
          $featured_image_id = $thumbnail_id;
        } else {
          $attachments  = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );
          if( $attachments ) {
            foreach( $attachments as $attachment_id => $attachment ) {
              $featured_image_id = $attachment_id;
              break;
            }
          }
        }
        if( empty( $featured_image_id ) ) {
          return $data;
        }

        $overview_thumb_type = ud_get_wp_property( 'configuration.admin_ui.overview_table_thumbnail_size' );

        if ( empty( $overview_thumb_type ) ) {
          $overview_thumb_type = 'thumbnail';
        }

        $image_large_obj = wpp_get_image_link( $featured_image_id, 'large', array( 'return' => 'array' ) );
        $image_thumb_obj = wpp_get_image_link( $featured_image_id, $overview_thumb_type, array( 'return' => 'array' ) );

        if ( !empty( $image_large_obj ) && !empty( $image_thumb_obj ) ) {
          $data = '<a href="' . $image_large_obj[ 'url' ] . '" class="fancybox" rel="overview_group" title="' . $post->post_title . '"><img src="' . $image_thumb_obj[ 'url' ] . '" width="' . $image_thumb_obj[ 'width' ] . '" height="' . $image_thumb_obj[ 'height' ] . '" /></a>';
        }

        return $data;
      }

      /**
       * Returns label for Title Column
       */
      public function get_column_title_label( $title, $post ) {
        $title = get_the_title( $post );
        if ( empty( $title ) )
          $title = __( '(no name)' );
        return $title;
      }

    }

  }

}