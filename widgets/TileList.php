<?php namespace Sewa\Tileable\Widgets;

use Backend\Classes\WidgetBase;
use Backend\Widgets\Lists;
use Event;
use DbDongle;
use Db;
use Request;
use Backend;
use System\Models\File;
use Form as FormHelper;
use Input;
use Validator;
use Lang;
use ApplicationException;

class TileList extends Lists
{
    
    protected $defaultAlias = 'tileList';
    
    //
    // Configurable properties
    //
    
    /**
     * @var bool Show thumbnail
     */
    public $showPreview = false;
    
    /**
     * @var string Attachment from which to load the thumbnail
     */
    public $previewFrom = 'avatar';
    
    /**
     * @var bool Show view switch in header
     */
    public $showViews = false;
    
    /**
     * @var array List all selectable view types (lg, md, sm, xs)
     */
    public $views = ['lg', 'md', 'sm', 'xs'];
    
    /**
     * @var string Default view type
     */
    public $defaultView = 'md';
    
    /**
     * @var bool Show upload button in header
     */
    public $showUpload = false;
    
    /**
     * @var string The attachment name the file should be uploaded to
     */
    public $uploadTo = null;
    
    /**
     * @var array Fill new models with values from uploaded attachment
     */
    public $uploadFill = [
        'name' => 'file_name',
        'slug' => 'file_name'
    ];
    
    /**
     * @var bool Show search field in header
     */
    public $showSearch = false;
    
    /**
     * 
     */
    public $showDelete = false;
    
    /**
     * @var string|bool Partial to integrate into header
     * ! This partial will be relative to the controller, not our widget !
     */
    public $headerPartial = false;
    
    /**
     * @var array Relation parent parameters
     * If the list is related to some model, put in the values for our parent.
     * In forms (initialized by FormWidget) just use the TileListRelation Form Widget.
     * Structure = ['field'=>'', 'class'=>'', 'id'=>'']
     */
    public $relation = null;
    
    /**
     * 
     */
    public $checkboxName = 'checked';
    
    /**
     * 
     */
    public $createRecordUrl = null;
    
    /**
     * 
     */
    public $relationSort = null;
    
    //
    // Object properties
    //
    
    /**
     * @var mixed SearchWidget
     */
    protected $searchWidget;
    
    /**
     * Initialize the widget, called by the constructor and free from its parameters.
     */
    public function init()
    {
        parent::init();
        
        $this->fillFromConfig([
            'showPreview',
            'previewFrom',
            'showViews',
            'views',
            'defaultView',
            'showUpload',
            'uploadTo',
            'uploadFill',
            'showSearch',
            'showDelete',
            'headerPartial',
            'relation',
            'checkboxName',
            'createRecordUrl',
            'relationSort'
        ]);
        
        //
        $this->makeCurrentPageNumber();
        
        // create the search widget
        // we create it so soon for the AJAX calls to catch 'em all
        // TODO: check if initialization is really necessary so soon
        $this->searchWidget = $this->showSearch ? $this->makeSearchWidget() : null;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->addCss('../../../assets/css/tileable.css');
        //$this->addCss('../../../assets/css/tileable-tilelist-widget.css');
        $this->addJs('../../../assets/js/Sortable.js');
        $this->addJs('../../../assets/js/tileable.tilelist.js');
        
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('list-container');
    }
    
    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        
        
        if($this->getSortColumn() == $this->relationSort){
            $this->sortDirection = 'asc';
        }
        
        parent::prepareVars();
        
        /*
         * PARENT VARS
         * 
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
        $this->vars['columns'] = $this->getVisibleColumns();
        $this->vars['columnTotal'] = $this->getTotalColumns();
        $this->vars['records'] = $this->getRecords();
        $this->vars['noRecordsMessage'] = trans($this->noRecordsMessage);
        $this->vars['showCheckboxes'] = $this->showCheckboxes;
        $this->vars['showSetup'] = $this->showSetup;
        $this->vars['showPagination'] = $this->showPagination;
        $this->vars['showSorting'] = $this->showSorting;
        $this->vars['sortColumn'] = $this->getSortColumn();
        $this->vars['sortDirection'] = $this->sortDirection;
        $this->vars['showTree'] = $this->showTree;
        $this->vars['treeLevel'] = 0;

        if ($this->showPagination) {
            $this->vars['recordTotal'] = $this->records->total();
            $this->vars['pageCurrent'] = $this->records->currentPage();
            $this->vars['pageLast'] = $this->records->lastPage();
            $this->vars['pageFrom'] = $this->records->firstItem();
            $this->vars['pageTo'] = $this->records->lastItem();
        }
        else {
            $this->vars['recordTotal'] = $this->records->count();
            $this->vars['pageCurrent'] = 1;
        }
        */
        
