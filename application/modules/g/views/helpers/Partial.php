<?php
class G_View_Helper_Partial extends Zend_View_Helper_Partial {

// protected $_objectKey;

	public function partial($name = null, $module = null, $model = null) {

		if (0 == func_num_args()) {
            return $this;
        }

        $view = $this->view;

		$storageArray = array();
		$toDeleteArray = array();
		if ($model != null){
	        foreach ($model as $key => $value){
	        	//check if the key already exists in the view
	        	if (array_key_exists($key, $view)){ 
		        	//if it does, it is saved in storageArray because it will be overwritten
	        		$storageArray[$key] = $this->view->{$key};
	        	} else {
	        		//if it does not exist it means that it will be created later and must be stored for deletion
	        		array_push($toDeleteArray, $key);
	        	}
	        }
    	}

        if (isset($this->partialCounter)) {
            $view->partialCounter = $this->partialCounter;
        }
        if (isset($this->partialTotalCount)) {
            $view->partialTotalCount = $this->partialTotalCount;
        }

        if ((null !== $module) && is_string($module)) {
            require_once 'Zend/Controller/Front.php';
            $moduleDir = Zend_Controller_Front::getInstance()->getControllerDirectory($module);
            if (null === $moduleDir) {
                require_once 'Zend/View/Helper/Partial/Exception.php';
                $e = new Zend_View_Helper_Partial_Exception('Cannot render partial; module does not exist');
                $e->setView($this->view);
                throw $e;
            }
            $viewsDir = dirname($moduleDir) . '/views';
            $view->addBasePath($viewsDir);
        } elseif ((null == $model) && (null !== $module)
            && (is_array($module) || is_object($module)))
        {
            $model = $module;
        }

        if (!empty($model)) {
            if (is_array($model)) {
                $view->assign($model);
            } elseif (is_object($model)) {
                if (null !== ($objectKey = $this->getObjectKey())) {
                    $view->assign($objectKey, $model);
                } elseif (method_exists($model, 'toArray')) {
                    $view->assign($model->toArray());
                } else {
                    $view->assign(get_object_vars($model));
                }
            }
        }

        $output = $view->render($name);

        foreach ($toDeleteArray as $key){
        	unset($this->view->{$key});
        }
        foreach ($storageArray as $key => $value){
        	//every value from the stored array is overwritten into the view
        	$this->view->{$key} = $storageArray[$key];
        }

        return $output;
	}

}