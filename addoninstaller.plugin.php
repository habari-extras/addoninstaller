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
	
	/** @var array $_types Subdirectories for default addon types */
	protected $_type_subdirs = array(
		'theme' => 'themes',
		'plugin' => 'plugins',
		'locale' => 'locale',
	);
	
	/**
	 * Plugin initialization. Add our templates and rules so they can be used.
	 */
	public function action_init()
	{
		$this->add_template( 'addon_preview', dirname(__FILE__) . '/templates/addon_preview.php' );
		$this->add_template( 'addon', dirname(__FILE__) . '/templates/addon.php' );
		
		$this->add_rule('"retrieve_addonlist"', 'retrieve_addonlist');
		$this->add_rule('"install_addons"', 'install_addons');
	}
	
	/**
	 * Grab calls to yoursite.tld/install_addons
	 * This is where the catalog redirects to
	 */
	public function theme_route_install_addons($theme, $params)
	{
		if(isset($_POST['payload']) && !empty($_POST['payload'])) {
			$payload = json_decode($_POST->raw('payload'));
			foreach($payload as $addondata) {
				Session::add_to_set('install_addons', $addondata, $addondata->slug);
			}
		}
		
		if(!User::identify()->loggedin) {
			Session::notice('You have to login to install addons');
			Utils::redirect(Site::get_url('login'));
		}
		else {
			$this->notice_installation();
		}

		Utils::redirect(Site::get_url("admin"));
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
	 * Show notification about new addons on dashboard
	 */
	public function action_admin_theme_get_dashboard($handler, $theme)
	{
		if(isset($_SESSION['install_addons'])) {
			$this->notice_installation();
		}
	}
	
	/**
	 * Prepare the template for the new pages we added in filter_adminhandler_post_loadplugins_main_menu
	 * It will automatically be included by the filename
	 */
	public function action_admin_theme_get_addon_preview($handler, $theme)
	{
		// Notify about problems with the tempdir
		$tmpdir = $this->tempdir();
		if(!$tmpdir) {
			Session::error(_t("You have no writable temporary directory. Your webserver needs to have write access to either your server's tempdir or Habari's user directory."));
		}
		
		$data = Session::get_set('install_addons', false);
		
		// Check if we need to do something before displaying the list
		$action = (isset($handler->handler_vars["action"])) ? $handler->handler_vars["action"] : "";
		switch($action)
		{
			case "install":
				$addon = $handler->handler_vars["addon"];
				if(!array_key_exists($addon, $data)) {
					// What did you pass us there?
					Session::error("There was a problem handling the addon identifier. Maybe you used a wrong link?");
					break;
				}
				
				// Get the addon!
				$request = new RemoteRequest($data[$addon]->download_url);
				$request->execute();
				file_put_contents($tmpdir . "/download.zip", $request->get_response_body());
				
				// Extract it to the real directory
				$zip = new \ZipArchive();
				$zipstatus = $zip->open($tmpdir . "/download.zip");
				if($zipstatus === true) {
					$addonpath = Site::get_dir("user") . '/' . $this->_type_subdirs[$data[$addon]->type] . '/' . $data[$addon]->name;
					if(!is_dir($addonpath)) {
						mkdir($addonpath);
					}
					if($zip->extractTo($addonpath)) {
						$this->remove_from_set($addon);
						Session::notice("Plugin now available for activation");
						Utils::redirect(URL::get( 'admin', array("page" => "addon_preview")));
					}
					else {
						Session::error("There was a problem extracting the zip file. Maybe your user directory is no writable?");
					}
					$zip->close();
				}
				else {
					switch($zipstatus) {
						case \ZipArchive::ER_NOZIP:
							Session::error(_t("There was a problem retrieving the zip file"));
							break;
						default:
							Session::error(_t("There was a problem opening the zip file"));
							break;
					}
				}
				break;
		}
		
		// Pass the addon list to the theme
		$addons = array();
		foreach($data as $addon) {
			// Insert other checks here
			$addon->habari_compatible = (version_compare(Version::get_habariversion(), $addon->habari_version) == -1) ? false : true;
			if($addon->habari_compatible && $tmpdir) {
				$actions = array("install" => URL::get( 'admin', array("page" => "addon_preview", "action" => "install", "addon" => $addon->slug)));
				$addon->actions = $actions;
			}
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
	
	/**
	 * Helper function to easily get rid of a single entry in the Session
	 */
	function remove_from_set($slug)
	{
		$data = Session::get_set("install_addons");
		foreach($data as $index => $entry) {
			if($index == $slug) {
				continue;
			}
			$Session::add_to_set("install_addons", $entry, $index);
		}
	}
	
	/**
	 * Helper function to avoid multiple places where one translated string is used
	 */
	function notice_installation()
	{
		Session::notice(_t("You have addons ready for installation.", __CLASS__) . " <a href='" . URL::get( 'admin', array("page" => "addon_preview")) . "'>" . _t("Go to list", __CLASS__) . "</a>", 'addons_installnotice');
	}
}
?>