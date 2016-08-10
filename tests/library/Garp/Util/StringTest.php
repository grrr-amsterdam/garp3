<?php
/**
 * Garp_Util_StringTest
 *
 * @author Harmen Janssen <harmen@grrr.nl>
 * @author David Spreekmeester <david@grrr.nl>
 * @group Util
 */
class Garp_Util_StringTest extends Garp_Test_PHPUnit_TestCase {

    public function testCamelcasedToUnderscored(){
        $this->assertEquals(
            'snoop_doggy_dog',
            Garp_Util_String::camelcasedToUnderscored('SnoopDoggyDog')
        );
    }

    public function testCamelcasedToDashed(){
        $this->assertEquals(
            'snoop-doggy-dog',
            Garp_Util_String::camelcasedToDashed('SnoopDoggyDog')
        );
    }

    public function testAcronymsToLowercase() {
        $this->assertEquals(
            'SslBreak and HtmlRequest',
            Garp_Util_String::acronymsToLowercase('SSLBreak and HTMLRequest')
        );
        $this->assertEquals('DhcpRouter', Garp_Util_String::acronymsToLowercase('DHCPRouter'));
        $this->assertEquals('Dhcp', Garp_Util_String::acronymsToLowercase('DHCP'));
    }

    public function testToDashed() {
        $this->assertEquals('orienteren', Garp_Util_String::toDashed('Oriënteren'));

        //word starts with uppercase letters
        $this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('Snoop Doggy Dog!'));
        $this->assertEquals('zlutoucky-kun', Garp_Util_String::toDashed('Žluťoučký kůň'));

