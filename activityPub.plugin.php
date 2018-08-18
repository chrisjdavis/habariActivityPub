<?php
namespace Habari;

class aPub extends Plugin
{
	public function filter_autoload_dirs($dirs) {
		$dirs[] = __DIR__ . '/classes';
		
		return $dirs;
	}
	
	public function action_init() {
		DB::register_table( 'inbox' );
		DB::register_table( 'outbox' );
		
		$this->add_templates();
	}
	
	private function add_templates() {
		$this->add_template( 'apub.profile', dirname(__FILE__) . '/templates/apub.profile.php' );
	}

	public function action_plugin_activated( $plugin_file ) {
		$this->create_activitypub_inbox();
		$this->create_activitypub_outbox();
	}
	
	private function create_activitypub_inbox() {
		$sql = "CREATE TABLE " . DB::table('inbox') . " (
				id int unsigned NOT NULL AUTO_INCREMENT,0',
				PRIMARY KEY (id),
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;";
			
		DB::dbdelta( $sql );
	}
	
	private function create_activitypub_outbox() {
		$sql = "CREATE TABLE " . DB::table('outbox') . " (
				id int unsigned NOT NULL AUTO_INCREMENT,0',
				PRIMARY KEY (id),
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;";
			
		DB::dbdelta( $sql );
	}
	
	public function filter_default_rewrite_rules( $rules ) {		
		$this->add_rule('"people"/username', 'display_apub_profile');

		return $rules;
	}
	
	# Get Following
	#
	# GET /v1/<username>/following
	#
	public function rest_get_v1__username_following($params) {
		$args['username'] = $params['username'];

		if( $this->passes( $args ) ) {
			$status = 200;
			$data = array();
			$ar = new AjaxResponse( $status, $data, null );
			$ar->out();
		} else {
			$status = 401;
			$ar = new AjaxResponse( $status, null, null );
			$ar->out();
		}
	}
	
	# Get Followers
	#
	# GET /v1/<username>/followers
	#
	public function rest_get_v1__username_followers($params) {
		$args['username'] = $params['username'];

		if( $this->passes( $args ) ) {
			$status = 200;
			$data = array();
			$ar = new AjaxResponse( $status, $data, null );
			$ar->out();
		} else {
			$status = 401;
			$ar = new AjaxResponse( $status, null, null );
			$ar->out();
		}
	}
	
	# Outbox
	#
	# POST /v1/<username>/outbox
	#
	public function rest_post_v1__username_outbox($params) {
		$args['username'] = $params['username'];

		if( $this->passes( $args ) ) {
			$status = 200;
			$data = array();
			$ar = new AjaxResponse( $status, $data, null );
			$ar->out();
		} else {
			$status = 401;
			$ar = new AjaxResponse( $status, null, null );
			$ar->out();
		}
	}
	
	# Outbox
	#
	# POST /v1/<username>/outbox
	#
	public function rest_post_v1__username_inbox($params) {
		$args['username'] = $params['username'];

		if( $this->passes( $args ) ) {
			$status = 200;
			$data = array();
			$ar = new AjaxResponse( $status, $data, null );
			$ar->out();
		} else {
			$status = 401;
			$ar = new AjaxResponse( $status, null, null );
			$ar->out();
		}
	}
	
	public function theme_route_apub_inbox($theme, $params) {
		$vars = $_GET;
		$params = array();
	}
	
	public function theme_route_apub_outbox($theme, $params) {
		$vars = $_GET;
		$params = array();
	}
	
	public function theme_route_display_apub_profile($theme, $params) {
		$theme->profile = User::get_by_name( $params['username'] );
		$theme->display( 'apub.profile' );
	}
}

?>