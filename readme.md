# PoParser
[![Build Status](https://api.travis-ci.org/MAXakaWIZARD/PoParser.png?branch=master)](https://travis-ci.org/MAXakaWIZARD/PoParser) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MAXakaWIZARD/PoParser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/MAXakaWIZARD/PoParser/?branch=master)
[![Coverage Status](https://coveralls.io/repos/MAXakaWIZARD/PoParser/badge.svg?branch=master)](https://coveralls.io/r/MAXakaWIZARD/PoParser?branch=master)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/14f7f95a-b068-44b8-8b0c-64575764944d.svg)](https://insight.sensiolabs.com/projects/14f7f95a-b068-44b8-8b0c-64575764944d)

[![GitHub tag](https://img.shields.io/github/tag/MAXakaWIZARD/PoParser.svg?label=latest)](https://packagist.org/packages/maxakawizard/po-parser) 
[![Packagist](https://img.shields.io/packagist/dt/maxakawizard/po-parser.svg)](https://packagist.org/packages/maxakawizard/po-parser)
[![Packagist](https://img.shields.io/packagist/dm/maxakawizard/po-parser.svg)](https://packagist.org/packages/maxakawizard/po-parser)

[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.3-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/maxakawizard/po-parser.svg)](https://packagist.org/packages/maxakawizard/po-parser)

Gettext *.po files parser for PHP.

This package is compliant with [PSR-4](http://www.php-fig.org/psr/4/), [PSR-1](http://www.php-fig.org/psr/1/), and [PSR-2](http://www.php-fig.org/psr/2/).
If you notice compliance oversights, please send a patch via pull request.

## Usage
### Read file content
```php
$parser = new PoParser\Parser();
$parser->read('my-pofile.po');
$entries = $parser->getEntriesAsArrays();
// Now $entries contains every string information in your pofile

echo '<ul>';
foreach ($entries as $entry) {
   echo '<li>'.
   '<b>msgid:</b> '.$entry['msgid'].'<br>'.         // Message ID
   '<b>msgstr:</b> '.$entry['msgstr'].'<br>'.       // Translation
   '<b>reference:</b> '.$entry['reference'].'<br>'. // Reference
   '<b>msgctxt:</b> ' . $entry['msgctxt'].'<br>'.   // Message Context
   '<b>tcomment:</b> ' . $entry['tcomment'].'<br>'. // Translator comment
   '<b>ccomment:</b> ' . $entry['ccomment'].'<br>'. // Code Comment
   '<b>obsolete?:</b> '.(string)$entry['obsolete'].'<br>'. // Is obsolete?
    '<b>fuzzy?:</b> ' .(string)$entry['fuzzy'].     // Is fuzzy?
    '</li>';
}
echo '</ul>';
```

### Modify content
```php
$parser = new PoParser\Parser();
$parser->read('my-pofile.po');
// Entries are stored in array, so you can modify them.

// Use updateEntry method to change messages you want.
$parser->updateEntry('Write your email', 'Escribe tu email');
$parser->write('my-pofile.po');
```

## Todo
* Improve interface to edit entries.
* Discover what's the meaning of "#@ " line.

## License
This library is released under [MIT](http://www.tldrlegal.com/license/mit-license) license.
