<?php
namespace Habari;

class aPub extends Plugin
{
	const EXECUTED = 1;
	const NOT_EXECUTED = 1;
	
	const ACTION_FOLLOW = 1;
	const ACTION_UNFOLLOW = 2;
	const ACTION_BLOCK = 3;
	const ACTION_LIKE = 4;
	const ACTION_NOTE = 5;
		
	public function filter_autoload_dirs($dirs) {
		$dirs[] = __DIR__ . '/classes';
		
		return $dirs;
	}
	
	public function action_init() {
		DB::register_table( 'inbox' );
		DB::register_table( 'outbox' );
		DB::register_table( 'liked' );
		
		$this->add_templates();
	}
	
	private function add_templates() {
		$this->add_template( 'apub.profile', dirname(__FILE__) . '/templates/apub.profile.php' );
	}

	public function action_plugin_activated( $plugin_file ) {
		$this->create_activitypub_inbox();
		$this->create_activitypub_outbox();
		$this->create_activitypub_liked();
	}
	
	# Create Inbox Table
	#
	# TODO: define what structure this table should have.
	#
	private function create_activitypub_inbox() {
		$sql = "CREATE TABLE {\$prefix}inbox (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `action` int(11) unsigned NOT NULL,
			  `raw_json` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			  `executed` int(11) unsigned NOT NULL,
			  `user_id` int(11) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		
		DB::dbdelta( $sql );
	}
	
	# Create Outbox Table
	#
	# TODO: define what structure this table should have.
	#
	private function create_activitypub_outbox() {
		$sql = "CREATE TABLE {\$prefix}outbox (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `action` int(11) unsigned NOT NULL,			  
			  `raw_json` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			  `executed` int(11) unsigned NOT NULL,
			  `user_id` int(11) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
			
		DB::dbdelta( $sql );
	}

	# Create Liked Table
	#
	# TODO: define what structure this table should have.
	#
	private function create_activitypub_liked() {
		$sql = "CREATE TABLE {\$prefix}liked (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `raw_json` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			  `executed` int(11) unsigned NOT NULL,
			  `user_id` int(11) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
			
		DB::dbdelta( $sql );
	}
	
	public function filter_default_rewrite_rules( $rules ) {		
		$this->add_rule('"people"/username', 'display_apub_profile');

		return $rules;
	}
	
	/**
	 * insert
	 * 
	 * @access private
	 * @param mixed array $data
	 * @param string $table_name
	 * @return void
	 */
	private static function insert(array $data, string $table_name) {
		$insert = DB::insert( DB::table( $table_name ), $data );
		return DB::last_insert_id();
	}
	
	/**
	 * passes_muster
	 * 
	 * @access private
	 * @param mixed array $args
	 * @return bitwise
	 */
	private function passes_muster( array $args ) {
		// Do something clever for the security times. In the meantime,
		// we'll pretend its 1935 in Maybury. We trust everyone!
		
		return true;
	}
	
	private function save_to_file($data) {
		file_put_contents( __DIR__ . '/logs/test.txt', print_r($data, true) );
	}

	/**
	 * rest_get_v1__username_following
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example GET /v1/<username>/following
	 * @return null
	 */
	public function rest_get_v1__username_following($params) {}
	
	/**
	 * rest_get_v1__username_following
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example GET /v1/<username>/followers
	 * @return null
	*/
	public function rest_get_v1__username_followers($params) {}
	
	/**
	 * rest_post_v1__username_feed
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example POST /v1/<username>/feed
	 * @return null
	 */
	public function rest_post_v1__username_feed($params) {
		$person = User::get_by_name( $params['username'] ); $i = 0;
		
		$ins = self::insert( array('raw_json' => $json, 'executed' => 0, 'user_id' => $person->id), DB::table('outbox') );
		
		$args = json_decode( $json );
		
		switch( $args->{'@type'} ) {
			case 'Follow' :					
				$url = $args->{'to'}[0]->{'@id'};
				
				$rr = new RemoteRequest( $url );
				$rr->execute();
				
				$response = $rr->get_response_body();
				$tokenizer = new HTMLTokenizer( $response );
				
				$deets = $tokenizer->parse();

				foreach( $deets as $deet ) { $i++;
					if( isset($deet['attrs']['type']) && $deet['attrs']['type'] == 'application/ld+json' ) {
						$inbox = json_decode( $deets[$i++]['value'] )->inbox;
						
						$req = new RemoteRequest( $inbox, 'GET' );
						$req->set_params( array('payload' => $json) );
						$req->execute();
					}
				}
			break;
		}
		
	}
	
	/**
	 * rest_get_v1__username_feed
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example GET /v1/<username>/feed
	 * @return null
	 */
	public function rest_get_v1__username_feed($params) {}
	
	/**
	 * rest_post_v1__username_inbox
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example POST /v1/<username>/inbox
	 * @return null
	 */
	public function rest_post_v1__username_inbox($params) {
		$i = 0; $vars = $_POST; $person = User::get_by_name( $params['username'] );
		
		$payload = html_entity_decode( $_GET['payload'] );
		$decoded = json_decode( $payload );
		
		$this->save_to_file( $decoded );
		
		$ins = self::insert( array('raw_json' => $payload , 'executed' => 0, 'user_id' => $person->id), DB::table('inbox') );
		
		exit();
		
		switch( $args->{'@type'} ) {
			case 'Follow' :
			break;
		}
	}
	
	/**
	 * rest_get_v1__username_inbox
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example GET /v1/<username>/inbox
	 * @return null
	 */
	public function rest_get_v1__username_inbox($params) {}
	
	/**
	 * rest_get_v1__username_liked
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example GET /v1/<username>/liked
	 * @return null
	 */
	public function rest_get_v1__username_liked($params) {}
	
	/**
	 * rest_post_v1__username_liked
	 * 
	 * @access public
	 * @param mixed array $params
	 * @example POST /v1/<username>/inbox
	 * @return null
	 */
	public function rest_post_v1__username_liked($params) {}
	
	public function theme_route_display_apub_profile($theme, $params) {
		$theme->profile = User::get_by_name( $params['username'] );
		
		$theme->display( 'apub.profile' );
	}
}

?>