<?php
namespace Habari;

class AddonInstaller extends Plugin
{
	/** @var array $_types Default addon types */
	protected $_types = array(
		'theme' => 'Themes',
		'plugin' => 'Plugins',
		'locale' => 'Locales',
		'core' => 'Core',
	);
	
	public function action_init()
	{
		$this->add_template( 'addon_preview', dirname(__FILE__) . '/templates/addon_preview.php' );
		$this->add_template( 'addon', dirname(__FILE__) . '/templates/addon.php' );
		
		$this->add_rule('"retrieve_addonlist"', 'retrieve_addonlist');
		$this->add_rule('"install_addons"', 'install_addons');
	}
	
	public function theme_route_install_addons($theme, $params)
	{
		if(isset($_POST['payload']) && !empty($_POST['payload'])) {
			Session::add_to_set('install_addons', json_decode($_POST->raw('payload')));
		}
		
		if(!User::identify()->loggedin) {
			Session::notice('You have to login to install addons');
			Utils::redirect(Site::get_url('login'));
		}
		else {
			// Tell the user we have a party to start
			$payload = Session::get_set('install_addons', false);
			if(isset($payload)) {
				Session::notice(_t("You have new addons to install.", __CLASS__));
			}
		}
		Utils::redirect(Site::get_url("admin"));
	}
	
	public function filter_login_redirect_dest($login_dest, $user, $login_session)
	{
		$data = Session::get_set('install_addons', false);
		if(isset($data) && !empty($data)) {
			// Only redirect when we caused it
			$login_dest = Site::get_url('habari') . '/install_addons';
		}
		return $login_dest;
	}
	
	/**
	 * Add a menu element for the addon list
	 * http://wiki.habariproject.org/en/Dev:Adding_an_Admin_Page
	 */
	public function filter_adminhandler_post_loadplugins_main_menu( array $menu )
	{
		$item_menu = array( 'addon_preview' => array(
			'url' => URL::get( 'admin', 'page=addon_preview'),
			'title' => _t('Preview available addons and install them'),
			'text' => _t('Install Addons'),
			'hotkey' => 'S',
			'selected' => false
		) );
	 
		$slice_point = array_search( 'users', array_keys( $menu ) ); // Element will be inserted before "users"
		$pre_slice = array_slice( $menu, 0, $slice_point);
		$post_slice = array_slice( $menu, $slice_point);
	 
		$menu = array_merge( $pre_slice, $item_menu, $post_slice );
	 
		return $menu;
	}
	
	/**
	 * Limit access to the new menu entries by using existing ACL tokens
	 * If the user can either manage plugins or themes he may view the list of addons
	 */
	public function filter_admin_access_tokens( array $require_any, $page )
	{
		switch ($page)
		{
			case 'addon_preview':
				$require_any = array( 'manage_plugins' => true, 'manage_theme' => true );
				break;			
		}
		return $require_any;
	}
	
	/**
	 * Prepare the template for the new pages we added in filter_adminhandler_post_loadplugins_main_menu
	 * It will automatically be included by the filename
	 */
	public function action_admin_theme_get_addon_preview($handler, $theme)
	{
		$tmpdir = $this->tempdir();
		if(!$tmpdir) {
			Session::error(_t("You have no writable temporary directory. Your webserver needs to have write access to either your server's tempdir or Habari's user directory."));
		}
		
		// Pass the addon list to the theme
		$payload = Session::get_set('install_addons', false);
		$data = $payload[0];
		$addons = array();
		foreach($data as $addon) {
			$addons[$addon->type][] = $addon;
		}
		
		$theme->addon_types = $this->_types;
		$theme->addons = $addons;
	}
	
	/**
	 * Determine a usable temporary directory
	 */
	function tempdir()
	{
		if(is_writable(sys_get_temp_dir())) {
			return sys_get_temp_dir();
		}
		else if(is_writable(Site::get_dir('user'))) {
			if(is_dir(Site::get_dir('user') . "/hpm_tmp")) {
				return Site::get_dir('user') . "/hpm_tmp";
			}
			else {
				mkdir(Site::get_dir('user') . "/hpm_tmp", 0755, true);
				if(is_dir(Site::get_dir('user') . "/hpm_tmp")) {
					return Site::get_dir('user') . "/hpm_tmp";
				}
				else return false;
			}
		}
		else return false;
	}
}
?>