        //word starts with lowercase letters
        $this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('snoop doggy dog!'));

        //punctuation characters
        $this->assertEquals(
            'snoop-the-doggy-dog',
            Garp_Util_String::toDashed('Snoop! [the: (doggy, \dog.')
        );

        //word contains special caracters
        $this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('Snoop Döggy Døg!'));

        //word contains special caracters and they are disregarded
        $this->assertEquals(
            'snoop-d-ggy-d-g',
            Garp_Util_String::toDashed('Snoop Döggy Døg!', false)
        );

        //word contains with decimals
        $this->assertEquals('th1s-1s-m0r3', Garp_Util_String::toDashed('th1s 1s m0r3'));

        //handling acronyms
        $this->assertEquals('ssl-break', Garp_Util_String::toDashed('SSLBreak'));
        $this->assertEquals('http-request', Garp_Util_String::toDashed('HTTPRequest'));
        $this->assertEquals(
            'a-dhcp-router-handles-http-requests',
            Garp_Util_String::toDashed('aDHCPRouterHandlesHTTPRequests')
        );

        $this->assertEquals('junkie-xl', Garp_Util_String::toDashed('Junkie XL'));
        $this->assertEquals('hbo', Garp_Util_String::toDashed('HBO'));
    }

    public function testUnderscoredToReadable() {
        $this->assertEquals(
            'This is underscored',
            Garp_Util_String::underscoredToReadable('this_is_underscored')
        );
        $this->assertEquals(
            'this is underscored',
            Garp_Util_String::underscoredToReadable('this_is_underscored', false)
        );
    }

    public function testUnderscoredToCamelcased() {
        $this->assertEquals(
            'thisIsUnderscored',
            Garp_Util_String::underscoredToCamelcased('this_is_underscored')
        );
        $this->assertEquals(
            'ThisIsUnderscored',
            Garp_Util_String::underscoredToCamelcased('this_is_underscored', true)
        );
    }

    public function testDashedToCamelcased() {
        $this->assertEquals(
            'thisIsDashed',
            Garp_Util_String::dashedToCamelcased('this-is-dashed')
        );
        $this->assertEquals(
            'ThisIsDashed',
            Garp_Util_String::dashedToCamelcased('this-is-dashed', true)
        );
    }

    public function testJa() {
        $this->assertEquals('L', strtr(chr(163), chr(163), 'L'));
        $this->assertEquals(
            'Hallo David', strtr(
                'Welkom Harmen', array(
                'Harmen' => 'David',
                'Welkom' => 'Hallo'
                )
            )
        );
    }

    public function testUtf8ToAscii() {
        $this->assertEquals('Snoop Doggy Dog', Garp_Util_String::utf8ToAscii('Snoop Döggy Døg'));
        $this->assertEquals('Snoop Duggy Dog', Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg'));
        $this->assertEquals('Zlutoucky kun', Garp_Util_String::utf8ToAscii('Žluťoučký kůň'));
        $this->assertEquals(
            'Weiss, Gobel, Gothe, Gotz',
            Garp_Util_String::utf8ToAscii('Weiß, Göbel, Göthe, Götz')
        );
        $this->assertEquals(
            'Abu Jafar al-Khazin',
            //the apostrophy is escaped
            Garp_Util_String::utf8ToAscii('Abū Ja\'far al-Khāzin')
        );
        $this->assertEquals(
            'In fiecare zi Dumnezeu ne saruta pe gura',
            Garp_Util_String::utf8ToAscii('În fiecare zi Dumnezeu ne sarută pe gură')
        );

        $this->assertEquals(
            'oe',
            Garp_Util_String::utf8ToAscii(chr(156))
        );

        //testing the json output
        $this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Snoop Döggy Døg')));
        $this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg')));
        $this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Žluťoučký kůň')));

        $arrayToTest = array(
            'something' =>  'Weiß, Göbel, Göthe, Götz',
            'weirdChar' => 'ॐ✡❀✿☃',
            // @codingStandardsIgnoreStart
            'diacritics' => 'ÁáÀàÂâǍǎĂăÃãẢảẠạÄäÅåĀāĄąẤấẦầẪẫẨẩẬậẮắẰằẴẵẲẳẶặǺǻĆćĈĉČčĊċÇçĎďĐđÐÉéÈèÊêĚěĔĕẼẽẺẻĖėËëĒēĘęẾếỀềỄễỂểẸẹỆệĞğĜĝĠġĢģĤĥĦħÍíÌìĬĭÎîǏǐÏïĨĩĮįĪīỈỉỊịĴĵĶķĹĺĽľĻļŁłĿŀŃńŇňÑñŅņÓóÒòŎŏÔôỐốỒồỖỗỔổǑǒÖöŐőÕõØøǾǿŌōỎỏƠơỚớỜờỠỡỞởỢợỌọỘộṔṕṖṗŔŕŘřŖŗŚśŜŝŠšŞşŤťŢţŦŧÚúÙùŬŭÛûǓǔŮůÜüǗǘǛǜǙǚǕǖŰűŨũŲųŪūỦủƯưỨứỪừỮữỬửỰựỤụẂẃẀẁŴŵẄẅÝýỲỳŶŷŸÿỸỹỶỷỴỵŹźŽžŻż',
            // @codingStandardsIgnoreEnd
            'czech' => 'Žluťoučký kůň'
        );
        $arrayAscii = array();
        foreach ($arrayToTest as $key => $value) {
            $arrayAscii[$key] = Garp_Util_String::utf8ToAscii($value);
        }
        $json = json_encode($arrayAscii);
        $reverted = json_decode($json);

        $this->assertFalse(is_null($reverted->something));
        $this->assertFalse(is_null($reverted->weirdChar));
        $this->assertFalse(is_null($reverted->diacritics));
        $this->assertFalse(is_null($reverted->czech));
    }

    public function testEndsIn() {
        $this->assertTrue(Garp_Util_String::endsIn('e', 'Bad to the bone'));
        $this->assertFalse(Garp_Util_String::endsIn('I', 'I drink alone'));
    }

    public function testHumanList() {
        $array = array('apples', 'pears', 'carrots');
        $this->assertEquals(
            'apples, pears and carrots',
            Garp_Util_String::humanList($array)
        );
        $this->assertEquals(
            '|apples|, |pears| >> |carrots|',
            Garp_Util_String::humanList($array, '|', '>>')
        );
    }


    public function testExcerpt() {
        $this->assertEquals(
            'This is some content.',
            Garp_Util_String::excerpt("<p><strong>This</strong> is some content.</p>")
        );
        $this->assertEquals(
            'Small font Large font Colored font Bold font Italic font and more...',
            Garp_Util_String::excerpt(
                '<p><span style="font-size:10px;">Small font</span> ' .
                '<span style="font-size:18px;">Large font</span> ' .
                '<span style="font-size:12px;color:orange;">Colored font</span> ' .
                '<span style="font-size:12px;font-weight:bold;">Bold font</span> ' .
                '<span style="font-size:12px;font-style:italic;">Italic font</span> and more...</p>'
            )
        );
        $this->assertEquals('x', Garp_Util_String::excerpt("<div class=\"beautify\">x</div>"));

        $this->assertEquals(
            "Uw uitdaging\n
                Geen tijd over voor het beheren van uw werkplekken?",
            Garp_Util_String::excerpt(
                "<p><strong>Uw uitdaging</strong><br>
                Geen tijd over voor het beheren van uw werkplekken?</p>"
            )
        );

        //test different <br> writing styles
        $this->assertEquals(
            "Testing the \nbreak \nwith different formats",
            Garp_Util_String::excerpt(
                "<p><strong>Testing</strong> the <br>break" .
                " <br />with different formats</p>"
            )
        );
        $this->assertEquals(
            "Testing the \nbreak \nwith different formats",
            Garp_Util_String::excerpt(
                "<p><strong>Testing</strong> the <br >break <br/>with different formats</p>"
            )
        );

        //test imput of a space if there is none
        $this->assertEquals(
            "insert a\nspace\nhere",
            Garp_Util_String::excerpt(
                "<p><strong>insert</strong> a<br >space<br/>here</p>"
            )
        );

        //this is not working
        // $this->assertEquals(Garp_Util_String::excerpt("</div><div>x</div>"),"x");
    }

    public function testLinkify() {
        $this->assertEquals(
            'Our contact email is <a href="mailto:contact@grrr.nl">contact@grrr.nl</a>, ' .
                'but it can be found at <a href="http://www.grrr.nl">http://www.grrr.nl</a>',
            Garp_Util_String::linkify(
                'Our contact email is contact@grrr.nl, but it can be found at http://www.grrr.nl'
            )
        );

        $this->assertEquals(
            'This also works for secure connections like ' .
                '<a href="https://www.grrr.nl">https://www.grrr.nl</a>',
            Garp_Util_String::linkify(
                'This also works for secure connections like https://www.grrr.nl'
            )
        );
    }

    public function testStrReplaceOnce() {
        $this->assertEquals(
            'that should be replaced',
            Garp_Util_String::strReplaceOnce('this', 'that', 'this should be replaced')
        );
        $this->assertEquals(
            'thoff online ./.on>>',
            Garp_Util_String::strReplaceOnce(
                'on', 'off', 'thon online ./.on>>'
            )
        );
        $this->assertEquals(
            'thoff offline ./.on>>',
            Garp_Util_String::strReplaceOnce('on', 'off', 'thoff online ./.on>>')
        );
        $this->assertEquals(
            'thoff offline ./.off>>',
            Garp_Util_String::strReplaceOnce('on', 'off', 'thoff offline ./.on>>')
        );
    }

    public function testUrlProtocol() {
        $this->assertEquals('//google.com', Garp_Util_String::ensureUrlProtocol('google.com'));
        $this->assertEquals(
            'http://google.com',
            Garp_Util_String::ensureUrlProtocol('http://google.com')
        );
        $this->assertEquals(
            'https://google.com',
            Garp_Util_String::ensureUrlProtocol('https://google.com')
        );
        $this->assertEquals(
            'ftp://google.com',
            Garp_Util_String::ensureUrlProtocol('ftp://google.com')
        );
        $this->assertEquals(
            '//google.com?a=abc&oh=123',
            Garp_Util_String::ensureUrlProtocol('google.com?a=abc&oh=123')
        );
        $this->assertEquals('//google.com', Garp_Util_String::ensureUrlProtocol('//google.com'));
    }

    public function testFuzzyMatcher() {
        // test match
        $this->assertTrue(
            Garp_Util_String::fuzzyMatch(
                'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwen'
            )
        );
        // test non match
        $this->assertFalse(
            Garp_Util_String::fuzzyMatch(
                'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
            )
        );
        // another non match
        $this->assertFalse(
            Garp_Util_String::fuzzyMatch(
                'rugmuncher', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
            )
        );
        // check with funky string
        $this->assertTrue(
            Garp_Util_String::fuzzyMatch(
                '(╯°□°）╯︵ ┻━┻', '(╯aejfekn°□°klewmwlkfm192）╯#(#)(#︵dsjkfwjkdn ┻━┻'
            )
        );
        // don't match when case-sensitive
        $this->assertFalse(
            Garp_Util_String::fuzzyMatch(
                'munchkin', 'm19302390iU23983n2893ch28302jdk2399i2903910hfwen', false
            )
        );
    }

}
