<?php
/**
 * Garp_Model_Behavior_HtmlFilterableTest
 *
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_HtmlFilterableTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @test
     */
    public function should_filter_non_existent_tags() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable(array());

        $test = '<banana>This tag does not exist</banana>';
        $this->assertEquals('This tag does not exist', $filterable->filter($test));
    }


    /**
     * @test
     */
    public function should_allow_youtube_embed() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable(array());

        $test = '<figure class="video-embed"><iframe frameborder="0" height="315" '
            . 'src="https://www.youtube.com/watch?v=sZ5nEuG-CRc" width="560"></iframe></figure>';

        $this->assertEquals($test, $filterable->filter($test));
    }


    /**
     * @test
     */
    public function should_keep_img_inside_figure_tag() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable(array());
        $test = '<figure><img src="http://placehold.it/300x300" alt="300x300"></figure>';
        $this->assertEquals($test, $filterable->filter($test));
    }

    
    /**
     * @test
     */
    public function should_keep_figcaption_inside_figure_tag() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable(array());
        $test = '<figure class="video-embed"><figcaption>Text goes here</figcaption></figure>';
        $this->assertEquals($test, $filterable->filter($test));
    }

    /**
     * @test
     */
    public function should_allow_figure() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable(array());
        $simpleFigure = '<figure><img src="https://grrr.nl/banaan.jpg" alt=""></figure>';
        $this->assertEquals(
            $simpleFigure,
            $filterable->filter($simpleFigure)
        );
        $figureInDiv = '<div><figure><img src="https://grrr.nl/banaan.jpg" alt=""></figure> '
            . 'Bloep</div>';
        $this->assertEquals(
            $figureInDiv,
            $filterable->filter($figureInDiv)
        );
        $figureWithClass = '<figure class="left"><img src="https://grrr.nl/banaan.jpg" alt="">' .
            '</figure>';
        $this->assertEquals(
            $figureWithClass,
            $filterable->filter($figureWithClass)
        );
        $figureWithCaption = '<figure class="left"><img src="https://grrr.nl/banaan.jpg" alt="">' .
            '<figcaption>Lorem ipsum</figcaption>' .
            '</figure>';
        $this->assertEquals(
            $figureWithCaption,
            $filterable->filter($figureWithCaption)
        );
    }

    /**
     * @test
     */
    public function you_can_add_elements_with_config() {
        $filterable = new Garp_Model_Behavior_HtmlFilterable();
        $this->_helper->injectConfigValues(
            array(
                'htmlFilterable' => array(
                    'allowedElements' => array(
                        'table', 'tr', 'td'
                    )
                )
            )
        );
        $html = "<table><tr><td>cell #1</td><td>cell #2</td></tr></table>";

        $this->assertEquals(
            $html,
            $filterable->filter($html)
        );
    }

    /**
     * Run setup for this test
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
                'app' => array(
                    'domain' => 'grrr.nl'
                ),
                'htmlFilterable' => array(
                    'cachePath' => null,
                    'allowedClasses' => null
                )
            )
        );
    }
}