        //print_r($this->vars['records']);
        //exit();
        
        $this->vars['showPreview'] = $this->showPreview;
        
        $this->vars['activeRecords'] = $this->getRelationRecordIds();
        $this->vars['allColumns'] = $this->getColumns();
        $this->vars['columnTypes'] = $this->getColumnTypes();
        $this->vars['columnValues'] = $this->getColumnValues();
        
        $this->vars['isRelation'] = $this->relation ? true : false;
        $this->vars['relationField'] = $this->getRelationField(); // needed for alias slug (TileListRelation)
        
        $this->vars['showViews'] = $this->showViews;
        $this->vars['views'] = $this->views;
        $this->vars['defaultView'] = $this->defaultView;
        $this->vars['activeView'] = $this->getActiveView();
        
        $this->vars['showUpload'] = $this->showUpload;
        $this->vars['showDelete'] = $this->showDelete;
        
        $this->vars['checkboxName'] = $this->checkboxName;
        
        // prepare sortable
        if($this->getSortColumn() == $this->relationSort){
            //$this->vars['records'] = $this->getRelationRecords();
            //$this->vars['sortDirection'] = 'asc';
            $this->vars['sortable'] = true; 
        }else{
            $this->vars['sortable'] = false; 
        }
        
        
        
        // header partial
        // TODO: check if ajax events are really propagated to our controller
        // maybe we can just add the partial inside the template?
        $this->vars['headerPartial'] = $this->makeHeaderPartial();
        
        $this->vars['createRecordUrl'] = $this->createRecordUrl;
        
