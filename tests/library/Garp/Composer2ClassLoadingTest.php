<?php

/**
 * Test some files that used to be loaded by Composer 1 as PSR-0, but Composer 2 is more strict.
 */
class Garp_Composer2ClassLoadingTest extends Garp_Test_PHPUnit_TestCase {


    public function testControllerClasses() {
        $this->assertTrue(class_exists(G_RestController::class));
    }

    public function testViewHelpers() {
        $this->assertTrue(class_exists(G_View_Helper_AssetUrl::class));
        $this->assertTrue(class_exists(G_View_Helper_Html::class));
    }

    public function testPHPExcel() {
        $this->assertTrue(class_exists(PHPExcel_IOFactory::class));
    }

}
