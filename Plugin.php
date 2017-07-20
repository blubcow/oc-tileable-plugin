<?php namespace Sewa\Tileable;

use App;
use Event;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

	public function registerFormWidgets()
	{
		return [
			'Sewa\Tileable\FormWidgets\TileRelation' => [
				'label' => 'TileRelation',
				'code'  => 'tilerelation'
			]
		];
	}
	
	public function boot()
	{
		// Check if we are currently in backend module.
	    if (!App::runningInBackend()) {
	        return;
	    }
		
		// add fileupload css
		//Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
	        //$controller->addCss('/modules/backend/formwidgets/fileupload/assets/css/fileupload.css');
	    //});
    }
	
}
