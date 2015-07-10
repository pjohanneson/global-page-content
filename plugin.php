<?php 

/*
Plugin Name: Global Privacy Page
Description: Add a 'virtual' privacy page on all sites
*/

// Modified from this source: http://davejesch.com/2012/12/creating-virtual-pages-in-wordpress/
 
if ( ! class_exists( 'Global_Privacy_Page') ) :

class Global_Privacy_Page {
    private $slug = NULL;
    private $title = NULL;
    private $content = NULL;
    private $author = NULL;
    private $date = NULL;
    private $type = NULL;

    public function __construct($args) {
        if (!isset($args['slug']))
            throw new Exception('No slug given for virtual page');

        $this->slug = $args['slug'];
        $this->title = isset($args['title']) ? $args['title'] : '';
        $this->content = isset($args['content']) ? $args['content'] : '';
        $this->author = isset($args['author']) ? $args['author'] : 1;
        $this->date = isset($args['date']) ? $args['date'] : current_time('mysql');
        $this->dategmt = isset($args['date']) ? $args['date'] : current_time('mysql', 1);
        $this->type = isset($args['type']) ? $args['type'] : 'page';

        add_filter('the_posts', array(&$this, 'virtual_page'));
    }

    // filter to create virtual page content
    public function virtual_page($posts) {
        global $wp, $wp_query;

        if ( 
          count( $posts ) == 0 
          && (
              strcasecmp( $wp->request, $this->slug ) == 0 
              || $wp->query_vars['page_id'] == $this->slug
            )
          ) {
            //create a fake post intance
            $post = new stdClass();
            // fill properties of $post with everything a page in the database would have
            $post->ID = -1;                          // use an illegal value for page ID
            $post->post_author = $this->author;       // post author id
            $post->post_date = $this->date;           // date of post
            $post->post_date_gmt = $this->dategmt;
            $post->post_content = $this->content;
            $post->post_title = $this->title;
            $post->post_excerpt = '';
            $post->post_status = 'publish';
            $post->comment_status = 'closed';        // mark as closed for comments, since page doesn't exist
            $post->ping_status = 'closed';           // mark as closed for pings, since page doesn't exist
            $post->post_password = '';               // no password
            $post->post_name = $this->slug;
            $post->to_ping = '';
            $post->pinged = '';
            $post->modified = $post->post_date;
            $post->modified_gmt = $post->post_date_gmt;
            $post->post_content_filtered = '';
            $post->post_parent = 0;
            $post->guid = get_home_url( '/' . $this->slug );
            $post->menu_order = 0;
            $post->post_tyle = $this->type;
            $post->post_mime_type = '';
            $post->comment_count = 0;

            // set filter results
            $posts = array($post);

            // reset wp_query properties to simulate a found page
            $wp_query->is_page = TRUE;
            $wp_query->is_singular = TRUE;
            $wp_query->is_home = FALSE;
            $wp_query->is_archive = FALSE;
            $wp_query->is_category = FALSE;
            unset($wp_query->query['error']);
            $wp_query->query_vars['error'] = '';
            $wp_query->is_404 = FALSE;
        }

        return $posts;
    }
}

endif;  // ! class_exists()
 
function global_privacy_page() {
  
  if ( BLOG_ID_CURRENT_SITE === get_current_blog_id() ) {
    return;
  }
  
  $wanted_url = trailingslashit( 'privacy-policy' );

  $url = trailingslashit( $_SERVER['REQUEST_URI'] );

  if ( $url == trailingslashit( get_blog_details( get_current_blog_id() )->path . 'privacy-policy') ) {

    switch_to_blog( BLOG_ID_CURRENT_SITE );
    $pp = get_page_by_title( 'Privacy Policy' );
    if( ! $pp ) {
      $pp = new stdClass();
      $pp->post_title = 'No Privacy Policy found';
      $pp->post_content = 'There is no Privacy Policy set up yet.';
      if( current_user_can( 'edit_posts' ) ) {
        $pp->post_content .= '<br />Please add a page with the title <strong>Privacy Policy</strong> to the main website (' . get_home_url() . ').';
      }
    }

    restore_current_blog();

    $args = array( 
      'slug' => 'privacy-policy',
      'title' => $pp->post_title,
      'content' => $pp->post_content,
    );

    $pg = new Global_Privacy_Page($args);
  }
}
add_action('init', 'global_privacy_page');