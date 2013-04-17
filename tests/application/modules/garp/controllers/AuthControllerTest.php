<?php
/**
 * G_AuthControllerTest
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class G_AuthControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {
	public function testIndexAction() {
        $params = array(
			'action' => 'index',
			'controller' => 'auth',
			'module' => 'g'
		);

        $url = $this->url($this->urlizeOptions($params));
		$this->dispatch($url);
		
		$this->assertController($params['controller']);
		$this->assertAction($params['action']);
	    $this->assertModule($params['module']);

		// $this->assertResponseCode(200);
		$this->assertRedirectTo('/g/auth/login');
	}
}
