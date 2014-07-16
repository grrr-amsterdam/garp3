<?php
class G_View_Helper_Partial extends Zend_View_Helper_Partial {

	public function partial($name = null, $module = null, $model = null) {
		return $this->view
		//take the array from $model and create properties on the view for every key that's in the array and make a render out of it.
		//check if there already is a property with that name on that view

		return 'doo doo head';
	}

}