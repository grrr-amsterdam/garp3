<?php
/**
 * Test utility stuff in init.php
 *
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Init
 */
class Garp_Application_InitTest extends Garp_Test_PHPUnit_TestCase {

    public function testArrayGet() {
        $a = array('foo' => 123, 'bar' => 456);
        $this->assertEquals(123, array_get($a, 'foo'));
        $this->assertEquals(456, array_get($a, 'bar'));
        $this->assertNull(array_get($a, 'baz'));

        $this->assertEquals('banana', array_get($a, 'baz', 'banana'));
    }

    public function testCurriedArrayGet() {
        $a = array('foo' => 123, 'bar' => 456);
        $curried = array_get('foo');
        $this->assertEquals(123, $curried($a));
        $curried = array_get('bar');
        $this->assertEquals(456, $curried($a));
        $curried = array_get('baz');
        $this->assertNull($curried($a));
    }

    public function testShouldGetSubsetOfArray() {
        $my_array = array(
            'name' => 'Henk',
            'occupation' => 'Doctor',
            'age' => 43,
            'country' => 'Zimbabwe'
        );
        $allowed = array('country', 'name');
        $this->assertEquals(
            array('name' => 'Henk', 'country' => 'Zimbabwe'),
            array_get_subset($my_array, $allowed)
        );
    }

    public function testId() {
        $this->assertTrue(is_callable(id()));
        $obj = new stdClass();
        $obj->hello = 'world';
        $this->assertEquals(
            $obj,
            id($obj)
        );
        $this->assertEquals(
            'banaan',
            id('banaan')
        );
        $this->assertEquals(
            $this,
            id($this)
        );
    }

    public function testWhen() {
        $this->assertEquals(
            'banana',
            when(true, 'banana', 'pineapple')
        );
        $this->assertEquals(
            'BANANA',
            when('is_string', 'strtoupper', 'id', 'baNaNa')
        );

        // Test with array_map
        $a = array(
            array('id' => 1, 'name' => 'Joe', 'type' => 'user'),
            array('id' => 2, 'name' => 'Hank', 'type' => 'admin'),
            array('id' => 3, 'name' => 'Alice', 'type' => 'user')
        );
        $mapped = array_map(
            when(
                propertyEquals('type', 'admin'),
                array_set('name', 'Superadmin'),
                array_set('name', 'Regular Joe')
            ),
            $a
        );
        $expected = array(
            array('id' => 1, 'name' => 'Regular Joe', 'type' => 'user'),
            array('id' => 2, 'name' => 'Superadmin', 'type' => 'admin'),
            array('id' => 3, 'name' => 'Regular Joe', 'type' => 'user')
        );
        $this->assertEquals($expected, $mapped);
    }

    public function testCallRight() {
        $sayHello = function ($to, $from, $message) {
            return "Hello {$to}, {$from} says '{$message}'";
        };
        $askDirections = callRight($sayHello, "Where's the supermarket?");
        $expected = 'Hello John, Hank says \'Where\'s the supermarket?\'';
        $this->assertEquals($expected, $askDirections('John', 'Hank'));

        $lindaAsksDirections = callRight($sayHello, 'Linda', "Where's the drugstore?");
        $expected = 'Hello John, Linda says \'Where\'s the drugstore?\'';
        $this->assertEquals($expected, $lindaAsksDirections('John'));

        $lindaGreetsJohnComplete = callRight($sayHello, 'John', 'Linda', 'Hi there!');
        $expected = 'Hello John, Linda says \'Hi there!\'';
        $this->assertEquals($expected, $lindaGreetsJohnComplete());

        $helloCopy = callRight($sayHello);
        $expected = 'Hello John, Linda says \'Hi there!\'';
        $this->assertEquals($expected, $helloCopy('John', 'Linda', 'Hi there!'));
    }

    public function testCallLeft() {
        $sayHello = function ($to, $from, $message) {
            return "Hello {$to}, {$from} says '{$message}'";
        };
        $sayHelloToJohn = callLeft($sayHello, 'John');
        $expected = 'Hello John, Hank says \'How\'s it going?\'';
        $this->assertEquals($expected, $sayHelloToJohn('Hank', 'How\'s it going?'));

        $hankGreetsJohn = callLeft($sayHello, 'John', 'Hank');
        $expected = 'Hello John, Hank says \'How\'s it going?\'';
        $this->assertEquals($expected, $hankGreetsJohn('How\'s it going?'));

        $hankGreetsJohnComplete = callLeft($sayHello, 'John', 'Hank', 'Hi there!');
        $expected = 'Hello John, Hank says \'Hi there!\'';
        $this->assertEquals($expected, $hankGreetsJohnComplete());

        $helloCopy = callLeft($sayHello);
        $expected = 'Hello John, Hank says \'Hi there!\'';
        $this->assertEquals($expected, $helloCopy('John', 'Hank', 'Hi there!'));
    }

    public function testArraySome() {
        $fn = function ($s) {
            return strlen($s) > 5;
        };
        $a = array('foo', 'bar', 'baz');
        $this->assertFalse(some($a, $fn));

        $b = array('foo', 'bar', 'baaaaaz');
        $this->assertTrue(some($b, $fn));
    }

    public function testCall() {
        // See below for the mock class
        $objects = array(
            new Mock_InitTest_User('henk'), new Mock_InitTest_User('jaap'),
            new Mock_InitTest_User('lars'));
        $names = array_map(callMethod('getName', array()), $objects);
        $this->assertEquals(
            array('henk', 'jaap', 'lars'),
            $names
        );
    }

