<?php
/*
Author 2013, 2014: Patrick Smith

This content is released under the MIT License: http://opensource.org/licenses/MIT
*/

define ('GLAZE_VERSION', '1.5.2c');

define ('GLAZE_TYPE_TEXT', 'text');
define ('GLAZE_TYPE_URL', 'URL');
define ('GLAZE_TYPE_EMAIL_ADDRESS', 'emailAddress');
define ('GLAZE_TYPE_EMAIL_ADDRESS_MAILTO_URL', 'emailAddressMailtoURL');
define ('GLAZE_TYPE_SPACED_LIST_ATTRIBUTE', 'spacedListAttribute');
define ('GLAZE_TYPE_PREGLAZED', 'preglazed');


function glazeText($string)
{
	// Convert to UTF-8 if it's not already.
	$string = mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string));
	// Encode quotes too: this function covers general and attribute text in one.
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function glazeURL($stringURL)
{
	return glazeText($stringURL);
}

/* private */ function glazeNumericallyEncodeString($string)
{
	// Based on http://github.com/mdesign/md.spam_me_not.ee_addon/blob/master/plugins/pi.md_spam_me_not.php
	$stringLength = strlen($string);
	$stringDisplay = '';
	for ($i = 0; $i < $stringLength; $i++) {
		$method = rand(1, 2);
		$characterOrdinal = ord($string[$i]);
		$encodedCharacter = '&#' .( ($method === 1)? $characterOrdinal : 'x' .dechex($characterOrdinal) ). ';';
		$stringDisplay .= $encodedCharacter;
	}
	return $stringDisplay;
}

function glazeEmailAddress($emailAddress)
{
	return glazeNumericallyEncodeString($emailAddress);
}

function glazeEmailAddressMailtoURL($emailAddress)
{
	$emailAddressParts = explode('@', $emailAddress);
	$emailAddressURL = 'mailto:' .rawurlencode($emailAddressParts[0]). '@' .rawurlencode($emailAddressParts[1]);
	
	return glazeNumericallyEncodeString($emailAddressURL);
}

function glazeValue($value, $valueType = null)
{
	if ($valueType === GLAZE_TYPE_PREGLAZED) {
		return $value;
	}
	else if ($valueType === GLAZE_TYPE_URL) {
		return glazeURL($value);
	}
	else if ($valueType === GLAZE_TYPE_EMAIL_ADDRESS) {
		return glazeEmailAddress($value);
	}
	else if ($valueType === GLAZE_TYPE_EMAIL_ADDRESS_MAILTO_URL) {
		return glazeEmailAddressMailtoURL($value);
	}
	else if ($valueType === GLAZE_TYPE_SPACED_LIST_ATTRIBUTE && is_array($value)) {
		$value = implode(' ', $value);
	}
	
	
	return glazeText($value);
}

/* Garnish */

function garnishNumberWithOrdinals($number)
{
	$lastUnit = $number % 10;
	$tens = $number % 100;
	if ($lastUnit === 0 || $lastUnit >= 4 || ($tens >= 11 && $tens <= 13))
		$suffix = 'th';
	else if ($lastUnit === 1)
		$suffix = 'st';
	else if ($lastUnit === 2)
		$suffix = 'nd';
	else
		$suffix = 'rd';
	
	return $number . $suffix;
}

/* Attributes */

// Used to automatically pick out the type of certain attributes.
/* private */ function glazeTypeForAttributeName($attributeName)
{
	$attributeName = strtolower($attributeName);
	
	if ($attributeName === 'class') {
		return GLAZE_TYPE_SPACED_LIST_ATTRIBUTE;
	}
	else if ($attributeName === 'href' || $attributeName === 'src') {
		return GLAZE_TYPE_URL;
	}
	
	return GLAZE_TYPE_TEXT;
}

/* private */ function glazeAttribute($attributeName, $attributeValue, $valueType = null)
{
	if (is_array($attributeValue) && isset($attributeValue['valueType'])) {
		$attributeOptions = $attributeValue;
		$attributeValue = $attributeOptions['value'];
		$valueType = $attributeOptions['valueType'];
	}
	
	if (empty($valueType)) {
		$valueType = glazeTypeForAttributeName($attributeName);
	}
	
	// Looks like | $name="$value"|
	return ' ' .$attributeName. '="' .glazeValue($attributeValue, $valueType). '"';
}

/* private */ function glazeAttributeCheck($attributeName, &$attributeValueToCheck, $attributeValueToUse = null, $valueType = null)
{
	if (empty($attributeValueToCheck))
		return '';
	
	return glazeAttribute($attributeName, isset($attributeValueToUse) ? $attributeValueToUse : $attributeValueToCheck, $valueType);
}



