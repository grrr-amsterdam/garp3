<?php
/**
 * @group Util
 */
class Garp_Util_StringTest extends Garp_Test_PHPUnit_TestCase {

	public function testCamelcasedToUnderscored(){
		$this->assertEquals('snoop_doggy_dog', Garp_Util_String::camelcasedToUnderscored('SnoopDoggyDog'));
	}

	public function testCamelcasedToDashed(){
		$this->assertEquals('snoop-doggy-dog', Garp_Util_String::camelcasedToDashed('SnoopDoggyDog'));
	}

	public function testAcronymsToLowercase() {
		$this->assertEquals('SslBreak and HtmlRequest', Garp_Util_String::acronymsToLowercase('SSLBreak and HTMLRequest'));
		$this->assertEquals('DhcpRouter', Garp_Util_String::acronymsToLowercase('DHCPRouter'));
	}

	public function testToDashed() {
		$this->assertEquals('orienteren', Garp_Util_String::toDashed('Oriënteren'));

		//word starts with uppercase letters
		$this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('Snoop Doggy Dog!'));
		$this->assertEquals('zlutoucky-kun', Garp_Util_String::toDashed('Žluťoučký kůň'));

		//word starts with lowercase letters
		$this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('snoop doggy dog!'));

		//punctuation characters
		$this->assertEquals('snoop-the-doggy-dog', Garp_Util_String::toDashed('Snoop! [the: (doggy, \dog.'));

		//word contains special caracters
		$this->assertEquals('snoop-doggy-dog', Garp_Util_String::toDashed('Snoop Döggy Døg!'));

		//word contains special caracters and they are disregarded
		$this->assertEquals('snoop-d-ggy-d-g', Garp_Util_String::toDashed('Snoop Döggy Døg!', false));

		//word contains with decimals
		$this->assertEquals('th1s-1s-m0r3', Garp_Util_String::toDashed('th1s 1s m0r3'));

		//handling acronyms
		$this->assertEquals('ssl-break', Garp_Util_String::toDashed('SSLBreak'));
		$this->assertEquals('http-request', Garp_Util_String::toDashed('HTTPRequest'));
		$this->assertEquals('a-dhcp-router-handles-http-requests', Garp_Util_String::toDashed('aDHCPRouterHandlesHTTPRequests'));
	}

	public function testUnderscoredToReadable() {
		$this->assertEquals('This is underscored', Garp_Util_String::underscoredToReadable('this_is_underscored'));
		$this->assertEquals('this is underscored', Garp_Util_String::underscoredToReadable('this_is_underscored', false));
	}

	public function testUnderscoredToCamelcased() {
		$this->assertEquals(Garp_Util_String::underscoredToCamelcased('this_is_underscored'), 		'thisIsUnderscored');
		$this->assertEquals(Garp_Util_String::underscoredToCamelcased('this_is_underscored', true), 'ThisIsUnderscored');
	}

	public function testDashedToCamelcased() {
		$this->assertEquals(Garp_Util_String::dashedToCamelcased('this-is-dashed'), 		'thisIsDashed');
		$this->assertEquals(Garp_Util_String::dashedToCamelcased('this-is-dashed', true), 	'ThisIsDashed');
	}

	public function testShouldConvertToPoundSign() {
		$this->assertEquals('£', Garp_Util_String::utf8ToAscii(chr(163)));
	}

	public function testUtf8ToAscii() {
		$this->assertEquals('Snoop Doggy Dog', Garp_Util_String::utf8ToAscii('Snoop Döggy Døg'));
		$this->assertEquals('Snoop Duggy Dog', Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg'));
		$this->assertEquals('Zlutoucky kun', Garp_Util_String::utf8ToAscii('Žluťoučký kůň'));
		$this->assertEquals('Weiss, Gobel, Gothe, Gotz', Garp_Util_String::utf8ToAscii('Weiß, Göbel, Göthe, Götz'));
		$this->assertEquals('Abu Jafar al-Khazin', Garp_Util_String::utf8ToAscii('Abū Ja\'far al-Khāzin')); //the apostrophy is escaped
		$this->assertEquals('In fiecare zi Dumnezeu ne saruta pe gura', Garp_Util_String::utf8ToAscii('În fiecare zi Dumnezeu ne sarută pe gură'));

		//This will not work because some of the characters are ignored by the iconv method
		// $this->assertEquals(Garp_Util_String::utf8ToAscii(
		// 	'ÁáÀàÂâǍǎĂăÃãẢảẠạÄäÅåĀāĄąẤấẦầẪẫẨẩẬậẮắẰằẴẵẲẳẶặǺǻĆćĈĉČčĊċÇçĎďĐđÐÉéÈèÊêĚěĔĕẼẽẺẻĖėËëĒēĘęẾếỀềỄễỂểẸẹỆệĞğĜĝĠġĢģĤĥĦħÍíÌìĬĭÎîǏǐÏïĨĩĮįĪīỈỉỊịĴĵĶķĹĺĽľĻļŁłĿŀŃńŇňÑñŅņÓóÒòŎŏÔôỐốỒồỖỗỔổǑǒÖöŐőÕõØøǾǿŌōỎỏƠơỚớỜờỠỡỞởỢợỌọỘộṔṕṖṗŔŕŘřŖŗŚśŜŝŠšŞşŤťŢţŦŧÚúÙùŬŭÛûǓǔŮůÜüǗǘǛǜǙǚǕǖŰűŨũŲųŪūỦủƯưỨứỪừỮữỬửỰựỤụẂẃẀẁŴŵẄẅÝýỲỳŶŷŸÿỸỹỶỷỴỵŹźŽžŻż'),
		// 	'AaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaCcCcCcCcCcDdDdDEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIiIiIiIiIiIiJjKkLlLlLlLlLlNnNnNnNnOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoPpPpRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuWwWwWwWwYyYyYyYyYyYyYyZzZzZz');

		//testing the json output
		$this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Snoop Döggy Døg')));
		$this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg')));
		$this->assertTrue((bool) json_encode(Garp_Util_String::utf8ToAscii('Žluťoučký kůň')));

		$arrayToTest = array(
			'something' =>  'Weiß, Göbel, Göthe, Götz',
			'weirdChar' => chr(163),
			'diacritics' => 'ÁáÀàÂâǍǎĂăÃãẢảẠạÄäÅåĀāĄąẤấẦầẪẫẨẩẬậẮắẰằẴẵẲẳẶặǺǻĆćĈĉČčĊċÇçĎďĐđÐÉéÈèÊêĚěĔĕẼẽẺẻĖėËëĒēĘęẾếỀềỄễỂểẸẹỆệĞğĜĝĠġĢģĤĥĦħÍíÌìĬĭÎîǏǐÏïĨĩĮįĪīỈỉỊịĴĵĶķĹĺĽľĻļŁłĿŀŃńŇňÑñŅņÓóÒòŎŏÔôỐốỒồỖỗỔổǑǒÖöŐőÕõØøǾǿŌōỎỏƠơỚớỜờỠỡỞởỢợỌọỘộṔṕṖṗŔŕŘřŖŗŚśŜŝŠšŞşŤťŢţŦŧÚúÙùŬŭÛûǓǔŮůÜüǗǘǛǜǙǚǕǖŰűŨũŲųŪūỦủƯưỨứỪừỮữỬửỰựỤụẂẃẀẁŴŵẄẅÝýỲỳŶŷŸÿỸỹỶỷỴỵŹźŽžŻż',
			'czech' => 'Žluťoučký kůň'
		);
		$arrayAscii = array();
		foreach ($arrayToTest as $key => $value){
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
		$this->assertEquals('apples, pears and carrots', Garp_Util_String::humanList($array));
		$this->assertEquals('|apples|, |pears| >> |carrots|', Garp_Util_String::humanList($array,'|', '>>'));
	}


	public function testExcerpt() {
		$this->assertEquals(Garp_Util_String::excerpt("<p><strong>This</strong> is some content.</p>"), 'This is some content.');
		$this->assertEquals(Garp_Util_String::excerpt(
				'<p><span style="font-size:10px;">Small font</span> <span style="font-size:18px;">Large font</span> <span style="font-size:12px;color:orange;">Colored font</span> <span style="font-size:12px;font-weight:bold;">Bold font</span> <span style="font-size:12px;font-style:italic;">Italic font</span> and more...</p>'),
				'Small font Large font Colored font Bold font Italic font and more...');
		$this->assertEquals(Garp_Util_String::excerpt("<div class=\"beautify\">x</div>"),"x");

		$this->assertEquals(Garp_Util_String::excerpt("<p><strong>Uw uitdaging</strong><br>
		Geen tijd over voor het beheren van uw werkplekken?</p>"), "Uw uitdaging\n
		Geen tijd over voor het beheren van uw werkplekken?");

		//test different <br> writing styles
		$this->assertEquals(Garp_Util_String::excerpt("<p><strong>Testing</strong> the <br>break <br />with different formats</p>"), "Testing the \nbreak \nwith different formats");
		$this->assertEquals(Garp_Util_String::excerpt("<p><strong>Testing</strong> the <br >break <br/>with different formats</p>"), "Testing the \nbreak \nwith different formats");

		//test imput of a space if there is none
		$this->assertEquals(Garp_Util_String::excerpt("<p><strong>insert</strong> a<br >space<br/>here</p>"), "insert a\nspace\nhere");

		//this is not working
		// $this->assertEquals(Garp_Util_String::excerpt("</div><div>x</div>"),"x");
	}

	public function testLinkify() {
		$this->assertEquals(Garp_Util_String::linkify(
			'Our contact email is contact@grrr.nl, but it can be found at http://www.grrr.nl'),
			'Our contact email is <a href="mailto:contact@grrr.nl">contact@grrr.nl</a>, but it can be found at <a href="http://www.grrr.nl">http://www.grrr.nl</a>');
		$this->assertEquals(Garp_Util_String::linkify(
			'This also works for secure connections like https://www.grrr.nl'),
			'This also works for secure connections like <a href="https://www.grrr.nl">https://www.grrr.nl</a>');
	}

	public function testScrambleEmail() {

	}

	public function testStrReplaceOnce() {
		$this->assertEquals(Garp_Util_String::strReplaceOnce('this', 'that', 'this should be replaced'),'that should be replaced');
		$this->assertEquals(Garp_Util_String::strReplaceOnce('on', 'off', 'thon online ./.on>>'),'thoff online ./.on>>');
		$this->assertEquals(Garp_Util_String::strReplaceOnce('on', 'off', 'thoff online ./.on>>'),'thoff offline ./.on>>');
		$this->assertEquals(Garp_Util_String::strReplaceOnce('on', 'off', 'thoff offline ./.on>>'),'thoff offline ./.off>>');
	}

	public function testInterpolate() {

	}

	public function testToArray() {

	}

	public function testUrlProtocol() {
		$this->assertEquals('//google.com', Garp_Util_String::ensureUrlProtocol('google.com'));
		$this->assertEquals('http://google.com',
			Garp_Util_String::ensureUrlProtocol('http://google.com'));
		$this->assertEquals('https://google.com',
			Garp_Util_String::ensureUrlProtocol('https://google.com'));
		$this->assertEquals('ftp://google.com',
			Garp_Util_String::ensureUrlProtocol('ftp://google.com'));
		$this->assertEquals('//google.com?a=abc&oh=123',
			Garp_Util_String::ensureUrlProtocol('google.com?a=abc&oh=123'));
		$this->assertEquals('//google.com', Garp_Util_String::ensureUrlProtocol('//google.com'));
	}

	public function testFuzzyMatcher() {
		// test match
		$this->assertTrue(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwen'
		));
		// test non match
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
		));
		// another non match
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'rugmuncher', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
		));
		// check with funky string
		$this->assertTrue(Garp_Util_String::fuzzyMatch(
			'(╯°□°）╯︵ ┻━┻', '(╯aejfekn°□°klewmwlkfm192）╯#(#)(#︵dsjkfwjkdn ┻━┻'
		));
		// don't match when case-sensitive
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iU23983n2893ch28302jdk2399i2903910hfwen', false
		));
	}

}