    public function testPsort() {
        $spices = array('cloves', 'coriander seed', 'cumin', 'chili', 'cinnamon');
        $sortedSpices = psort(null, $spices);
        $this->assertEquals(
            array('chili', 'cinnamon', 'cloves', 'coriander seed', 'cumin'),
            $sortedSpices,
            'psort sorts the array'
        );
        $this->assertEquals(
            array('cloves', 'coriander seed', 'cumin', 'chili', 'cinnamon'),
            $spices,
            'Original array is left untouched'
        );

        $sortByLength = function ($a, $b) {
            return strlen($b) - strlen($a);
        };
        $sortedSpicesByLength = psort($sortByLength, $spices);
        $this->assertEquals(
            array('coriander seed', 'cinnamon', 'cloves', 'cumin', 'chili'),
            $sortedSpicesByLength,
            'psort sorts array using custom function (usort)'
        );

        $usersByGroup = array(
            array('Henk', 'Bettie', 'Johan'),
            array('Gijs', 'Frits', 'Bernard'),
            array('Julia', 'Wilma', 'Zacharia')
        );
        $sortedUserGroups = array_map(psort(), $usersByGroup);
        $this->assertEquals(
            array(
                array('Bettie', 'Henk', 'Johan'),
                array('Bernard', 'Frits', 'Gijs'),
                array('Julia', 'Wilma', 'Zacharia')
            ),
            $sortedUserGroups,
            'psort will auto-curry itself when called without array'
        );
    }

    public function testShouldHaveSuccessfullyClonedGzdecode() {
        if (version_compare(PHP_VERSION, '5.4.0') === -1) {
            // gzdecode() is not available, so we can't compare the clone to the native function.
            return true;
        }

        // @codingStandardsIgnoreStart
        function clonedGzDecode($data,&$filename='',&$error='',$maxlength=null) {
            $len = strlen($data);
            if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
                $error = "Not in GZIP format.";
                return null;  // Not GZIP format (See RFC 1952)
            }
            $method = ord(substr($data,2,1));  // Compression method
            $flags  = ord(substr($data,3,1));  // Flags
            if ($flags & 31 != $flags) {
                $error = "Reserved bits not allowed.";
                return null;
            }
            // NOTE: $mtime may be negative (PHP integer limitations)
            $mtime = unpack("V", substr($data,4,4));
            $mtime = $mtime[1];
            $xfl   = substr($data,8,1);
            $os    = substr($data,8,1);
            $headerlen = 10;
            $extralen  = 0;
            $extra     = "";
            if ($flags & 4) {
                // 2-byte length prefixed EXTRA data in header
                if ($len - $headerlen - 2 < 8) {
                    return false;  // invalid
                }
                $extralen = unpack("v",substr($data,8,2));
                $extralen = $extralen[1];
                if ($len - $headerlen - 2 - $extralen < 8) {
                    return false;  // invalid
                }
                $extra = substr($data,10,$extralen);
                $headerlen += 2 + $extralen;
            }
            $filenamelen = 0;
            $filename = "";
            if ($flags & 8) {
                // C-style string
                if ($len - $headerlen - 1 < 8) {
                    return false; // invalid
                }
                $filenamelen = strpos(substr($data,$headerlen),chr(0));
                if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                    return false; // invalid
                }
                $filename = substr($data,$headerlen,$filenamelen);
                $headerlen += $filenamelen + 1;
            }
            $commentlen = 0;
            $comment = "";
            if ($flags & 16) {
                // C-style string COMMENT data in header
                if ($len - $headerlen - 1 < 8) {
                    return false;    // invalid
                }
                $commentlen = strpos(substr($data,$headerlen),chr(0));
                if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                    return false;    // Invalid header format
                }
                $comment = substr($data,$headerlen,$commentlen);
                $headerlen += $commentlen + 1;
            }
            $headercrc = "";
            if ($flags & 2) {
                // 2-bytes (lowest order) of CRC32 on header present
                if ($len - $headerlen - 2 < 8) {
                    return false;    // invalid
                }
                $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
                $headercrc = unpack("v", substr($data,$headerlen,2));
                $headercrc = $headercrc[1];
                if ($headercrc != $calccrc) {
                    $error = "Header checksum failed.";
                    return false;    // Bad header CRC
                }
                $headerlen += 2;
            }
            // GZIP FOOTER
            $datacrc = unpack("V",substr($data,-8,4));
            $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
            $isize = unpack("V",substr($data,-4));
            $isize = $isize[1];
            // decompression:
            $bodylen = $len-$headerlen-8;
            if ($bodylen < 1) {
                // IMPLEMENTATION BUG!
                return null;
            }
            $body = substr($data,$headerlen,$bodylen);
            $data = "";
            if ($bodylen > 0) {
                switch ($method) {
                case 8:
                    // Currently the only supported compression method:
                    $data = gzinflate($body,$maxlength);
                    break;
                default:
                    $error = "Unknown compression method.";
                    return false;
                }
            }  // zero-byte body content is allowed
            // Verifiy CRC32
            $crc   = sprintf("%u",crc32($data));
            $crcOK = $crc == $datacrc;
            $lenOK = $isize == strlen($data);
            if (!$lenOK || !$crcOK) {
                $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
                return false;
            }
            return $data;
        }

        $encodedContent =
            gzencode('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
        // @codingStandardsIgnoreEnd

        // Compare output of cloned version with native function
        $this->assertEquals(clonedGzDecode($encodedContent), gzdecode($encodedContent));

    }

}

/**
 * Mock_InitTest_User
 *
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Mock_InitTest_User {
    protected $_name;
    public function __construct($name) {
        $this->_name = $name;
    }

    public function getName() {
        return $this->_name;
    }
}