function glazeElementTagNameIsSelfClosing($tagName)
{
	$tagName = strtolower($tagName);
	
	switch ($tagName):
		case 'area':
		case 'base':
		case 'br':
		case 'col':
		case 'command':
		case 'embed':
		case 'hr':
		case 'img':
		case 'input':
		case 'keygen':
		case 'link':
		case 'meta':
		case 'param':
		case 'source':
		case 'track':
		case 'wbr':
			return true;
		default:
			return false;
	endswitch;
}

function glazeElementTagNameIsBlockLevel($tagName)
{	
	$tagName = strtolower($tagName);
	
	// https://developer.mozilla.org/en-US/docs/HTML/Block-level_elements
	switch ($tagName):
		case 'address':
		case 'article':
		case 'aside':
		case 'audio':
		case 'blockquote':
		case 'canvas':
		case 'dd':
		case 'div':
		case 'dl':
		case 'fieldset':
		case 'figcaption':
		case 'figure':
		case 'footer':
		case 'form':
		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
		case 'header':
		case 'hgroup':
		case 'hr':
		case 'noscript':
		case 'ol':
		case 'output':
		case 'p':
		case 'pre':
		case 'section':
		case 'table':
		case 'tfoot':
		case 'ul':
		case 'video':
			return true;
		// Also include list items
		case 'li':
		case 'nav':
			return true;
		default:
			return false;
	endswitch;
}

function glazeElementTagNameBelongsInHead($tagName)
{
	$tagName = strtolower($tagName);
	
	switch ($tagName):
		case 'title':
		case 'meta':
		case 'link':
			return true;
		default:
			return false;
	endswitch;
}


/* Feeling Glazy? */

/* private */ function &glazyGetOpenElements($begin = false)
{
	global $glazyOpenElements;
	
	if ($begin and !isset($glazyOpenElements)) {
		$glazyOpenElements = array();
	}
	
	return $glazyOpenElements;
}

/* private */ function &glazyGetElementsBuffer($options = null)
{
	global $glazyElementsBuffer;
	
	if (isset($options['clean'])) {
		$glazyElementsBuffer = null; // See http://au1.php.net/unset
	}
	
	if (isset($options['begin'])) {
		if (!isset($glazyElementsBuffer)) {
			$glazyElementsBuffer = '';
		}
	}
	
	return $glazyElementsBuffer;
}

/* private */ function glazyAddToElementsBuffer($string)
{
	$glazyElementsBuffer = &glazyGetElementsBuffer();
	// If elements buffer is being used append it there, otherwise just display as is.
	if (isset($glazyElementsBuffer)) {
		$glazyElementsBuffer .= $string;
	}
	else {
		echo $string;
	}
}

/* private */ function glazyCopyAndCleanElementsBuffer()
{
	$glazyElementsBuffer = glazyGetElementsBuffer();
	$copy = $glazyElementsBuffer;
	
	glazyGetElementsBuffer(array('clean' => true));
	
	return $copy;
}

function glazyEnsureOpeningTagForLatestElementIsDisplayed()
{
	$glazyOpenElements = &glazyGetOpenElements();
	if (empty($glazyOpenElements)) {
		return;
	}
	
	$latestOpenElement = &$glazyOpenElements[count($glazyOpenElements) - 1];
	
	if (!$latestOpenElement['openTagDone']) {
		echo glazyCopyAndCleanElementsBuffer();
		echo '>';
		
		if (glazeElementTagNameIsBlockLevel($latestOpenElement['tagName'])) {
			echo "\n";
		}
		
		$latestOpenElement['openTagDone'] = true;
	}
}

/* Glazy Attributes */
// Can be used by themselves.

function glazyAttribute($attributeName, $attributeValue, $valueType = null)
{
	$string = glazeAttribute($attributeName, $attributeValue, $valueType);
	
	glazyAddToElementsBuffer($string);
}

function glazyAttributeCheck($attributeName, &$attributeValueToCheck, $attributeValueToUse = null, $valueType = null)
{
	$string = glazeAttributeCheck($attributeName, $attributeValueToCheck, $attributeValueToUse, $valueType);
	
	glazyAddToElementsBuffer($string);
}

function glazyAttributesArray($attributes)
{
	if (empty($attributes)):
		return;
	endif;
	
	foreach ($attributes as $attributeName => $attributeValue):
		glazyAttribute($attributeName, $attributeValue);
	endforeach;
}


