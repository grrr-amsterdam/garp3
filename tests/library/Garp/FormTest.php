<?php
use Garp\Functional as f;

/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Form
 */
class Garp_FormTest extends Garp_Test_PHPUnit_TestCase {

    /** @test */
    public function should_accept_names_with_dashes(){
        $form = new Garp_Form();
        $form->setAction('/my/action/')
            ->setMethod('post');
        $form->addElement(
            'email', 'e-mail', [
                'label' => 'Your email address',
                'required' => true
            ]
        );
        $form->addElement(
            'password', 'pass-word', [
                'label' => 'the label',
                'required' => true
            ]
        );
        $form->addElement('submit', 'Register');

        $this->assertTrue(
            $form->isValid(
                [
                    'e-mail' => 'iosif@grrr.nl',
                    'pass-word' => '123456'
                ]
            )
        );
    }

    /** @test */
    public function should_set_label_required_suffix() {
        $form = new Garp_Form();
        $form->addElement('text', 'foo', ['label' => 'Name:']);

        $this->assertEquals(
            ' <i>*</i>',
            $form->getElement('foo')->getDecorator('Label')->getRequiredSuffix()
        );
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unrecognized options fluffy, flooflap encountered
     */
    public function should_throw_on_unrecognized_merge_options() {
        $form = new Garp_Form();
        $form->addElement(
            'text', 'foo', [
                'decorators' => [
                    'inherit' => [
                        'fluffy' => [],
                        'flooflap' => [],
                    ]
                ]
            ]
        );
    }

    /** @test */
    public function should_merge_decorator_options() {
        $form = new Garp_Form();
        $form->addElement(
            'text', 'foo', [
                'decorators' => [
                    'inherit' => [
                        'merge_options' => [
                            ['HtmlTag', ['attribs' => ['data-foo' => 'bar']]]
                        ]
                    ]
                ]
            ]
        );

        $element = $form->getElement('foo');
        $decorators = $element->getDecorators();
        $this->assertCount(5, $decorators);
        $classSuffixes = f\map(f\compose('Garp\Functional\last', f\split('_')), f\keys($decorators));
        $this->assertEquals(
            ['ViewHelper', 'Label', 'Description', 'Errors', 'HtmlTag'],
            $classSuffixes
        );
        $this->assertEquals(
            ['data-foo' => 'bar'],
            $element->getDecorator('HtmlTag')->getOption('attribs')
        );
    }

    /** @test */
    public function should_omit_decorators() {
        $form = new Garp_Form();
        $form->addElement(
            'text', 'foo', [
                'decorators' => [
                    'inherit' => [
                        'omit' => [
                            'Description'
                        ]
                    ]
                ]
            ]
        );

        $element = $form->getElement('foo');
        $decorators = $element->getDecorators();
        $this->assertCount(4, $decorators);
        $classSuffixes = f\map(f\compose('Garp\Functional\last', f\split('_')), f\keys($decorators));
        $this->assertEquals(
            ['ViewHelper', 'Label', 'Errors', 'HtmlTag'],
            $classSuffixes
        );
    }

    /** @test */
    public function should_add_decorators_to_default() {
        $form = new Garp_Form();
        $form->addElement(
            'text', 'foo', [
                'decorators' => [
                    'inherit' => [
                        'merge' => [
                            [
                                'at' => 'Errors',
                                'spec' => [['SecondDescription' => 'Description'], []]
                            ],
                            [
                                'Image'
                            ],
                            [
                                'after' => 'Label',
                                'spec' => ['Captcha']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $element = $form->getElement('foo');
        $decorators = $element->getDecorators();
        $this->assertCount(8, $decorators);
        $classSuffixes = f\map(f\compose('Garp\Functional\last', f\split('_')), f\keys($decorators));
        $this->assertEquals(
            ['ViewHelper', 'Label', 'Captcha', 'Description', 'SecondDescription', 'Errors', 'HtmlTag', 'Image'],
            $classSuffixes
        );

    }

    /** @test */
    public function should_add_decorators_to_default_radio() {
        $form = new Garp_Form();
        $form->addElement(
            'radio', 'foo', [
                'multiOptions' => ['foo', 'bar'],
                'decorators' => [
                    'inherit' => [
                        'merge' => [
                            [
                                'after' => 'tag2',
                                'spec' => 'Captcha'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $element = $form->getElement('foo');
        $decorators = $element->getDecorators();
        $this->assertCount(8, $decorators);
        $classSuffixes = f\map(f\compose('Garp\Functional\last', f\split('_')), f\keys($decorators));
        $this->assertEquals(
            ['ViewHelper', 'Description', 'tag1', 'tag2', 'Captcha', 'AnyMarkup', 'Errors', 'tag3'],
            $classSuffixes
        );

    }

}
