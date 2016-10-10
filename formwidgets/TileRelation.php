<?php namespace Sewa\Tileable\FormWidgets;

use Db;
use Lang;
use Backend\Classes\FormWidgetBase;
use ApplicationException;
use SystemException;
use Illuminate\Database\Eloquent\Relations\Relation as RelationBase;
use Sewa\Tileable\Widgets\TileList;

/**
 * Form Relationship
 * Renders a field prepopulated with a belongsTo and belongsToHasMany relation.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class TileRelation extends FormWidgetBase
{
    /**
     * @var const Postback parameter for the active relationship field.
     * TODO: do we need this?
     */
    //const PARAM_SLUG = '_relation_field';
    
    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - list: List column definitions
     */
    protected $requiredConfig = ['list'];
    
    //
    // Object properties
    //

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'sewa_tileable_tilerelation';
    
    /**
     * list alias slug
     */
    protected $listSlug;
    
    /**
     * list widget
     */
    protected $listWidget = null;
    
    /**
     * list config
     */
    protected $listConfig = null;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        // get list config
        // in a matter similar to the TileListController
        $this->listConfig = $this->makeConfig($this->config->list, $this->requiredConfig);
        $this->listConfig->columns = $this->makeConfig($this->listConfig->list)->columns;
        
        // create a slug based on the form field
        // TODO: check if we really need a list slug for alias,
        // 		 it seems this widget has already a field slug in it
        //$this->listSlug = post(self::PARAM_SLUG) ? post(self::PARAM_SLUG) : $this->fieldName;
        
        // create the list widget
        // we create it so soon for the AJAX calls to catch 'em all
        // TODO: check if initialization is really necessary so soon
        $this->listWidget = $this->makeTileListWidget();
    }
    
    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        //$this->addCss('../../../assets/css/tileable-tilerelation-widget.css', 'sewa.Tileable');
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        return $this->listWidget->render();
    }
    
    /**
     * Make widget
     */
    protected function makeTileListWidget()
    {
        $this->listConfig->model = $this->getRelationChildModel();
        
        if(!isset($this->listConfig->relation)){
            $this->listConfig->relation = array(
                    'field' => $this->fieldName,
                    'class' => get_class($this->model),
                    'id' => $this->model->id
                );
        }
        
        //$this->listConfig->alias = $this->alias . '_tileList' . '_'.$this->listSlug;
        $this->listConfig->alias = $this->alias . '_tileList';
        
        $this->listConfig->checkboxName = $this->formField->getName();
        
        $listWidget = $this->makeWidget('Sewa\Tileable\Widgets\TileList', $this->listConfig);
        $listWidget->bindToController();
        return $listWidget;
    }
    
    /**
     * TODO: why do we need this?
     * some time ago there was an error without it
     */
    public function getRelationList($model)
    {
        
    }

    /**
     * change active relations
     */
    public function getSaveValue($value)
    {
        if (is_string($value) && !strlen($value)) {
            return null;
        }

        if (is_array($value) && !count($value)) {
            return null;
        }
        
        if (!$value) return null;
        
        //
        $activeRelations = $this->formField->value;
        $visibleRelations = $this->listWidget->getVisibleRecords();
        $newRelations = $value;
        
        // insert active relations, which are not visible, into new relations
        if(is_array($activeRelations) && (count($activeRelations) > 0))
        {
            foreach($activeRelations as $k => $v)
            {
                if(!in_array(intval($v), $visibleRelations)){
                    array_push($newRelations, intval($v));
                }
            }
        }
        
        //
        return $newRelations;
    }


    /**
     * Returns the value as a relation object from the model,
     * supports nesting via HTML array.
     * @return Relation
     */
    protected function getRelationObject()
    {
        // TODO: why did we need this before?
        // After an update, those function "resolveModelAttribute" went to Backend\Classes\FormField
        // Also, check how we can push back an system update
        /*
        list($model, $attribute) = $this->resolveModelAttribute($this->valueFrom);
        
        if (!$model->hasRelation($attribute)) {
            throw new ApplicationException(Lang::get('backend::lang.model.missing_relation', [
                'class' => get_class($model),
                'relation' => $attribute
            ]));
        }
        
        return $model->{$attribute}();
        */
        
        // check if this widget in fact represents an relation
        if (!$this->model->hasRelation($this->valueFrom)) {
            throw new ApplicationException(Lang::get('backend::lang.model.missing_relation', [
                'class' => get_class($this->model),
                'relation' => $this->valueFrom
            ]));
        }
        
        //
        return $this->model->{$this->valueFrom}();
    }
    
    /**
     * 
     */
    protected function getRelationChildModel()
    {
        return $this->getRelationObject()->getRelated();
    }
    
    /**
     * 
     */
    protected function getRelationChildClass()
    {
        return get_class($this->getRelationObject()->getRelated());
    }

}
