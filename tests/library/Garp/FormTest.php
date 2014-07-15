<?php
/**
 * @group Form
 */
class Garp_FormTest extends Garp_Test_PHPUnit_TestCase {

	public function testFormWithDashes(){

		$form = new Garp_Form();
		$form->setAction('/my/action/')
		     ->setMethod('post');
		$form->addElement('email', 'e-mail', array(
		    'label' => 'Your email address',
		    'required' => true
		));
		$form->addElement('password', 'pass-word',array(
			'label' => 'the label',
			'required' => true
			));
		$form->addElement('submit', 'Register');

		$this->assertTrue($form->isValid(array(
			'e-mail' => 'iosif@grrr.nl',
			'pass-word' => '123456'
		)));
	}
}