        // search widget
        $this->vars['showSearch'] = $this->showSearch;
        $this->vars['searchWidget'] = $this->searchWidget ? $this->searchWidget->render() : null;
    }

    /**
     * Get search widget
     */
    protected function makeSearchWidget()
    {
        $config = $this->makeConfig();
        $config->alias = $this->alias . '_search';
        $config->growable = true;
        $config->prompt = 'backend::lang.list.search_prompt';
        $searchWidget = $this->makeWidget('Backend\Widgets\Search', $config);
        //$searchWidget->cssClasses[] = 'recordfinder-search';
        
        /*
         * Persist the search term across AJAX requests only
         */
        if(!Request::ajax()){
            $searchWidget->setActiveTerm(null);
        }
        
        /*
         * Link the Search Widget to the TileList Widget
         */
        if($searchWidget){
            $searchWidget->bindEvent('search.submit', function(){
                return $this->onSearchSubmit();
            });
            $this->setSearchTerm($searchWidget->getActiveTerm());
        }
        
        $searchWidget->bindToController();
        return $searchWidget;
    }
    
    /**
     * Get header partial, relative to controller
     */
    protected function makeHeaderPartial()
    {
        if($this->headerPartial){
            return $this->controller->makePartial( $this->headerPartial );
        }else{
            return null;
        }
    }
    
    /**
     * refresh BODY
     */
    public function onRefreshBody()
    {
        $this->prepareVars();
        return ['#'.$this->getId().' #list_body' => $this->makePartial('list_body')];
    }
    
    /**
     * refresho RECORD
     */
    public function onRefreshRecord($id)
    {
        $this->prepareVars();
        $record = $this->getRecord($id);
        return ['#'.$this->getId().' .record#'.$id => $this->makePartial('list_body_row', ['record' => $record])];
    }

    /**
     * Event handler for switching the page number.
     */
    public function onPaginate()
    {
        $this->currentPageNumber = post('page');
        //return $this->onRefresh();
        return $this->onRefreshBody();
    }
    
    //
    // EVENTS #########################################################################################
    // 
    //
    
    
    /**
     * 
     */
    public function onApplySetup()
    {
        if (($visibleColumns = post('visible_columns')) && is_array($visibleColumns)) {
            $this->columnOverride = array_keys($visibleColumns);
            $this->putSession('visible', array_keys($visibleColumns));
        }

        $this->putSession('order', post('column_order'));
        $this->putSession('per_page', post('records_per_page', $this->recordsPerPage));
        
        //
        // ADDED
        $this->recordsPerPage = $this->getSession('per_page');
        
        return $this->onRefresh();
    }
    
    /**
     * 
     */
    public function onSearchSubmit()
    {
        $this->setSearchTerm( $this->searchWidget->getActiveTerm() );
        
        $this->currentPageNumber = 1;
        
        return $this->onRefreshBody();
    }
    
    /**
     * Event handler for sorting the list.
     */
    public function onSort()
    {
        //if ($column = post('sortColumn')){
        if ($column = post($this->getFieldName('sortColumn'))) {
            
            /*
             * Set the sorting column
             */
            $sortOptions = ['column' => $this->getSortColumn(), 'direction' => $this->sortDirection];

            $this->sortColumn = $sortOptions['column'] = $column;

            $this->putSession('sort', $sortOptions);

            return $this->onRefresh();
        }
    }
    
    /**
     * 
     */
    public function onSortDirection()
    {
        /*
         * Toggle the sort direction
         */
        $sortOptions = ['column' => $this->getSortColumn(), 'direction' => $this->sortDirection];

        if ($sortOptions['direction'] == 'asc') {
            $this->sortDirection = $sortOptions['direction'] = 'desc';
        }
        else {
            $this->sortDirection = $sortOptions['direction'] = 'asc';
        }
        
        $this->putSession('sort', $sortOptions);
        
        return $this->onRefresh();
    }
    
    /**
     * 
     */
    public function onView()
    {
        if ($activeView = post('view'))
        {
            $this->putSession('view', $activeView);
            
            // return 
            return $this->onRefresh();
        }
    }
    
    /**
     * TODO: delete_model is just an ID, change all the variables so we know if its an id
     */
    public function onDeleteRecord()
    {
        $recordId = post('delete_model');
        $record = $this->model->find($recordId);
        if($record) $record->delete();
        
        //
        return $this->onRefreshBody();
    }
    
    
    
    
    /**
     * ################################################################################
     * Sort Records
     */
    
    
    
    public function onSortRecords()
    {
        // array [recordID => orderIndex]
        $sortOrder = post('sortOrder');
        // minimum order index in array
        $minSortOrder = intval(min($sortOrder));
        
        //
        // respect paginator minimum order index
        // calc by page
        //print_r($sortOrder);
        $minOrder = max(0, $this->recordsPerPage * ($this->currentPageNumber - 1)) + 1;
        // correct difference (add indexes to each record)
        if($minOrder > $minSortOrder){
            $diff = $minOrder - $minSortOrder;
            //echo($diff."\n");
            array_walk($sortOrder, function(&$value, $key)use($diff) { $value += $diff; });
        }
        //print_r($sortOrder);
        
        //
        // update model
        if(count($sortOrder) > 0){
            //DB::enableQueryLog();
            foreach ($sortOrder as $recordId => $index) {
                
                //echo($this->getSortColumn().' - '.$index."\n");
                //$this->model->find($recordId)->update([$this->getSortColumn() => $index]);
                
                
                $this->sortRecord($this->model->find($recordId), $index);
                
                
                
            }
            //print_r(DB::getQueryLog());
        }
        
        return $this->onRefreshBody();
    }
    
    
    public function sortRecord($record, $index)
    {
        //
        //print_r($record->toArray());
        
        //
        $parent = $this->getRelationParent();
        if(!$parent){
            // TODO: whats best for creating new models?
            // return null;
            throw new ApplicationException("It seems, the model has not been created yet!\nCreate / Save the model first...");
        }
        $field = $this->getRelationField();
        $type = $parent->getRelationType($field);
        
        //print_r($parent->{$field});
        //print_r(get_class($parent->{$field}));
        //print_r($parent->getRelationValue($field));
        //print_r($parent->getRelation($field));
        if($record && $parent){
            
            
            if (in_array($type, ['hasMany'])){
                //$relation = $parent->{$field};
                echo('hasMany');
            }elseif (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])){
                //echo('belongsToMany');
                
                /*
                $interimTable = $parent->{$field}()->getTable();
                DB::query('')
                $parent->id
                $record->id
                */
                
                /*
                $relation = $parent->{$field}();
                $relation->where()->rawUpdate([
                    getForeignKey() = ''
                    getOtherKey()
                ]);
                */
                //print_r($relation = $parent->{$field}->toArray());
                
                $relation = $parent->{$field}();
                //echo $relation->getOtherKey().' - '.$relation->getForeignKey()."\n";
                /*
                $query = DB::table($relation->getTable())
                    ->where($relation->getOtherKey(), $record->id)
                    ->where($relation->getForeignKey(), $parent->id)
                    ->get();
                print_r($query);
                */
                //if(!$query){
                    //$relation->updateExistingPivot($relation->getForeignKey(), [$this->getSortColumn() => 11234]);
                    //$relation->attach($record);
                //}
                
                
                
                /*
                if(!$parent->{$field}->find($record->id)){
                    $relation->attach($record);
                }
                */
                
                
                
                // update "Pivot" table, which is not a pivot
                $updateQuery = DB::table($relation->getTable())
                    ->where($relation->getOtherKey(), $record->id)
                    ->where($relation->getForeignKey(), $parent->id)
                    ->update([$this->getSortColumn() => $index]);
                
                echo($relation->getOtherKey().' '.$record->id.' | '.$relation->getForeignKey().' '.$parent->id.' | '.$this->getSortColumn().' '.$index."<br>\n");
                print_r($updateQuery);
                
            }
            elseif (in_array($type, ['belongsTo', 'hasOne', 'morphOne'])){
                echo('belongsTo');
            }
            elseif (in_array($type, ['morphTo'])){
                throw new ApplicationException('The.......');
            }
            
            
            //$this->getSortColumn()
            
            //$index
            
            //print_r($parent->{$field}()->toArray());
            
            
            /*
            // TODO: check if this is correct?
            // TODO: also, can we make it neater?
            if (in_array($type, ['hasMany'])){
                
                // TODO: this is turned off, because hasMany is not supported (look for the error break)
                $relation = $parent->{$field}();
                
                if($value == 'true'){
                    $relation->save( $record );
                }else{
                    $parent->{$field} = $parent->{$field}->except($recordId);
                    $parent->save();
                }
                
                $parent->load($field);
                
            }
            elseif (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])){
                $relation = $parent->{$field}();
                    
                if($value == 'true'){
                    $relation->attach($recordId);
                }else{
                    $relation->detach($recordId);
                }
                
                $parent->load($field);
            }
            elseif (in_array($type, ['belongsTo', 'hasOne', 'morphOne'])){
                $relation = $parent->{$field}();
                    
                if($value == 'true'){
                    $relation->associate($recordId);
                }else{
                    $relation->dissociate();
                }
                
                $parent->load($field);
            }
            elseif (in_array($type, ['morphTo'])){
                throw new ApplicationException('The relationship morphTo is not supported for list columns.');
            }
            */
            
        }
        
        //return $this->onRefreshBody();
    }
    
    
    
    
    
    
    /**
     * change relation between this and PARENT model
     */
    public function onRelationChanged()
    {
        //
        $recordId = post($this->getFieldName('relation_model'));
        $record = $this->model->find( $recordId );
        
        //
        $parent = $this->getRelationParent();
        if(!$parent){
            // TODO: whats best for creating new models?
            // return null;
            throw new ApplicationException("It seems, the model has not been created yet!\nCreate / Save the model first...");
        }
        $field = $this->getRelationField();
        $type = $parent->getRelationType($field);
        
        //
        $value = post($this->getFieldName('relation_value'));
        if($record && $parent && $value){
            
            
            
            // TODO: check if this is correct?
            // TODO: also, can we make it neater?
            if (in_array($type, ['hasMany'])){
                
                // TODO: this is turned off, because hasMany is not supported (look for the error break)
                $relation = $parent->{$field}();
                
                if($value == 'true'){
                    $relation->save( $record );
                }else{
                    $parent->{$field} = $parent->{$field}->except($recordId);
                    $parent->save();
                }
                
                $parent->load($field);
                
            }
            elseif (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])){
                $relation = $parent->{$field}();
                    
                if($value == 'true'){
                    $relation->attach($recordId);
                }else{
                    $relation->detach($recordId);
                }
                
                $parent->load($field);
            }
            elseif (in_array($type, ['belongsTo', 'hasOne', 'morphOne'])){
                $relation = $parent->{$field}();
                    
                if($value == 'true'){
                    $relation->associate($recordId);
                }else{
                    $relation->dissociate();
                }
                
                $parent->load($field);
            }
            elseif (in_array($type, ['morphTo'])){
                throw new ApplicationException('The relationship morphTo is not supported for list columns.');
            }
            
            
            
            
            
        }
        
        return $this->onRefreshRecord($recordId);
    }
    
    /**
     * ##############################################################################################
     */
    
    
    /**
     * change relation fields (relations inside related models)
     */
    public function onRelatedChanged()
    {
        //relatedModel
        $recordId = post($this->getFieldName('related_model'));
        $record = $this->model->find( $recordId );
        
        //relationField
        $fieldName = post($this->getFieldName('related_field'));
        $aliasedFieldName = $this->getFieldName($fieldName);
        $postField = post($aliasedFieldName);
        
        
        
        // check if related model was found
        // and field name is set
        if($record && $fieldName)
        {
            
            // is adding or deleting?
            $add = ($postField && array_key_exists($recordId, $postField)) ? true : false;
            // get relation type
            $type = $record->getRelationType($fieldName);
            // get input data
            $relatedData = array_values($postField[$recordId]);
            
            
            
            
            // TODO: check if this is correct?
            // TODO: also, can we make it neater?
            if (in_array($type, ['hasMany'])){
                throw new ApplicationException('The relationship hasMany is not supported for list columns.');
                /*
                foreach($relatedData as $k => $v){
                    if($add){
                        $relatedRecord = $relatedModelObject->find($v);
                        $record->{$fieldName}()->save( $relatedRecord );
                    }else{
                        $record->{$fieldName} = $record->{$fieldName}->except($v);
                        $record->save();
                    }
                }
                $record->load($fieldName);
                */
            }else
            if (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])){
                if($add){
                    $record->{$fieldName}()->detach();
                    $record->{$fieldName}()->attach($relatedData);
                }else{
                    $record->{$fieldName}()->detach();
                }
                $record->load($fieldName);
            }
            elseif (in_array($type, ['belongsTo', 'hasOne', 'morphOne'])){
                if($add){
                    if($type == 'belongsTo'){
                        $relatedData = intval($relatedData[0]);
                    }
                    $record->{$fieldName}()->dissociate();
                    $record->{$fieldName}()->associate($relatedData);
                    $record->save();
                }else{
                    $record->{$fieldName}()->dissociate();
                }
                $record->load($fieldName);
            }
            elseif (in_array($type, ['morphTo'])){
                throw new ApplicationException('The relationship morphTo is not supported for list columns.');
            }
            
            
            
            
            
            
        }else{
            throw new ApplicationException('There was an error parsing data for the deep relation "'.$fieldName.'"');
        }
        
        dump($type);
        dump($record->{$fieldName}->toArray());
    }

    /**
     * batch upload image event
     */
    public function onUpload()
    {
        try {
            //$model = $this->model->create();
            $model = new $this->model();
            
            // UPLOAD THE IMAGE
            // got this code from: FileUpload -> checkUploadPostback
            try {
                if (!Input::hasFile('file_data')) {
                    throw new ApplicationException('File missing from request');
                }
                $uploadedFile = Input::file('file_data');
                
                // SAVE FILE
                $file = $this->saveUploadedFile($uploadedFile);
                
                // FILL MODEL ATTRIBUTES WITH FILE ATTRIBUTES
                foreach($this->uploadFill as $fillK => $fillV){
                    if($file->{$fillV}){
                        $model->{$fillK} = $file->{$fillV};
                    }else{
                        $model->{$fillK} = $fillV;
                    }
                }
                
                
                // --------------------------
                // TODO: check if this is working for all relations
                if($this->relation){
                    //
                    $parentModel = $this->getRelationParent();
                    $parentRelation = $parentModel->{$this->getRelationField()}();
                    $parentRelation->save($model);
                }
                // --------------------------
                
                
                // SAVE MODEL
                $model->save();
                
                // SAVE RELATION
                $fileRelation = $model->{$this->uploadTo}();
                $fileRelation->save($file);
            }
            catch (Exception $ex) {
                throw new ApplicationException('Couldnt put file into model - onUpload action in TileList.php: '.$ex->getMessage());
            }
            
            //
            return 'SUCCESS';
        }
        catch (Exception $ex) {
            throw new ApplicationException('Couldnt upload the file - onUpload action in TileList.php: '.$ex->getMessage());
        }
    }

    protected function saveUploadedFile($uploadedFile)
    {
        $validationRules = [
            'max:'.File::getMaxFilesize(),
            'extensions:jpg,gif,png,mp3,mp4,mov'
        ];

        $validation = Validator::make(
            ['file_data' => $uploadedFile],
            ['file_data' => $validationRules]
        );

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        if (!$uploadedFile->isValid()) {
            throw new ApplicationException('File is not valid');
        }
          
        $file = new File();
        $file->data = $uploadedFile;
        //$file->is_public = $fileRelation->isPublic();
        $file->save();
        
        return $file;
    }
    
    /*
     * TODO: add this funcitonality? rename the file maybe
    protected function validateFileName($name)
    {
        if (!preg_match('/^[0-9a-z\.\s_\-]+$/i', $name)) {
            return false;
        }

        if (strpos($name, '..') !== false) {
            return false;
        }

        return true;
    }
    */
    
    //
    // GETTERS #########################################################################################
    //
    
    /**
     * Returns the current sorting column, saved in a session or cached.
     */
    protected function getActiveView()
    {
        //if (!$this->showViews) {
            //return false;
        //}
        
        if($activeView = $this->getSession('view')) {
            return $activeView;
        }else{
            return $this->defaultView;
        }
    }
    
    
    
    /**
     * Get ID list of active records
     * TODO: check if we need collection or is a list of IDs just fine?
     */
    protected function getRelationRecordIds()
    {
        if($this->relation){
            $parent = $this->getRelationParent();
            if(!$parent) return [];
            return $parent->{$this->getRelationField()}->lists('id');
        }else{
            return [];
        }
    }
    
    
    
    /**
     * Returns all the records from the supplied model, after filtering.
     * @return Collection
     */
    protected function getRecords()
    {
        //####################################
            
        if ($this->showTree) {
            $records = $this->model->getAllRoot();
        }
        else {
            $model = $this->prepareModel();
            $query = $model;
            
            // GET RELATIONS INTO QUERY
            // TODO: performance check
            foreach($this->columns as $colK => $colV){
                if(array_key_exists('relation', $colV)){
                    $query = $query->with($colV['relation']);
                }
            }
            
            /* // moved to code below
            $records = ($this->showPagination)
                ? $query->paginate($this->recordsPerPage, $this->currentPageNumber)
                : $query->get();
            */
        }
        
        //
        // IS IN RELATION SORTING MODE
        // filter to related records
        //
        if($this->getSortColumn() == $this->relationSort)
        {
            // display only records from relation
            $activeIds = $this->getRelationRecordIds();
            $query = $query->whereIn($this->model->getTable().'.id', $activeIds);
            
            //echo($query->toSql());
            //print_r($query->get()->count());
            //var_dump($query->get()->toArray());
            
            // pagination
            $records = ($this->showPagination)
                ? $query->paginate($this->recordsPerPage, $this->currentPageNumber)
                : $query->get();
        }else{
            // pagination
            $records = ($this->showPagination)
                ? $query->paginate($this->recordsPerPage, $this->currentPageNumber)
                : $query->get();
        }
        
        //echo($query->toSql());
        
        //print_r(count($records->items()));
        //exit();
        return $this->records = $records;
    }
    
    /**
     * 
     */
    protected function getRecord($id)
    {
        $model = $this->prepareModel();
        $query = $model;
                
        // GET RELATIONS INTO QUERY
        // TODO: performance check
        foreach($this->columns as $colK => $colV){
            if(array_key_exists('relation', $colV)){
                $query = $query->with($colV['relation']);
            }
        }
            
        return $query->find($id);
    }
    
    /**
     * 
     */
    public function getVisibleRecords()
    {
        $model = $this->prepareModel();
        $query = $model;
        return ($this->showPagination)
            ? $query->paginate($this->recordsPerPage, $this->currentPageNumber)->lists('id')
            : $query->get()->lists('id');
    }
    
    
    /**
     * Get preview image url
     * TODO: make it faster - preload or left join with records
     */
     
    //public $recordPreviews = null;
    public function getRecordPreview($record)
    {
        if(!$this->showPreview || !$this->previewFrom){
            return null;
        }
        //if(!$this->recordPreviews) $this->recordPreviews = $record->{$this->previewFrom}()->getModel()->all();
        if(array_key_exists($this->previewFrom, $this->model->attachMany))
        {
            $image = $record->{$this->previewFrom}->first();
            if($image) return $image->getThumb(300, 300, 'crop');
        }
        elseif(array_key_exists($this->previewFrom, $this->model->attachOne))
        {
            $image = $record->{$this->previewFrom};
            if($image) return $image->getThumb(300, 300, 'crop');
        }
        return null;
        //throw new ApplicationException('Attachment Image Preview field not found');
    } 	
    
    /**
     * Get the model which this list belongs to (is a relation of)
     */
    protected function getRelationParentModel()
    {
        try{
            $relationClass = $this->relation['class'];
        } catch (Exception $e){
            throw new ApplicationException('No relation class was set in the TileList widget.');
        };
        return new $relationClass();
    }
    
    /**
     * 
     */
    protected function getRelationParent()
    {
        return $this->getRelationParentModel()->find($this->relation['id']);
    }
    
    /**
     * 
     */
    protected function getRelationField()
    {
        return $this->relation['field'];
    }
    
    /**
     * relation types of columns, when they are relations
     * TODO: rename to "related" something
     */
    protected function getColumnTypes()
    {
        $columnTypes = [];
        foreach($this->columns as $colK => $colV){
            if(array_key_exists('relation', $colV))
            {
                $type = $this->model->getRelationType($colV['relation']);
                
                if (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    $columnTypes[$colK] = 'one2many';
                }
                elseif (in_array($type, ['belongsTo', 'hasOne'])) {
                    $columnTypes[$colK] = 'one2one';
                }
                elseif (in_array($type, ['morphTo', 'hasMany'])) {
                    throw new ApplicationException('The relationships morphTo and hasMany are not supported for list columns.');
                }
            }
        }
        return $columnTypes;
    }
    
    /**
     * TODO: rename to "related" something
     */
    public function getColumnValues()
    {
        $valuesArray = [];
        foreach($this->columns as $colK => $colV){
            if(array_key_exists('relation', $colV))
            {
                $fieldFrom = $colV['select'];
                $valuesArray[$colK] = $this->model->{$colK}()->getRelated()->lists($fieldFrom, 'id');
            }
        }
        return $valuesArray;
    }
    
    /**
     * 
     */
    public function makeCurrentPageNumber()
    {
        if(post('page')){
            $this->currentPageNumber = post('page');
        }else
        if(post($this->getFieldName('page'))){
            $this->currentPageNumber = post($this->getFieldName('page'));
        }
    }
    
    /**
     * Return a identifiable name for form field names
     */
    public function getFieldName($string)
    {
        return $this->alias . '['.$string.']';
    }
    
    //
    // OVERRIDES #########################################################################################
    //
    
    /**
     * Returns an array of allowable records per page.
     */
    protected function getSetupPerPageOptions()
    {
        $perPageOptions = [10, 20, 40, 60, 100];
        if (!in_array($this->recordsPerPage, $perPageOptions)) {
            $perPageOptions[] = $this->recordsPerPage;
        }

        sort($perPageOptions);
        return $perPageOptions;
    }
        
    /**
     * Applies any filters to the model.
     * 
     * 
     * TODO: I don't like that much code lying around...
     * That's the only line changed:
     * 
     * DbDongle::raw("group_concat(" . $sqlSelect . " separator ',')")
     */
    public function prepareModel()
    {
        $query = $this->model->newQuery();
        $primaryTable = $this->model->getTable();
        $selects = [$primaryTable.'.*'];
        $joins = [];
        $withs = [];

        /*
         * Extensibility
         */
        Event::fire('backend.list.extendQueryBefore', [$this, $query]);
        $this->fireEvent('list.extendQueryBefore', [$query]);

        /*
         * Prepare searchable column names
         */
        $primarySearchable = [];
        $relationSearchable = [];

        $columnsToSearch = [];
        if (!empty($this->searchTerm) && ($searchableColumns = $this->getSearchableColumns())) {
            foreach ($searchableColumns as $column) {
                /*
                 * Related
                 */
                if ($this->isColumnRelated($column)) {
                    $table = $this->model->makeRelation($column->relation)->getTable();
                    $columnName = isset($column->sqlSelect)
                        ? DbDongle::raw($this->parseTableName($column->sqlSelect, $table))
                        : $table . '.' . $column->valueFrom;

                    $relationSearchable[$column->relation][] = $columnName;
                }
                /*
                 * Primary
                 */
                else {
                    $columnName = isset($column->sqlSelect)
                        ? DbDongle::raw($this->parseTableName($column->sqlSelect, $primaryTable))
                        : Db::getTablePrefix() . $primaryTable . '.' . $column->columnName;

                    $primarySearchable[] = $columnName;
                }
            }
        }

        /*
         * Prepare related eager loads (withs) and custom selects (joins)
         */
        foreach ($this->getVisibleColumns() as $column) {

            if (!$this->isColumnRelated($column) || (!isset($column->sqlSelect) && !isset($column->valueFrom))) {
                continue;
            }

            if (isset($column->valueFrom)) {
                $withs[] = $column->relation;
            }

            $joins[] = $column->relation;
        }

        /*
         * Add eager loads to the query
         */
        if ($withs) {
            $query->with(array_unique($withs));
        }

        /*
         * Apply search term
         */
        $query->where(function ($innerQuery) use ($primarySearchable, $relationSearchable, $joins) {

            /*
             * Search primary columns
             */
            if (count($primarySearchable) > 0) {
                $innerQuery->orSearchWhere($this->searchTerm, $primarySearchable);
            }

            /*
             * Search relation columns
             */
            if ($joins) {
                foreach (array_unique($joins) as $join) {
                    /*
                     * Apply a supplied search term for relation columns and
                     * constrain the query only if there is something to search for
                     */
                    $columnsToSearch = array_get($relationSearchable, $join, []);

                    if (count($columnsToSearch) > 0) {
                        $innerQuery->orWhereHas($join, function ($_query) use ($columnsToSearch) {
                            $_query->searchWhere($this->searchTerm, $columnsToSearch);
                        });
                    }
                }
            }

        });

        /*
         * Custom select queries
         */
        foreach ($this->getVisibleColumns() as $column) {
            if (!isset($column->sqlSelect)) {
                continue;
            }

            $alias = $query->getQuery()->getGrammar()->wrap($column->columnName);

            /*
             * Relation column
             */
            if (isset($column->relation)) {
                // @todo Find a way...
                $relationType = $this->model->getRelationType($column->relation);
                if ($relationType == 'morphTo') {
                    throw new ApplicationException('The relationship morphTo is not supported for list columns.');
                }

                $table =  $this->model->makeRelation($column->relation)->getTable();
                $sqlSelect = $this->parseTableName($column->sqlSelect, $table);

                //
                // Manipulate a count query for the sub query
                //
                $relationObj = $this->model->{$column->relation}();
                $countQuery = $relationObj->getRelationCountQuery($relationObj->getRelated()->newQueryWithoutScopes(), $query);
                
                $joinSql = $this->isColumnRelated($column, true)
                    ? DbDongle::raw("group_concat(" . $sqlSelect . " separator ',')")
                    : DbDongle::raw($sqlSelect);
                
                $joinSql = $countQuery->select($joinSql)->toSql();

                $selects[] = Db::raw("(".$joinSql.") as ".$alias);
            }
            
            //
            // Primary column
            //
            else {
                $sqlSelect = $this->parseTableName($column->sqlSelect, $primaryTable);
                $selects[] = DbDongle::raw($sqlSelect . ' as '. $alias);
            }
        }

        //
        //dump($query->toSql());

        
        
        
        
        
        
        
        /*
         * Apply sorting
         */
        if($this->getSortColumn() == $this->relationSort)
        {
            
            
            if ($sortColumn = $this->getSortColumn()) {
                //
                $parent = $this->getRelationParent();
                if(!$parent){
                    // TODO: whats best for creating new models?
                    // return null;
                    throw new ApplicationException("It seems, the model has not been created yet!\nCreate / Save the model first...");
                }
                $field = $this->getRelationField();
                $type = $parent->getRelationType($field);
                
                if (in_array($type, ['hasMany'])){
                    //$relation = $parent->{$field};
                    echo('hasMany');
                }elseif (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])){
                    
                    $relation = $parent->{$field}();
                    
                    
                    //$query->leftJoin($relation->getTable(), function ($join)use($relation,$parent) {
                    //    $join->on('id', '=', $relation->getOtherKey())
                     //        ->where($relation->getForeignKey(), '=', $parent->id);
                    //});
                    
                    $query->leftJoin($relation->getTable(), $this->model->getTable().'.id', '=', $relation->getOtherKey())
                        ->where($relation->getForeignKey(), '=', $parent->id);
                }
                elseif (in_array($type, ['belongsTo', 'hasOne', 'morphOne'])){
                    echo('belongsTo');
                }
                elseif (in_array($type, ['morphTo'])){
                    throw new ApplicationException('The.......');
                }
                
                
                
                array_push($selects, $relation->getTable().'.'.$sortColumn);
                
                
                $query->orderBy($relation->getTable().'.'.$sortColumn, $this->sortDirection);
            }
            
            //print_r($query->toSql());
            //exit();
            
        }else{
            if ($sortColumn = $this->getSortColumn()) {
                if (($column = array_get($this->getColumns(), $sortColumn)) && $column->valueFrom) {
                    $sortColumn = $this->isColumnPivot($column)
                        ? 'pivot_' . $column->valueFrom
                        : $column->valueFrom;
                }
    
                $query->orderBy($sortColumn, $this->sortDirection);
            }
        }
    
    
    
        
        
        
        
        
        
        //
        //dump($query->toSql());

        /*
         * Apply filters
         */
        foreach ($this->filterCallbacks as $callback) {
            $callback($query);
        }

        /*
         * Add custom selects
         */
        $query->select($selects);
        
        //
        //dump($query->toSql());

        /*
         * Extensibility
         */
        if (
            ($event = $this->fireEvent('list.extendQuery', [$query], true)) ||
            ($event = Event::fire('backend.list.extendQuery', [$this, $query], true))
        ) {
            return $event;
        }

        return $query;
    }
    
    
}
    