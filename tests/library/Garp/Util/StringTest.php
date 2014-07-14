<?php
/**
 * @group Util
 */
class Garp_Util_StringTest extends Garp_Test_PHPUnit_TestCase {

	public function testCamelcasedToUnderscored(){
		$this->assertEquals(Garp_Util_String::camelcasedToUnderscored('SnoopDoggyDog'), 'snoop_doggy_dog');
	}

	public function testCamelcasedToDashed(){
		$this->assertEquals(Garp_Util_String::camelcasedToDashed('SnoopDoggyDog'), '-snoop-doggy-dog');
	}

	public function testAcronymsToLowercase() {
		$this->assertEquals(Garp_Util_String::acronymsToLowercase('SSLBreak and HTMLRequest'), 'SslBreak and HtmlRequest');
		$this->assertEquals(Garp_Util_String::acronymsToLowercase('DHCPRouter'), 'DhcpRouter');
	}

	public function testToDashed() {

		//word starts with uppercase letters
		$this->assertEquals(Garp_Util_String::toDashed('Snoop Doggy Dog!'), 'snoop-doggy-dog');
		$this->assertEquals(Garp_Util_String::toDashed('Žluťoučký kůň'), 'zlutoucky-kun');

		//word starts with lowercase letters
		$this->assertEquals(Garp_Util_String::toDashed('snoop doggy dog!'), 'snoop-doggy-dog');

		//punctuation characters
		$this->assertEquals(Garp_Util_String::toDashed('Snoop! [the: (doggy, \dog.'), 'snoop-the-doggy-dog');
		
		//word contains special caracters
		$this->assertEquals(Garp_Util_String::toDashed('Snoop Döggy Døg!'), 'snoop-doggy-dog');

		//word contains special caracters and they are disregarded
		$this->assertEquals(Garp_Util_String::toDashed('Snoop Döggy Døg!', false), 'snoop-d-ggy-d-g');
		
		//word contains with decimals
		$this->assertEquals(Garp_Util_String::toDashed('th1s 1s m0r3'), 'th1s-1s-m0r3');
		//$this->assertTrue(strcmp(Garp_Util_String::toDashed('Snoop Döggy Døg!', true), 'snoop-doggy-dog'));

		//handling acronyms
		$this->assertEquals(Garp_Util_String::toDashed('SSLBreak'), 'ssl-break');
		$this->assertEquals(Garp_Util_String::toDashed('HTTPRequest'), 'http-request');
		$this->assertEquals(Garp_Util_String::toDashed('aDHCPRouterHandlesHTTPRequests'), 'a-dhcp-router-handles-http-requests');
	}



	public function testUtf8ToAscii() {

		$this->assertEquals(Garp_Util_String::utf8ToAscii('Snoop Döggy Døg'), 'Snoop Doggy Dog');
		$this->assertEquals(Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg'), 'Snoop Duggy Dog');
		$this->assertEquals(Garp_Util_String::utf8ToAscii('Žluťoučký kůň'), 'Zlutoucky kun');
		$this->assertEquals(Garp_Util_String::utf8ToAscii('Weiß, Göbel, Göthe, Götz'), 'Weiss, Gobel, Gothe, Gotz');
		$this->assertEquals(Garp_Util_String::utf8ToAscii('Abū Ja\'far al-Khāzin'), 'Abu Jafar al-Khazin'); //the apostrophy is escaped
		$this->assertEquals(Garp_Util_String::utf8ToAscii('În fiecare zi Dumnezeu ne sarută pe gură'), 'In fiecare zi Dumnezeu ne saruta pe gura');

		//This will not work because some of the characters are ignored by the iconv method
		// $this->assertEquals(Garp_Util_String::utf8ToAscii(
		// 	'ÁáÀàÂâǍǎĂăÃãẢảẠạÄäÅåĀāĄąẤấẦầẪẫẨẩẬậẮắẰằẴẵẲẳẶặǺǻĆćĈĉČčĊċÇçĎďĐđÐÉéÈèÊêĚěĔĕẼẽẺẻĖėËëĒēĘęẾếỀềỄễỂểẸẹỆệĞğĜĝĠġĢģĤĥĦħÍíÌìĬĭÎîǏǐÏïĨĩĮįĪīỈỉỊịĴĵĶķĹĺĽľĻļŁłĿŀŃńŇňÑñŅņÓóÒòŎŏÔôỐốỒồỖỗỔổǑǒÖöŐőÕõØøǾǿŌōỎỏƠơỚớỜờỠỡỞởỢợỌọỘộṔṕṖṗŔŕŘřŖŗŚśŜŝŠšŞşŤťŢţŦŧÚúÙùŬŭÛûǓǔŮůÜüǗǘǛǜǙǚǕǖŰűŨũŲųŪūỦủƯưỨứỪừỮữỬửỰựỤụẂẃẀẁŴŵẄẅÝýỲỳŶŷŸÿỸỹỶỷỴỵŹźŽžŻż'), 
		// 	'AaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaAaCcCcCcCcCcDdDdDEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIiIiIiIiIiIiJjKkLlLlLlLlLlNnNnNnNnOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoOoPpPpRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuUuWwWwWwWwYyYyYyYyYyYyYyZzZzZz');
		
		//testing the json output
		$this->assertFalse(json_encode(Garp_Util_String::utf8ToAscii('Snoop Döggy Døg')) === false);
		$this->assertFalse(json_encode(Garp_Util_String::utf8ToAscii('Snøøp Düggy Døg')) === false);
		$this->assertFalse(json_encode(Garp_Util_String::utf8ToAscii('Žluťoučký kůň')) === false);

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