/* private */ function glazyElementInfoForPassedOptions($tagNameOrElementOptions)
{
	$attributes = array();
	
	// String of form: tagName#elementID.multiple.class.names
	// The ID and classes are optional.
	if (is_string($tagNameOrElementOptions)):
		// Extract classes
		$parts = explode('.', $tagNameOrElementOptions);
		$elementInfo = $parts[0];
		// Add found classes
		if (count($parts) > 1):
			$elementClassNameParts = array_slice($parts, 1);
		endif;
		
		// Extract ID
		$parts = explode('#', $elementInfo);
		// Tag name is the left over
		$tagName = $parts[0];
		// Add found ID
		if (count($parts) > 1):
			$elementID = $parts[1];
		endif;
		
		// Set attributes, in our preferred order.
		if (!empty($elementID)):
			$attributes['id'] = $elementID;
		endif;
		
		if (!empty($elementClassNameParts)):
			$attributes['class'] = $elementClassNameParts;
		endif;
	// Array with required tagName and optional attributes as extra keys.
	elseif (is_array($tagNameOrElementOptions)):
		// Tag name is specified in the dict
		$tagName = $tagNameOrElementOptions['tagName'];
		// Attributes are all the other keys.
		$attributes = $tagNameOrElementOptions;
		unset($attributes['tagName']);
	endif;
	
	return array(
		'tagName' => $tagName,
		'attributes' => $attributes
	);
}

function glazyElement($tagNameOrElementOptions, $contentsValue = null, $valueType = null)
{
	glazyEnsureOpeningTagForLatestElementIsDisplayed();
	
	
	$elementInfo = glazyElementInfoForPassedOptions($tagNameOrElementOptions);
	$tagName = $elementInfo['tagName'];
	$attributes = $elementInfo['attributes'];
	
	
	echo "<$tagName";
	
	if (!empty($attributes)):
		glazyAttributesArray($attributes);
	endif;
	
	echo '>';
	
	if (!is_null($contentsValue)):
		echo glazeValue($contentsValue, $valueType);
	endif;
	
	if (!glazeElementTagNameIsSelfClosing($tagName)):
		echo "</$tagName>";
	endif;
	
	if (glazeElementTagNameIsBlockLevel($tagName) || glazeElementTagNameBelongsInHead($tagName)):
		echo "\n";
	endif;
}

/* Convenience for lazy debugging */
function glazyPrintR($object)
{
	glazyElement('pre', print_r($object, true));
}


function glazyBegin($tagNameOrElementOptions, $valueType = GLAZE_TYPE_PREGLAZED)
// TODO: Possibly the tagName in glazyBegin() would be optional,
//       or another begin function could be created, - glazyPrepare(), glazyServe()
//       allowing you to wrap a whole bunch of elements easily together
//       and ensure they are closed.
{
	glazyEnsureOpeningTagForLatestElementIsDisplayed();
	
	$elementInfo = glazyElementInfoForPassedOptions($tagNameOrElementOptions);
	$tagName = $elementInfo['tagName'];
	$attributes = $elementInfo['attributes'];
	
	
	echo "<$tagName";
	
	$elementsBuffer = &glazyGetElementsBuffer(array('begin' => true));
	$openElements = &glazyGetOpenElements(true);
	$openElementsCount = count($openElements);
	
	if (empty($openElements)) {
		ob_start();
	}
	else {
		ob_start();
	}
	
	$openElements[] = array(
		'tagName' => $tagName,
		'openTagDone' => false,
		'valueType' => $valueType
	);
	
	if (!empty($attributes)):
		glazyAttributesArray($attributes);
	endif;
	
	// Return info for glazyClose.
	return array(
		'glazyBegin' => true,
		'tagName' => $tagName,
		'previousOpenElementsCount' => $openElementsCount
	);
}

function glazyClose($openedElementInfo = null)
{
	$openElements = &glazyGetOpenElements();
	
	$repeatCount = 1;
	
	if (!empty($openedElementInfo['previousOpenElementsCount'])) {
		$previousCount = $openedElementInfo['previousOpenElementsCount'];
		$currentCount = count($openElements);
		$repeatCount = $currentCount - $previousCount;
	}
	
	$outputtedString = ob_get_clean();
	//$outputtedString = ob_get_contents();
	//ob_clean();
	
	glazyEnsureOpeningTagForLatestElementIsDisplayed();
	
	while ($repeatCount--) {
		$elementInfo = array_pop($openElements);
		$valueType = $elementInfo['valueType'];
		
		if (!empty($outputtedString)) {
			echo glazeValue($outputtedString, $valueType);
		}
		
		$tagName = $elementInfo['tagName'];
		
		if (!glazeElementTagNameIsSelfClosing($tagName)) {
			echo "</$tagName>";
		}
		
		if (glazeElementTagNameIsBlockLevel($tagName) || glazeElementTagNameBelongsInHead($tagName)) {
			echo "\n";
		}
		
		$outputtedString = null;
	}
}
