<?php
/**
 * Tests several functions which are used to prevent xss attacks
 *
 * @package test.util
 */

use PHPUnit\Framework\TestCase;

require_once('include/text.php');

class AntiXSSTest extends TestCase {

	/**
	 * Test, that tags are escaped
	 */
	public function testEscapeTags() {
		$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />';

		$validstring=notags($invalidstring);
		$escapedString=escape_tags($invalidstring);

		$this->assertEquals('[submit type="button" onclick="alert(\'failed!\');" /]', $validstring);
		$this->assertEquals("&lt;submit type=&quot;button&quot; onclick=&quot;alert('failed!');&quot; /&gt;", $escapedString);
	}

	/**
	 * Test escaping URL's to make them safe for use in html and attributes.
	 *
	 * @dataProvider urlTestProvider
	 */
	public function testEscapeURL($url, $expected) : void {
		$this->assertEquals($expected, escape_url($url));
	}

	public function urlTestProvider() : array {
		return [
			[
				"https://example.com/settings/calendar/?f=&rpath=https://example.com/cdav/calendar'><script>alert('boom')</script>",
				"https://example.com/settings/calendar/?f=&amp;rpath=https://example.com/cdav/calendar&apos;&gt;&lt;script&gt;alert(&apos;boom&apos;)&lt;/script&gt;"
			],
			[
				"settings/calendar/?f=&rpath=https://example.com'+accesskey=x+onclick=alert(/boom/);a='",
				"settings/calendar/?f=&amp;rpath=https://example.com&apos;+accesskey=x+onclick=alert(/boom/);a=&apos;"
			],
		];
	}

	/**
	 * Test xmlify and unxmlify
	 */
	public function testXmlify() {
		$text="<tag>I want to break\n this!11!<?hard?></tag>";
		$xml=xmlify($text);
		$retext=unxmlify($text);

		$this->assertEquals($text, $retext);
	}

	/**
	 * Test xmlify and put in a document
	 */
	public function testXmlifyDocument() {
		$tag="<tag>I want to break</tag>";
		$xml=xmlify($tag);
		$text='<text>'.$xml.'</text>';

		$xml_parser=xml_parser_create();
		//should be possible to parse it
		$values=array();
		$index=array();

		$this->assertEquals(1, xml_parse_into_struct($xml_parser, $text, $values, $index));

		$this->assertEquals(array('TEXT'=>array(0)),
				$index);
		$this->assertEquals(array(array('tag'=>'TEXT', 'type'=>'complete', 'level'=>1, 'value'=>$tag)),
				$values);

		xml_parser_free($xml_parser);
	}

	//function qp, quick and dirty??
	//get_mentions
	//get_contact_block, bis Zeile 538
}
?>
