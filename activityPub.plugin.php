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
	const ACTION_POST = 5;
	const ACTION_NOTE = 6;
		
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
	
	private function create_payload($type, $actor, $to, $public = true) {	
		$data = [];
		$data['@context'] = "http://www.w3.org/ns/activitystreams";
		
		switch( $type ) {
			case self::ACTION_FOLLOW :
				$data['@type'] = "Follow";
				$data['object'] = array( "@type" => "Person", "@id" => URL::get( "display_apub_profile", array("username" => $to->username)) );
				$data['to'] = array( array("@type" => "Person", "@id" => URL::get( "display_apub_profile", array("username" => $to->username))) );
			break;
			case ACTION_UNFOLLOW :
				$data['@type'] = "Unfollow";
				$data['object'] = array( "@type" => "Person", "@id" => URL::get( "display_apub_profile", array("username" => $to->username)) );
				$data['to'] = array( array("@type" => "Person", "@id" => URL::get( "display_apub_profile", array("username" => $to->username))) );
			break;
			case ACTION_BLOCK :
			break;
			case ACTION_LIKE :
			break;
			case ACTION_POST :
				$data['@type'] = "Post";
			break;			
			case ACTION_NOTE :
			break;
		}
		
		$data['actor'] = array( "@type" => "Person", "@id" => URL::get( "display_apub_profile", array("username" => $actor->username) ), "displayName" => $actor->info->displayname );
		
		if( $public == true ) {
			$data['cc'] = array( array( "@context" => "http://www.w3.org/ns/activitystreams", "@id" => "http://activityschema.org/collection/public", "@type" => "Collection") );
		}
		
		return json_encode($data );
	}
	
	/**
	 * follow
	 * 
	 * @access private
	 * @param string $actor
	 * @param string $to_url
	 * @example $this->follow( 1, https://example.com/v1/veronica/inbox );
	 */
	private function follow($actor, $to) {
		$req = new RemoteRequest( URL::get( 'v1_usernamefeed', array('username' => $actor->username) ), 'POST' );
		
		$json = $this->create_payload( self::ACTION_FOLLOW, $actor, $to, true );
		
		$vars = array(
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
			);
		
		$req->add_header( $vars );
		
		$req->set_params( array('payload' => $json) );
		$req->execute();
		
		$response = $req->get_response_body();
		
		var_dump( $response ); exit();
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
		
		$json = html_entity_decode( $_GET['payload'] );
		
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
		
		$this->save_to_file( $payload );
		
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
	
	/**
	 * theme_route_display_apub_profile
	 * 
	 * @access public
	 * @param mixed array $theme
	 * @param mixed array $params
	 * @return null
	 */
	public function theme_route_display_apub_profile($theme, $params) {
		$theme->profile = User::get_by_name( $params['username'] );
		
		$theme->display( 'apub.profile' );
	}
	
	public function action_auth_ajax_follow() {
		$vars = $_POST; $user = User::identify(); $to = User::get_by_id( $vars['user_id'] );
		
		try {
			$this->follow( $user, $to );
			$ar = new AjaxResponse( 200, 'Follow Successful', '' );
			$ar->html( '#follow_form', '#' );
		} catch( Exception $e ) {
			$ar = new AjaxResponse( 500, 'Follow Unsuccessful', '' );
		}
		
		$ar->out();
	}
	
    public function action_auth_ajax_verify($data) {
		if( isset($_GET['nonce']) ) {
			$vars = $_GET;
		} else {
			$vars = $_POST;
		}
		
		if( !$verified = Utils::verify_wsse($vars) ) {
			die('{error: "WSSE Failure"}');
		}
    }
	
	public function action_auth_ajax_wsse_update() {
		$ar = new AjaxResponse(200, null, Utils::WSSE());
		$ar->out();
	}
	
	public static function is_following($actor, $to) {
		return false;
	}
}

?>