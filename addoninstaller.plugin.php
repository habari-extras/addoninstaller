<?php
namespace Habari;

class AddonInstaller extends Plugin
{
	public function action_init()
	{
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
			// Start processing the addons we were passed
			$payload = Session::get_set('install_addons', false);
			$data = $payload[0];
			foreach($data as $addon) {
				Session::notice(_t("You have %s v%s waiting for install", array($addon->name, $addon->version), __CLASS__));
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
}
?>