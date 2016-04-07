<?php namespace Sewa\Tileable;

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
}
